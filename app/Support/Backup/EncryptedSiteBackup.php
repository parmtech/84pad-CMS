<?php

namespace App\Support\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveCallbackFilterIterator;
use RuntimeException;
use Throwable;

/**
 * Streaming authenticated encryption for site exports.
 * Never writes a plaintext archive, and intentionally has no restore method.
 */
final class EncryptedSiteBackup
{
    private const MAGIC = "84PAD-SITE-BACKUP-1\n";
    private const MAX_RECORD = 16777216;
    private const MAX_FILES = 100000;
    private const MAX_BYTES = 4294967296;
    private const ROOT_FILES = ['.env', '.env.example', 'artisan', 'composer.json', 'composer.lock'];
    private const DIRECTORIES = [
        'app', 'bootstrap', 'config', 'database', 'resources', 'routes',
        'public', 'plugins', 'vendor', 'storage/app/public', 'storage/app/private',
    ];

    public function __construct(
        private readonly ?string $sourceRoot = null,
        private readonly ?string $destinationDirectory = null,
    ) {}

    /** @return array{path:string,sha256:string,files:int,bytes:int,tables:int,rows:int} */
    public function create(): array
    {
        $key = $this->key();
        if (app()->environment('production')) {
            $maintenance = storage_path('framework/down');
            if (! is_file($maintenance) || is_link($maintenance)) {
                throw new RuntimeException('Production site backup requires maintenance mode and paused external writers.');
            }
        }
        $root = realpath($this->sourceRoot ?? base_path());
        if ($root === false) {
            throw new RuntimeException('Application root is missing.');
        }
        foreach (['.env', 'artisan', 'composer.json', 'vendor/autoload.php'] as $required) {
            if (! is_file($root.'/'.$required) || is_link($root.'/'.$required)) {
                throw new RuntimeException('Site backup requires an installed app and private configuration.');
            }
        }

        $dir = $this->destinationDirectory ?? storage_path('app/private/site-backups');
        if (is_link($dir)) {
            throw new RuntimeException('Backup destination is a symlink.');
        }
        File::ensureDirectoryExists($dir, 0700);
        $actual = realpath($dir);
        $public = realpath(public_path());
        if ($actual === false || is_link($dir)
            || ($public !== false && ($actual === $public || str_starts_with($actual, $public.DIRECTORY_SEPARATOR)))) {
            throw new RuntimeException('Backup destination is not private.');
        }

        $path = $dir.'/site-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(8)).'.padbackup';
        $partial = $path.'.partial';
        $fp = @fopen($partial, 'xb');
        if ($fp === false) {
            throw new RuntimeException('Encrypted backup file could not be created.');
        }
        @chmod($partial, 0600);
        try {
            [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
            $this->writeAll($fp, self::MAGIC.$header);
            $this->push($fp, $state, 'H'.json_encode([
                'format' => '84pad-site-backup-v1', 'created_utc' => gmdate('c'),
            ], JSON_THROW_ON_ERROR));
            $stats = ['files' => 0, 'bytes' => 0, 'tables' => 0, 'rows' => 0];
            $this->exportDatabase($fp, $state, $stats);
            $this->exportFiles($fp, $state, $root, $stats);
            $this->push($fp, $state, 'Z'.json_encode($stats, JSON_THROW_ON_ERROR), true);
            if (! fflush($fp)) {
                throw new RuntimeException('Backup could not be flushed.');
            }
            fclose($fp);
            $fp = null;
            if (! $this->verify($partial) || ! @rename($partial, $path)) {
                throw new RuntimeException('Backup failed verification.');
            }
            @chmod($path, 0600);
            return ['path' => $path, 'sha256' => (string) hash_file('sha256', $path)] + $stats;
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
            if (is_file($partial)) {
                @unlink($partial);
            }
        }
    }

    /** Authenticated, read-only verification, without SQL execution or file extraction. */
    public function verify(string $path): bool
    {
        if (! is_file($path) || is_link($path) || ! is_readable($path)) {
            return false;
        }
        try {
            $key = $this->key();
            $fp = @fopen($path, 'rb');
            if ($fp === false) {
                return false;
            }
            try {
                if ($this->readExact($fp, strlen(self::MAGIC)) !== self::MAGIC) {
                    return false;
                }
                $header = $this->readExact($fp, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
                if ($header === false) {
                    return false;
                }
                $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
                $stats = ['files' => 0, 'bytes' => 0, 'tables' => 0, 'rows' => 0];
                $started = false;
                $tableOpen = false;
                $file = null;
                $seen = [];
                while (true) {
                    $len = $this->readExact($fp, 4);
                    if ($len === false) {
                        return false;
                    }
                    $size = unpack('Nsize', $len)['size'];
                    if ($size < 18 || $size > self::MAX_RECORD + 32) {
                        return false;
                    }
                    $cipher = $this->readExact($fp, $size);
                    if ($cipher === false) {
                        return false;
                    }
                    $decrypted = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $cipher);
                    if ($decrypted === false) {
                        return false;
                    }
                    [$record, $tag] = $decrypted;
                    if ($record === '') {
                        return false;
                    }
                    $type = $record[0];
                    $payload = substr($record, 1);
                    if (! $started) {
                        $meta = $type === 'H' ? json_decode($payload, true) : null;
                        if (! is_array($meta) || ($meta['format'] ?? null) !== '84pad-site-backup-v1'
                            || $tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE) {
                            return false;
                        }
                        $started = true;
                        continue;
                    }
                    if ($type === 'T' && ! $tableOpen && $file === null) {
                        $data = json_decode($payload, true);
                        if (! is_array($data) || ! is_string($data['name'] ?? null)
                            || ! preg_match('/^[A-Za-z0-9_]+$/D', $data['name'])
                            || ! is_string($data['create'] ?? null) || $data['create'] === '') {
                            return false;
                        }
                        $tableOpen = true;
                        $stats['tables']++;
                    } elseif ($type === 'R' && $tableOpen) {
                        $data = json_decode($payload, true);
                        if (! is_array($data) || $data === []) {
                            return false;
                        }
                        foreach ($data as $column => $value) {
                            if (! is_string($column)
                                || ($value !== null && (! is_string($value) || base64_decode($value, true) === false))) {
                                return false;
                            }
                        }
                        $stats['rows']++;
                    } elseif ($type === 'E' && $tableOpen && $payload === '') {
                        $tableOpen = false;
                    } elseif ($type === 'F' && ! $tableOpen && $file === null) {
                        $data = json_decode($payload, true);
                        if (! is_array($data) || ! is_string($data['path'] ?? null)
                            || ! $this->safePath($data['path']) || isset($seen[$data['path']])
                            || ! is_int($data['size'] ?? null) || $data['size'] < 0) {
                            return false;
                        }
                        $seen[$data['path']] = true;
                        $file = ['size' => $data['size'], 'received' => 0, 'hash' => hash_init('sha256')];
                    } elseif ($type === 'B' && $file !== null) {
                        $file['received'] += strlen($payload);
                        $stats['bytes'] += strlen($payload);
                        if ($file['received'] > $file['size'] || $stats['bytes'] > self::MAX_BYTES) {
                            return false;
                        }
                        hash_update($file['hash'], $payload);
                    } elseif ($type === 'X' && $file !== null) {
                        if ($file['received'] !== $file['size']
                            || ! preg_match('/^[a-f0-9]{64}$/D', $payload)
                            || ! hash_equals($payload, hash_final($file['hash']))) {
                            return false;
                        }
                        $file = null;
                        $stats['files']++;
                        if ($stats['files'] > self::MAX_FILES) {
                            return false;
                        }
                    } elseif ($type === 'Z' && ! $tableOpen && $file === null) {
                        return json_decode($payload, true) === $stats
                            && $stats['files'] > 0
                            && $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                            && fgetc($fp) === false;
                    } else {
                        return false;
                    }
                    if ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE) {
                        return false;
                    }
                }
            } finally {
                fclose($fp);
            }
        } catch (Throwable) {
            return false;
        }
    }

