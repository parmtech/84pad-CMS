<?php

namespace App\Support\Plugins;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

/**
 * A two-step, owner-bound workflow for signed .pad/ZIP packages.
 * Package name and version are read from the signed root plugin.json,
 * never trusted from the browser filename or confirmation form.
 */
final class PluginUpdateManager
{
    public function __construct(
        private readonly PluginPackageInstaller $installer,
        private readonly PluginCatalog $catalog
    ) {}

    /** @return array<string,string> */
    public function prepare(UploadedFile $upload, string $signature, string $ownerId): array
    {
        $manifest = $this->installer->inspect($upload, $signature);
        $this->assertCandidate($manifest);
        $root = $this->privateDirectory('plugin-review');
        $token = bin2hex(random_bytes(24));
        $path = $root.'/'.$token.'.pad';
        $metadata = $root.'/'.$token.'.json';
        $file = @fopen($path, 'x+b');
        if ($file === false) {
            throw new RuntimeException('Could not stage the signed plugin package.');
        }
        try {
            $source = @fopen($upload->getRealPath(), 'rb');
            if ($source === false) {
                throw new RuntimeException('Uploaded plugin could not be read.');
            }
            try {
                if (stream_copy_to_stream($source, $file) !== filesize($upload->getRealPath())) {
                    throw new RuntimeException('Plugin upload was incomplete.');
                }
            } finally {
                fclose($source);
            }
        } catch (Throwable $e) {
            fclose($file);
            @unlink($path);
            throw $e;
        }
        fclose($file);
        @chmod($path, 0600);
        try {
            if (! hash_equals((string) hash_file('sha256', $upload->getRealPath()), (string) hash_file('sha256', $path))) {
                throw new RuntimeException('Staged plugin archive failed checksum verification.');
            }
            $record = [
                'owner' => $ownerId,
                'created' => time(),
                'signature' => $signature,
                'sha256' => hash_file('sha256', $path),
                'id' => $manifest['id'],
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'requires_core' => $manifest['requires_core'],
            ];
            $handle = @fopen($metadata, 'xb');
            if ($handle === false) {
                throw new RuntimeException('Unable to stage plugin review metadata.');
            }
            try {
                if (fwrite($handle, json_encode($record, JSON_THROW_ON_ERROR)) === false) {
                    throw new RuntimeException('Unable to write review metadata.');
                }
            } finally {
                fclose($handle);
            }
            @chmod($metadata, 0600);
            return $this->review($token, $ownerId);
        } catch (Throwable $e) {
            @unlink($metadata);
            @unlink($path);
            throw $e;
        }
    }

    /** @return array<string,string> */
    public function review(string $token, string $ownerId): array
    {
        $metadata = $this->readRecord($token, $ownerId);
        $manifest = $this->installer->inspect(
            $this->uploadFor($token), $metadata['signature']
        );
        if (! hash_equals($metadata['sha256'], (string) hash_file('sha256', $this->stagedPath($token)))
            || $manifest['id'] !== $metadata['id']
            || $manifest['version'] !== $metadata['version']) {
            throw new RuntimeException('Staged plugin file changed. Upload it again.');
        }
        $existing = $this->assertCandidate($manifest);
        return [
            'ticket' => $token,
            'id' => $manifest['id'],
            'name' => $manifest['name'],
            'version' => $manifest['version'],
            'requires_core' => $manifest['requires_core'],
            'previous_version' => $existing ?? '',
            'action' => $existing === null ? 'install' : 'update',
            'filename' => $manifest['id'].'-v'.$manifest['version'].'.pad',
        ];
    }

