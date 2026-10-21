<?php

namespace App\Support\Plugins;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class PluginPackageInstaller
{
    public function __construct(private readonly PluginCatalog $catalog) {}

    public function install(UploadedFile $upload, string $signatureBase64): string
    {
        $path = $upload->getRealPath();
        if (! $upload->isValid() || $path === false || ! is_file($path)
            || filesize($path) === false || filesize($path) > (int) config('84pad.plugin_zip_max_bytes', 134217728)
            || ! $this->verifyPublisherSignature($path, $signatureBase64)) {
            throw new RuntimeException('Untrusted or invalid plugin archive.');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZIP support is not installed.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Invalid ZIP archive.');
        }

        $stage = null;
        try {
            $entries = $zip->numFiles;
            if ($entries < 1 || $entries > (int) config('84pad.plugin_zip_max_entries', 5000)) {
                throw new RuntimeException('Invalid ZIP entry count.');
            }

            $total = 0;
            $seen = [];
            for ($index = 0; $index < $entries; $index++) {
                $stat = $zip->statIndex($index);
                if (! is_array($stat)) {
                    throw new RuntimeException('Invalid ZIP member.');
                }
                $name = (string) ($stat['name'] ?? '');
                $isDirectory = str_ends_with($name, '/');
                $normalized = $isDirectory ? substr($name, 0, -1) : $name;
                if ($normalized === '' || strlen($name) > 255
                    || ! preg_match('/^[A-Za-z0-9_.\/-]+$/D', $normalized)
                    || str_starts_with($normalized, '/')
                    || str_contains($normalized, '//')) {
                    throw new RuntimeException('Unsafe plugin archive path.');
                }
                if (isset($seen[$normalized])) {
                    throw new RuntimeException('Duplicate ZIP member names are forbidden.');
                }
                $seen[$normalized] = true;
                foreach (explode('/', $normalized) as $segment) {
                    if (str_starts_with($segment, '.') || $segment === '') {
                        throw new RuntimeException('Unsafe plugin archive path.');
                    }
                }
                $expanded = (int) ($stat['size'] ?? -1);
                $compressed = (int) ($stat['comp_size'] ?? -1);
                if ($expanded < 0 || $compressed < 0
                    || ($expanded > 0 && $compressed === 0)
                    || ($compressed > 0 && $expanded > $compressed * 500)) {
                    throw new RuntimeException('Unsafe ZIP compression ratio.');
                }
                $total += $expanded;
                if ($total > (int) config('84pad.plugin_extracted_max_bytes', 536870912)) {
                    throw new RuntimeException('Plugin extracted content exceeds safety limit.');
                }
                $opsys = 0;
                $attributes = 0;
                if ($zip->getExternalAttributesIndex($index, $opsys, $attributes)
                    && (($attributes >> 16) & 0170000) === 0120000) {
                    throw new RuntimeException('Archive links are forbidden.');
                }
            }

            $raw = $zip->getFromName('plugin.json');
            $manifest = is_string($raw) ? json_decode($raw, true) : null;
            $slug = $manifest['id'] ?? null;
            if (! is_array($manifest)
                || ! is_string($slug)
                || ! preg_match('/^[a-z][a-z0-9-]{1,63}$/D', $slug)
                || ! is_string($manifest['name'] ?? null)
                || strlen($manifest['name']) > 100
                || ! is_string($manifest['version'] ?? null)
                || ! preg_match('/^\d+\.\d+\.\d+$/D', $manifest['version'])
                || ! is_string($manifest['requires_core'] ?? null)
                || ! preg_match('/^\d+\.\d+\.\d+$/D', $manifest['requires_core'])
                || ! $this->catalog->compatible($manifest)) {
                throw new RuntimeException('Invalid or incompatible plugin manifest.');
            }

            $root = base_path('plugins');
            if (! is_dir($root) || is_link($root) || ! is_writable($root)) {
                throw new RuntimeException('Plugin directory is unavailable.');
            }
            $destination = $root.DIRECTORY_SEPARATOR.$slug;
            if (file_exists($destination) || is_link($destination)) {
                throw new RuntimeException('The plugin already exists; automatic overwrite is disabled.');
            }

            $private = storage_path('app/private/plugin-staging');
            File::ensureDirectoryExists($private, 0700);
            $stage = $private.DIRECTORY_SEPARATOR.bin2hex(random_bytes(16));
            File::ensureDirectoryExists($stage, 0700);
            if (! $zip->extractTo($stage)) {
                throw new RuntimeException('Unable to extract verified package.');
            }
            // Never execute a bootstrap during package installation.
            if (! is_file($stage.'/plugin.json') || is_link($stage.'/plugin.json')) {
                throw new RuntimeException('Invalid extracted manifest.');
            }
            // Provenance marker is created by the trusted installer after ZIP
            // signature verification; local bundled plugins have no marker.
            if (file_put_contents($stage.'/.84pad-signed-upload', "signed-upload-v1\n") === false) {
                throw new RuntimeException('Unable to mark installed package.');
            }
            if (! rename($stage, $destination)) {
                throw new RuntimeException('Unable to install plugin package.');
            }
            $stage = null;
            return $slug;
        } finally {
            $zip->close();
            if ($stage !== null && is_dir($stage)) {
                File::deleteDirectory($stage);
            }
        }
    }

    private function verifyPublisherSignature(string $path, string $signature): bool
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $decoded = base64_decode($signature, true);
        if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }
        $digest = hash_file('sha256', $path, true);
        if ($digest === false) {
            return false;
        }

        foreach ((array) config('84pad.plugin_public_keys', []) as $encodedKey) {
            if (! is_string($encodedKey)) {
                continue;
            }
            $key = base64_decode($encodedKey, true);
            if ($key !== false && strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                && sodium_crypto_sign_verify_detached(
                    $decoded, "84PAD_PLUGIN_ZIP_V1\n".$digest, $key
                )) {
                return true;
            }
        }
        return false;
    }
}
