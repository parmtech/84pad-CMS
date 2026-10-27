<?php

namespace App\Support\Updates;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class CoreSnapshot
{
    private const DIRECTORIES = [
        'app', 'bootstrap', 'config', 'database', 'routes', 'resources', 'public',
    ];
    private const ROOT_FILES = ['artisan', 'composer.json', 'composer.lock', 'phpunit.xml'];
    private const MAX_FILES = 20000;
    private const MAX_BYTES = 268435456;

    /** @return array{path:string,files:int,bytes:int,sha256:string} */
    public function create(): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZIP extension is required.');
        }

        $root = base_path();
        $entries = [];
        $bytes = 0;
        foreach (self::ROOT_FILES as $relative) {
            if (is_file($root.'/'.$relative) && ! is_link($root.'/'.$relative)) {
                $this->includeFile($entries, $bytes, $root, $relative);
            }
        }
        foreach (self::DIRECTORIES as $folder) {
            $directory = $root.'/'.$folder;
            if (! is_dir($directory) || is_link($directory)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $node) {
                if (! $node->isFile() || $node->isLink()) {
                    continue;
                }
                $relative = substr($node->getPathname(), strlen($root) + 1);
                if ($this->excluded($relative)) {
                    continue;
                }
                $this->includeFile($entries, $bytes, $root, $relative);
            }
        }

        if ($entries === []) {
            throw new RuntimeException('Core source tree is empty.');
        }
        ksort($entries, SORT_STRING);

        $dir = storage_path('app/private/core-snapshots');
        File::ensureDirectoryExists($dir, 0700);
        if (is_link($dir) || ! is_dir($dir)) {
            throw new RuntimeException('Private snapshot directory unsafe.');
        }

        $target = $dir.'/core-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(8)).'.zip';
        $staging = $target.'.partial';
        $zip = new ZipArchive();
        if ($zip->open($staging, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('Cannot create snapshot archive.');
        }

        try {
            $manifest = [
                'format' => '84pad-core-source-v1',
                'created_utc' => gmdate('c'),
                'files' => $entries,
            ];
            foreach (array_keys($entries) as $relative) {
                if (! $zip->addFile($root.'/'.$relative, 'source/'.$relative)) {
                    throw new RuntimeException('Failed to archive source file.');
                }
            }
            if (! $zip->addFromString(
                'snapshot.json',
                json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
            )) {
                throw new RuntimeException('Failed to archive snapshot manifest.');
            }
        } finally {
            $zip->close();
        }

        if (! $this->verify($staging) || ! rename($staging, $target)) {
            @unlink($staging);
            throw new RuntimeException('Snapshot failed verification or publishing.');
        }

        @chmod($target, 0600);
        return [
            'path' => $target,
            'files' => count($entries),
            'bytes' => $bytes,
            'sha256' => hash_file('sha256', $target),
        ];
    }

    /** Verify the private code-only snapshot and its permitted source paths. */
    public function verify(string $path): bool
    {
        if (! class_exists(ZipArchive::class) || ! is_file($path)
            || is_link($path) || ! is_readable($path)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }
        try {
            $raw = $zip->getFromName('snapshot.json');
            if (! is_string($raw) || strlen($raw) > 3000000) {
                return false;
            }
            $manifest = json_decode($raw, true, 32);
            if (! is_array($manifest)
                || ($manifest['format'] ?? null) !== '84pad-core-source-v1'
                || ! is_array($manifest['files'] ?? null)
                || count($manifest['files']) < 1
                || count($manifest['files']) > self::MAX_FILES
                || $zip->numFiles !== count($manifest['files']) + 1) {
                return false;
            }

            $bytes = 0;
            foreach ($manifest['files'] as $relative => $expected) {
                if (! is_string($relative)
                    || ! is_array($expected)
                    || ! preg_match('/^[A-Fa-f0-9]{64}$/D', (string) ($expected['sha256'] ?? ''))
                    || ! is_int($expected['size'] ?? null)
                    || $expected['size'] < 0
                    || ! $this->allowedSnapshotPath($relative)
                    || str_contains($relative, '\\')
                    || $this->excluded($relative)
                    || str_contains($relative, '..')
                    || str_starts_with($relative, '/')) {
                    return false;
                }
                $name = 'source/'.$relative;
                $index = $zip->locateName($name);
                if ($index === false) {
                    return false;
                }
                $stat = $zip->statIndex($index);
                if (! is_array($stat) || $stat['size'] !== $expected['size']) {
                    return false;
                }
                $bytes += $stat['size'];
                if ($bytes > self::MAX_BYTES) {
                    return false;
                }
                $stream = $zip->getStream($name);
                if ($stream === false) {
                    return false;
                }
                $hash = hash_init('sha256');
                hash_update_stream($hash, $stream);
                fclose($stream);
                if (! hash_equals($expected['sha256'], hash_final($hash))) {
                    return false;
                }
            }
            return true;
        } finally {
            $zip->close();
        }
    }

    /**
     * Restore the files present in a verified source snapshot. This is not a
     * whole-site rollback: files absent from the snapshot and excluded data
     * (database, vendor, plugins, uploads, and .env) are left in place.
     *
     * @return array{files:int,rollback_path:string,rollback_sha256:string}
     */
    public function restore(string $path, string $expectedSha256, ?array $rollbackPoint = null): array
    {
        if (! preg_match('/^[a-f0-9]{64}$/iD', $expectedSha256)
            || ! is_file($path) || is_link($path)
            || ! hash_equals(strtolower($expectedSha256), strtolower((string) hash_file('sha256', $path)))
            || ! $this->verify($path)) {
            throw new RuntimeException('Snapshot path, SHA-256, or integrity check is invalid.');
        }

        // Create and verify a recovery point before replacing any source file.
        $rollback = $rollbackPoint ?? $this->create();
        if (! is_string($rollback['path'] ?? null)
            || ! is_string($rollback['sha256'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}$/iD', $rollback['sha256'])
            || ! is_file($rollback['path']) || is_link($rollback['path'])
            || ! hash_equals(strtolower($rollback['sha256']), strtolower((string) hash_file('sha256', $rollback['path'])))
            || ! $this->verify($rollback['path'])) {
            throw new RuntimeException('Pre-restore recovery snapshot is invalid. No source files were changed.');
        }
        $snapshotPaths = $this->snapshotPaths($path);
        $rollbackPaths = $this->snapshotPaths($rollback['path']);
        try {
            $files = $this->applySnapshot($path);
        } catch (Throwable) {
            try {
                $this->applySnapshot($rollback['path']);
                $root = realpath(base_path());
                if ($root === false) {
                    throw new RuntimeException('Core source root is unavailable during recovery.');
                }
                foreach (array_diff($snapshotPaths, $rollbackPaths) as $relative) {
                    $extra = $this->safeTarget($root, $relative);
                    if (is_file($extra) && ! is_link($extra)) {
                        @unlink($extra);
                    }
                }
            } catch (Throwable) {
                throw new CoreSnapshotRestoreException(
                    'Restore failed and automatic recovery was incomplete. '
                    .'Pre-restore snapshot: '.$rollback['path'].' (SHA-256 '.$rollback['sha256'].').',
                    false
                );
            }

            throw new CoreSnapshotRestoreException(
                'Restore failed; previous source files were restored. '
                .'Pre-restore snapshot: '.$rollback['path'].' (SHA-256 '.$rollback['sha256'].').',
                true
            );
        }

        return [
            'files' => $files,
            'rollback_path' => $rollback['path'],
            'rollback_sha256' => $rollback['sha256'],
        ];
    }

    private function applySnapshot(string $path): int
    {
        if (! $this->verify($path)) {
            throw new RuntimeException('Recovery snapshot failed integrity verification.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Recovery snapshot is not a readable ZIP.');
        }
        $zipOpen = true;

        $privateRoot = storage_path('app/private');
        if (is_link($privateRoot)) {
            $zip->close();
            throw new RuntimeException('Private application storage is unsafe.');
        }
        File::ensureDirectoryExists($privateRoot, 0700);
        if (is_link($privateRoot) || ! is_dir($privateRoot)) {
            $zip->close();
            throw new RuntimeException('Private application storage is unsafe.');
        }
        $private = $privateRoot.DIRECTORY_SEPARATOR.'core-restore-staging';
        File::ensureDirectoryExists($private, 0700);
        $privateReal = realpath($private);
        $privateRootReal = realpath($privateRoot);
        if (is_link($private) || ! is_dir($private)
            || $privateReal === false || $privateRootReal === false
            || ! str_starts_with($privateReal, $privateRootReal.DIRECTORY_SEPARATOR)) {
            $zip->close();
            throw new RuntimeException('Private restore staging directory is unsafe.');
        }
        $stage = $private.DIRECTORY_SEPARATOR.bin2hex(random_bytes(16));
        if (! mkdir($stage, 0700)) {
            $zip->close();
            throw new RuntimeException('Private restore staging directory could not be created.');
        }

        $staged = [];
        $applied = [];
        try {
            $raw = $zip->getFromName('snapshot.json');
            $manifest = is_string($raw) ? json_decode($raw, true, 32) : null;
            if (! is_array($manifest) || ! is_array($manifest['files'] ?? null)) {
                throw new RuntimeException('Recovery manifest is invalid.');
            }

            foreach ($manifest['files'] as $relative => $expected) {
                if (! is_string($relative) || ! is_array($expected)
                    || ! $this->allowedSnapshotPath($relative)) {
                    throw new RuntimeException('Recovery manifest contains an unsupported path.');
                }
                $stageFile = $stage.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR
                    .str_replace('/', DIRECTORY_SEPARATOR, $relative);
                $this->ensureStageDirectory($stage, dirname($stageFile));
                $source = $zip->getStream('source/'.$relative);
                $destination = @fopen($stageFile, 'xb');
                if ($source === false || $destination === false) {
                    if (is_resource($source)) fclose($source);
                    if (is_resource($destination)) fclose($destination);
                    throw new RuntimeException('A snapshot file could not be staged.');
                }
                $copied = stream_copy_to_stream($source, $destination);
                fclose($source);
                fclose($destination);
                if ($copied !== $expected['size']
                    || ! hash_equals($expected['sha256'], (string) hash_file('sha256', $stageFile))) {
                    throw new RuntimeException('A staged snapshot file failed integrity verification.');
                }
                $staged[$relative] = $stageFile;
            }
            $zip->close();
            $zipOpen = false;

            $root = realpath(base_path());
            if ($root === false) {
                throw new RuntimeException('Core source root is unavailable.');
            }
            $token = bin2hex(random_bytes(8));
            foreach ($staged as $relative => $stageFile) {
                $target = $this->safeTarget($root, $relative);
                $temporary = $target.'.84pad-restore-'.$token.'.tmp';
                $backup = $target.'.84pad-restore-'.$token.'.bak';
                if (file_exists($temporary) || is_link($temporary)
                    || file_exists($backup) || is_link($backup)
                    || ! copy($stageFile, $temporary)) {
                    throw new RuntimeException('A source file could not be prepared for restoration.');
                }

                $permissions = is_file($target) ? fileperms($target) : false;
                if (is_file($target) && $permissions === false) {
                    @unlink($temporary);
                    throw new RuntimeException('Existing source file permissions could not be read.');
                }
                $mode = $permissions === false ? 0644 : ($permissions & 0777);
                if (! @chmod($temporary, $mode)) {
                    @unlink($temporary);
                    throw new RuntimeException('Restored source file permissions could not be set.');
                }
                $hadTarget = is_file($target);
                if ($hadTarget && ! rename($target, $backup)) {
                    @unlink($temporary);
                    throw new RuntimeException('An existing source file could not be backed up.');
                }
                if (! rename($temporary, $target)) {
                    if ($hadTarget) @rename($backup, $target);
                    @unlink($temporary);
                    throw new RuntimeException('A restored source file could not be installed.');
                }
                $applied[] = ['target' => $target, 'backup' => $hadTarget ? $backup : null];
            }

            foreach ($applied as $change) {
                if (is_string($change['backup']) && is_file($change['backup'])) {
                    @unlink($change['backup']);
                }
            }

            return count($staged);
        } catch (Throwable $error) {
            if ($zipOpen) {
                $zip->close();
                $zipOpen = false;
            }
            foreach (array_reverse($applied) as $change) {
                if (is_file($change['target']) && ! is_link($change['target'])) {
                    @unlink($change['target']);
                }
                if (is_string($change['backup']) && is_file($change['backup'])
                    && ! is_link($change['backup'])) {
                    @rename($change['backup'], $change['target']);
                }
            }
            throw $error;
        } finally {
            File::deleteDirectory($stage);
        }
    }

    /** @return list<string> */
    private function snapshotPaths(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Recovery snapshot is not a readable ZIP.');
        }
        try {
            $raw = $zip->getFromName('snapshot.json');
            $manifest = is_string($raw) ? json_decode($raw, true, 32) : null;
            if (! is_array($manifest) || ! is_array($manifest['files'] ?? null)) {
                throw new RuntimeException('Recovery manifest is invalid.');
            }

            return array_keys($manifest['files']);
        } finally {
            $zip->close();
        }
    }

    private function allowedSnapshotPath(string $relative): bool
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, "\0")
            || str_contains($relative, '\\')) {
            return false;
        }
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }
        if (in_array($relative, self::ROOT_FILES, true)) {
            return true;
        }
        foreach (self::DIRECTORIES as $directory) {
            if (str_starts_with($relative, $directory.'/')) {
                return true;
            }
        }
        return false;
    }

    private function ensureStageDirectory(string $stage, string $directory): void
    {
        $root = realpath($stage);
        if ($root === false) {
            throw new RuntimeException('Private restore staging directory is unavailable.');
        }
        $relative = substr($directory, strlen($stage) + 1);
        $cursor = $stage;
        foreach (explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('Invalid staged path.');
            }
            $cursor .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($cursor)) {
                throw new RuntimeException('A staged path is a symbolic link.');
            }
            if (! is_dir($cursor) && ! mkdir($cursor, 0700)) {
                throw new RuntimeException('A staged directory could not be created.');
            }
            $resolved = realpath($cursor);
            if ($resolved === false || ! str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('Staged path escaped its private directory.');
            }
        }
    }

    private function safeTarget(string $root, string $relative): string
    {
        if (! $this->allowedSnapshotPath($relative) || str_contains($relative, '\\')) {
            throw new RuntimeException('Snapshot path is outside Core source directories.');
        }
        $segments = explode('/', $relative);
        $cursor = $root;
        $filename = array_pop($segments);
        foreach ($segments as $segment) {
            $cursor .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($cursor) || (file_exists($cursor) && ! is_dir($cursor))) {
                throw new RuntimeException('A Core source directory is not safe to restore.');
            }
            if (! is_dir($cursor) && ! mkdir($cursor, 0755)) {
                throw new RuntimeException('A Core source directory could not be created.');
            }
            $resolved = realpath($cursor);
            if ($resolved === false || ! str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('Core source path escaped the application root.');
            }
        }
        $target = $cursor.DIRECTORY_SEPARATOR.$filename;
        if (is_link($target) || (file_exists($target) && ! is_file($target))) {
            throw new RuntimeException('A Core source file is not safe to replace.');
        }

        return $target;
    }

    private function includeFile(array &$entries, int &$bytes, string $root, string $relative): void
    {
        if ($this->excluded($relative)) {
            return;
        }
        $path = $root.'/'.$relative;
        $size = filesize($path);
        if ($size === false || $size > self::MAX_BYTES) {
            throw new RuntimeException('Unreadable source file.');
        }
        $bytes += $size;
        if ($bytes > self::MAX_BYTES || count($entries) >= self::MAX_FILES) {
            throw new RuntimeException('Core snapshot limits exceeded.');
        }
        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new RuntimeException('Cannot hash a Core source file.');
        }
        $entries[$relative] = ['size' => $size, 'sha256' => $hash];
    }

    private function excluded(string $relative): bool
    {
        $segments = explode('/', str_replace('\\', '/', $relative));
        $filename = strtolower(basename($relative));
        if (in_array($filename, ['auth.json', 'credentials.json', 'secrets.json', 'id_rsa', 'id_ed25519'], true)
            || str_ends_with($filename, '.pem')
            || str_ends_with($filename, '.key')
            || str_ends_with($filename, '.p12')
            || str_ends_with($filename, '.pfx')) {
            return true;
        }
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..'
                || (str_starts_with($segment, '.') && $segment !== '.htaccess')) {
                return true;
            }
        }
        if (str_starts_with($relative, 'public/storage/')
            || str_starts_with($relative, 'public/uploads/')
            || str_starts_with($relative, 'bootstrap/cache/')) {
            return true;
        }
        return false;
    }
}