    /** @return array{slug:string,version:string,filename:string,action:string} */
    public function apply(
        string $token,
        string $ownerId,
        string $confirmedSlug,
        string $confirmedVersion
    ): array {
        // One lock spans the final re-check, filesystem swap and DB update.
        $root = $this->privateDirectory('plugin-review');
        $lock = @fopen($root.'/install.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('Another plugin installation is running.');
        }
        try {
            $details = $this->review($token, $ownerId);
            if (! hash_equals($details['id'], $confirmedSlug)
                || ! hash_equals($details['version'], $confirmedVersion)) {
                throw new RuntimeException('Confirm the exact detected plugin ID and version.');
            }
            $archiveRoot = $this->privateDirectory('plugin-archives');
            $filename = $details['filename'];
            $archive = $archiveRoot.'/'.$filename;
            $bytes = $this->stagedPath($token);
            if (file_exists($archive) || is_link($archive)) {
                if (! is_file($archive) || ! hash_equals(
                    (string) hash_file('sha256', $archive),
                    (string) hash_file('sha256', $bytes)
                )) {
                    throw new RuntimeException('Another package already uses this plugin name/version.');
                }
            } else {
                $file = @fopen($archive, 'xb');
                if ($file === false) {
                    throw new RuntimeException('Could not preserve versioned plugin archive.');
                }
                try {
                    $source = fopen($bytes, 'rb');
                    try {
                        if (stream_copy_to_stream($source, $file) !== filesize($bytes)) {
                            throw new RuntimeException('Unable to preserve the entire archive.');
                        }
                    } finally {
                        fclose($source);
                    }
                } finally {
                    fclose($file);
                }
                @chmod($archive, 0600);
            }

            $slug = $details['id'];
            $source = base_path('plugins/'.$slug);
            $snapshot = null;
            if ($details['action'] === 'update') {
                // Only ZIP-installed plugins can be replaced. Bundled plugins stay protected.
                $vault = $this->privateDirectory('plugin-quarantine');
                $snapshot = $vault.'/'.$slug.'-'.date('YmdHis').'-'.bin2hex(random_bytes(8));
                DB::table('core_plugins')->updateOrInsert(
                    ['slug' => $slug],
                    ['version' => $details['previous_version'], 'enabled' => false, 'updated_at' => now()]
                );
                if (! rename($source, $snapshot)) {
                    throw new RuntimeException('Could not preserve the previous plugin version.');
                }
            }
            try {
                // Re-verify the original signed binary at the last possible point.
                $this->installer->install(
                    $this->uploadFor($token),
                    $this->readRecord($token, $ownerId)['signature']
                );
                DB::table('core_plugins')->updateOrInsert(
                    ['slug' => $slug],
                    ['version' => $details['version'], 'enabled' => false, 'updated_at' => now()]
                );
            } catch (Throwable $e) {
                // If the new install fails, keep the last verified version.
                if ($snapshot !== null && is_dir($snapshot) && ! file_exists($source) && ! is_link($source)) {
                    @rename($snapshot, $source);
                }
                throw new RuntimeException('Plugin update failed; prior version remains in recovery or installed.', 0, $e);
            }
            @unlink($this->stagedPath($token));
            @unlink($root.'/'.$token.'.json');
            return [
                'slug' => $slug, 'version' => $details['version'],
                'filename' => $filename, 'action' => $details['action'],
            ];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @param array{id:string,name:string,version:string,requires_core:string} $manifest */
    private function assertCandidate(array $manifest): ?string
    {
        $slug = $manifest['id'];
        $destination = base_path('plugins/'.$slug);
        if (is_link($destination)) {
            throw new RuntimeException('Plugin directory is not safe.');
        }
        if (! file_exists($destination)) {
            return null;
        }
        if (! is_dir($destination) || is_link($destination.'/.84pad-signed-upload')
            || ! is_file($destination.'/.84pad-signed-upload')) {
            throw new RuntimeException('Bundled or unmanaged plugins cannot be replaced by uploads.');
        }
        $installed = $this->catalog->all()[$slug] ?? null;
        if ($installed === null) {
            throw new RuntimeException('Installed plugin manifest is invalid.');
        }
        if (version_compare($manifest['version'], $installed['version'], '<=')) {
            throw new RuntimeException('Upload a newer plugin version; same-version and downgrade updates are blocked.');
        }
        return $installed['version'];
    }

    private function privateDirectory(string $name): string
    {
        $base = storage_path('app/private');
        if (! is_dir($base) || is_link($base)) {
            throw new RuntimeException('Private plugin storage is unavailable.');
        }
        $path = $base.'/'.$name;
        if (is_link($path)) {
            throw new RuntimeException('Unsafe private storage location.');
        }
        File::ensureDirectoryExists($path, 0700);
        return $path;
    }

    private function stagedPath(string $token): string
    {
        if (! preg_match('/^[a-f0-9]{48}$/D', $token)) {
            throw new RuntimeException('Invalid plugin review ticket.');
        }
        return storage_path('app/private/plugin-review/'.$token.'.pad');
    }

    private function uploadFor(string $token): UploadedFile
    {
        $path = $this->stagedPath($token);
        if (! is_file($path) || is_link($path)) {
            throw new RuntimeException('Signed plugin staging file is missing.');
        }
        return new UploadedFile($path, 'reviewed-plugin.pad', 'application/zip', null, true);
    }

    /** @return array{owner:string,created:int,signature:string,sha256:string,id:string,name:string,version:string,requires_core:string} */
    private function readRecord(string $token, string $ownerId): array
    {
        $path = $this->stagedPath($token);
        $meta = substr($path, 0, -4).'.json';
        if (is_link($meta) || ! is_file($meta) || filesize($meta) > 8192) {
            throw new RuntimeException('Plugin review ticket has expired or is missing.');
        }
        try {
            $data = json_decode((string) file_get_contents($meta), true, 8, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('Invalid plugin review metadata.', 0, $e);
        }
        if (! is_array($data)
            || ($data['owner'] ?? null) !== $ownerId
            || ! is_int($data['created'] ?? null)
            || $data['created'] < time() - 3600 || $data['created'] > time() + 30
            || ! is_string($data['signature'] ?? null)
            || ! is_string($data['sha256'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}$/D', $data['sha256'])
            || ! is_string($data['id'] ?? null)
            || ! is_string($data['version'] ?? null)) {
            throw new RuntimeException('Plugin review ticket is invalid or expired.');
        }
        return $data;
    }
}