    private function exportDatabase($fp, string &$state, array &$stats): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if (! in_array($driver, ['sqlite', 'mysql'], true)) {
            throw new RuntimeException('Only SQLite or MySQL database export is supported.');
        }
        $connection->beginTransaction();
        try {
            $pdo = $connection->getPdo();
            $tables = [];
            if ($driver === 'mysql') {
                foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
                    if (($row[1] ?? null) === 'BASE TABLE') {
                        $tables[] = (string) $row[0];
                    }
                }
            } else {
                $tables = $pdo->query(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
                )->fetchAll(PDO::FETCH_COLUMN);
            }
            sort($tables, SORT_STRING);
            $quote = $driver === 'mysql' ? chr(96) : '"';
            foreach ($tables as $table) {
                if (! preg_match('/^[A-Za-z0-9_]+$/D', $table)) {
                    throw new RuntimeException('Nonportable table name.');
                }
                $name = $quote.str_replace($quote, $quote.$quote, $table).$quote;
                if ($driver === 'mysql') {
                    $definition = $pdo->query('SHOW CREATE TABLE '.$name)->fetch(PDO::FETCH_NUM);
                    $create = $definition[1] ?? null;
                } else {
                    $query = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?");
                    $query->execute([$table]);
                    $create = $query->fetchColumn();
                }
                if (! is_string($create) || $create === '') {
                    throw new RuntimeException('Database table schema could not be exported.');
                }
                $this->push($fp, $state, 'T'.json_encode(['name' => $table, 'create' => $create], JSON_THROW_ON_ERROR));
                $stats['tables']++;
                $rows = $pdo->query('SELECT * FROM '.$name);
                while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                    $encoded = [];
                    foreach ($row as $column => $value) {
                        if ($value !== null && ! is_string($value) && ! is_int($value) && ! is_float($value)) {
                            throw new RuntimeException('Unsupported database cell type.');
                        }
                        $encoded[$column] = $value === null ? null : base64_encode((string) $value);
                    }
                    $this->push($fp, $state, 'R'.json_encode($encoded, JSON_THROW_ON_ERROR));
                    $stats['rows']++;
                }
                $rows->closeCursor();
                $this->push($fp, $state, 'E');
            }
            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();
            throw new RuntimeException('Database export failed.', 0, $e);
        }
    }

    private function exportFiles($fp, string &$state, string $root, array &$stats): void
    {
        $files = [];
        foreach (self::ROOT_FILES as $relative) {
            if (is_file($root.'/'.$relative) && ! is_link($root.'/'.$relative)) {
                $files[$relative] = $root.'/'.$relative;
            }
        }
        foreach (self::DIRECTORIES as $dir) {
            $start = $root.'/'.$dir;
            if (! file_exists($start) && ! is_link($start)) {
                continue;
            }
            if (! is_dir($start) || is_link($start)) {
                throw new RuntimeException('Unsafe site directory.');
            }
            $filter = new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($start, \FilesystemIterator::SKIP_DOTS),
                function ($node) use ($root): bool {
                    $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($node->getPathname(), strlen($root) + 1));
                    if ($this->excluded($relative) || $relative === 'public/storage') {
                        return false;
                    }
                    if ($node->isLink()) {
                        throw new RuntimeException('Symlink in site backup input.');
                    }
                    return true;
                }
            );
            foreach (new RecursiveIteratorIterator($filter) as $node) {
                if ($node->isFile()) {
                    $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($node->getPathname(), strlen($root) + 1));
                    if (! $this->safePath($relative)) {
                        throw new RuntimeException('Invalid site file path.');
                    }
                    $files[$relative] = $node->getPathname();
                }
            }
        }
        ksort($files, SORT_STRING);
        foreach ($files as $relative => $path) {
            $size = filesize($path);
            if (! is_int($size) || $size < 0 || $stats['files'] >= self::MAX_FILES
                || $stats['bytes'] + $size > self::MAX_BYTES) {
                throw new RuntimeException('Site file count or size limit exceeded.');
            }
            $this->push($fp, $state, 'F'.json_encode(['path' => $relative, 'size' => $size], JSON_THROW_ON_ERROR));
            $stream = @fopen($path, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Site file is unreadable.');
            }
            $hash = hash_init('sha256');
            $bytes = 0;
            try {
                while (! feof($stream)) {
                    $part = fread($stream, 65536);
                    if ($part === false) {
                        throw new RuntimeException('Site file read failed.');
                    }
                    if ($part === '') {
                        continue;
                    }
                    $bytes += strlen($part);
                    hash_update($hash, $part);
                    $this->push($fp, $state, 'B'.$part);
                }
            } finally {
                fclose($stream);
            }
            if ($bytes !== $size) {
                throw new RuntimeException('Site file changed during backup.');
            }
            $this->push($fp, $state, 'X'.hash_final($hash));
            $stats['bytes'] += $bytes;
            $stats['files']++;
        }
    }

    private function excluded(string $relative): bool
    {
        foreach (['storage/app/private/site-backups', 'storage/app/private/core-snapshots',
                  'storage/app/private/core-restore-staging', 'bootstrap/cache'] as $excluded) {
            if ($relative === $excluded || str_starts_with($relative, $excluded.'/')) {
                return true;
            }
        }
        return str_starts_with($relative, 'database/')
            && (str_ends_with($relative, '.sqlite') || str_contains($relative, '.sqlite-'));
    }

    private function safePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '\\')
            || preg_match('/[\x00-\x1f\x7f]/', $path)) {
            return false;
        }
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                return false;
            }
        }
        return true;
    }

    private function key(): string
    {
        $encoded = (string) config('84pad.backup_key', '');
        if ($encoded === '') {
            throw new RuntimeException('Configure PAD_BACKUP_KEY (base64-encoded 32 random bytes) and store an offline copy.');
        }
        try {
            $key = sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (Throwable) {
            throw new RuntimeException('Invalid PAD_BACKUP_KEY.');
        }
        if (strlen($key) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            throw new RuntimeException('PAD_BACKUP_KEY must represent exactly 32 random bytes.');
        }
        return $key;
    }

    private function push($fp, string &$state, string $record, bool $last = false): void
    {
        if (strlen($record) > self::MAX_RECORD) {
            throw new RuntimeException('Backup record too large.');
        }
        $cipher = sodium_crypto_secretstream_xchacha20poly1305_push(
            $state, $record, '',
            $last ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                  : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE
        );
        $this->writeAll($fp, pack('N', strlen($cipher)).$cipher);
    }

    private function writeAll($fp, string $data): void
    {
        $length = strlen($data);
        for ($written = 0; $written < $length;) {
            $n = fwrite($fp, substr($data, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('Cannot write encrypted backup.');
            }
            $written += $n;
        }
    }

    private function readExact($fp, int $length): string|false
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($fp, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                return false;
            }
            $data .= $chunk;
        }
        return $data;
    }
}
