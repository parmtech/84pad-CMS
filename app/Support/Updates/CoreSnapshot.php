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

    /** Verify the private code-only snapshot, never extract/restore it. */
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
