<?php

namespace App\Support\Updates;

use RuntimeException;
use Throwable;
use ZipArchive;

final class UpdateInspector
{
    public function __construct(private readonly UpdateVerifier $verifier) {}

    /** @return array{version:string,requires_core:string,requires_php:string,files:int,uncompressed_bytes:int} */
    public function inspect(string $path, string $signature): array
    {
        if (! $this->verifier->verify($path, $signature)) {
            throw new RuntimeException('Update signature is invalid or not trusted.');
        }
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZIP support is unavailable.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Signed artifact is not a ZIP.');
        }

        try {
            if ($zip->numFiles < 2 || $zip->numFiles > 5000) {
                throw new RuntimeException('Unexpected number of update files.');
            }

            $total = 0;
            $seen = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (! is_array($stat)) {
                    throw new RuntimeException('Corrupt ZIP member.');
                }
                $name = (string) ($stat['name'] ?? '');
                $normalized = str_ends_with($name, '/') ? substr($name, 0, -1) : $name;
                if ($normalized === '' || strlen($name) > 255
                    || ! preg_match('/^[A-Za-z0-9_.\/-]+$/D', $normalized)
                    || str_starts_with($normalized, '/')
                    || str_contains($normalized, '//')
                    || isset($seen[$normalized])) {
                    throw new RuntimeException('Invalid update path.');
                }
                foreach (explode('/', $normalized) as $part) {
                    if ($part === '' || str_starts_with($part, '.')) {
                        throw new RuntimeException('Hidden or parent paths are forbidden.');
                    }
                }
                $seen[$normalized] = true;
                $total += (int) ($stat['size'] ?? 0);
                if ($total > 536870912) {
                    throw new RuntimeException('Update decompressed size limit exceeded.');
                }
                $os = 0;
                $attributes = 0;
                if ($zip->getExternalAttributesIndex($index, $os, $attributes)
                    && (($attributes >> 16) & 0170000) === 0120000) {
                    throw new RuntimeException('Update ZIP symlinks are forbidden.');
                }
            }

            $json = $zip->getFromName('update.json');
            if (! is_string($json) || strlen($json) > 16384) {
                throw new RuntimeException('Missing or oversized update manifest.');
            }
            try {
                $metadata = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                throw new RuntimeException('Invalid update manifest JSON.');
            }

            if (! is_array($metadata)
                || ! is_string($metadata['version'] ?? null)
                || ! is_string($metadata['requires_core'] ?? null)
                || ! is_string($metadata['requires_php'] ?? null)) {
                throw new RuntimeException('Invalid update manifest.');
            }

            foreach (['version', 'requires_core', 'requires_php'] as $field) {
                if (! preg_match('/^\d+\.\d+\.\d+$/D', $metadata[$field])) {
                    throw new RuntimeException('Invalid update semantic version.');
                }
            }
            $current = (string) config('84pad.core_version', '0.1.0');
            if (version_compare($metadata['version'], $current, '<=')
                || version_compare($current, $metadata['requires_core'], '<')
                || version_compare(PHP_VERSION, $metadata['requires_php'], '<')) {
                throw new RuntimeException('Release is not compatible with this Core/PHP version.');
            }

            return [
                'version' => $metadata['version'],
                'requires_core' => $metadata['requires_core'],
                'requires_php' => $metadata['requires_php'],
                'files' => $zip->numFiles,
                'uncompressed_bytes' => $total,
            ];
        } finally {
            $zip->close();
        }
    }
}
