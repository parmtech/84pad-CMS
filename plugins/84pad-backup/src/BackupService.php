<?php
declare(strict_types=1);
namespace Pad\Backup;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use ZipArchive;

final class BackupService
{
    private const MAGIC = '84PADBK1';
    private const CHUNK = 1048576;
    private const MAX_ENTRIES = 100000;
    private const MAX_BYTES = 8589934592;
    private const LIFE = 60 * 86400;

    public function directory(): string
    {
        $dir = storage_path('app/private/84pad-backups');
        if (is_link($dir)) throw new RuntimeException('Unsafe backup storage.');
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Backup storage unavailable.');
        @chmod($dir, 0700);
        return $dir;
    }

    public function settings(): array
    {
        $p = $this->directory().'/settings.json';
        $s = is_file($p) && !is_link($p) ? json_decode((string)file_get_contents($p), true) : null;
        return [
            'frequency' => in_array($s['frequency'] ?? null, ['off','hourly','daily','weekly','monthly'], true) ? $s['frequency'] : 'daily',
            'last_success' => is_int($s['last_success'] ?? null) ? $s['last_success'] : null,
            'retention_days' => 60,
        ];
    }

    public function configure(string $frequency): void
    {
        if (!in_array($frequency, ['off','hourly','daily','weekly','monthly'], true)) throw new RuntimeException('Invalid frequency.');
        $this->withLock(function () use ($frequency): void {
            $s = $this->settings();
            $s['frequency'] = $frequency;
            $this->writeJson($this->directory().'/settings.json', $s);
        });
    }

    public function tick(): void
    {
        $this->withLock(function (): void {
            $this->prune();
            $s = $this->settings();
            if ($s['frequency'] === 'off') return;
            $last = $s['last_success'];
            $interval = match ($s['frequency']) {
                'hourly' => 3600, 'daily' => 86400, 'weekly' => 604800, 'monthly' => null, default => null
            };
            $due = $last === null || ($interval !== null
                ? time() >= $last + $interval
                : time() >= (new \DateTimeImmutable('@'.$last))->setTimezone(new \DateTimeZone('UTC'))->modify('first day of next month midnight')->getTimestamp());
            if ($due) $this->createInsideLock('scheduled');
        }, false);
    }

    public function backup(): array
    {
        return $this->withLock(fn (): array => $this->createInsideLock('manual'));
    }

    public function list(): array
    {
        $items = [];
        foreach (glob($this->directory().'/b*.json') ?: [] as $p) {
            if (is_link($p) || filesize($p) > 8192) continue;
            $meta = json_decode((string)file_get_contents($p), true);
            if (!is_array($meta) || !$this->validId($meta['id'] ?? null)
                || basename($p) !== $meta['id'].'.json'
                || !is_file($this->directory().'/'.$meta['id'].'.84bk')) continue;
            $items[] = $meta;
        }
        usort($items, static fn ($a,$b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        return $items;
    }

    public function verify(string $id): bool
    {
        $src = $this->location($id);
        $tmp = $this->directory().'/verify-'.bin2hex(random_bytes(8)).'.zip';
        try {
            $m = json_decode((string)file_get_contents($this->directory().'/'.$id.'.json'), true);
            if (!is_array($m) || !hash_equals((string)($m['sha256'] ?? ''), (string)hash_file('sha256',$src))) return false;
            $this->decrypt($src,$tmp);
            $this->verifyZip($tmp);
            return true;
        } catch (\Throwable $e) {
            return false;
        } finally {
            @unlink($tmp);
        }
    }

    public function portableExport(string $id): string
    {
        $src = $this->location($id);
        $tmp = $this->directory().'/export-'.bin2hex(random_bytes(8)).'.zip';
        try {
            $this->decrypt($src, $tmp);
            $this->verifyZip($tmp);
            return $tmp;
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }
    }

    public function portableImport(UploadedFile $upload): array
    {
        $src = $upload->getRealPath();
        if (!$upload->isValid() || !is_string($src) || ($upload->getSize() ?? 0) > self::MAX_BYTES)
            throw new RuntimeException('Invalid imported ZIP.');
        $this->verifyZip($src);
        return $this->withLock(function () use ($src): array {
            $id = $this->id();
            $target = $this->directory().'/'.$id.'.84bk';
            try {
                $this->encrypt($src,$target);
                return $this->metadata($id,'imported',0);
            } catch (\Throwable $e) {
                @unlink($target);
                throw $e;
            }
        });
    }

    public function delete(string $id): void
    {
        $this->withLock(function () use ($id): void {
            $p = $this->location($id);
            if (!@unlink($p)) throw new RuntimeException('Backup deletion failed.');
            @unlink($this->directory().'/'.$id.'.json');
        });
    }

    private function createInsideLock(string $reason): array
    {
        $id = $this->id();
        $zipPath = $this->directory().'/'.$id.'.partial.zip';
        $sqlPath = $this->directory().'/'.$id.'.partial.sql';
        $out = $this->directory().'/'.$id.'.84bk';
        try {
            $this->databaseDump($sqlPath);
            $files = $this->siteFiles();
            $files['database.sql'] = $sqlPath;
            ksort($files, SORT_STRING);
            $manifestFiles = [];
            $total = 0;
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true)
                throw new RuntimeException('Cannot create site archive.');
            try {
                foreach ($files as $rel => $abs) {
                    $size = filesize($abs);
                    if ($size === false || $size < 0) throw new RuntimeException('Source file unreadable.');
                    $total += $size;
                    if ($total > self::MAX_BYTES || count($manifestFiles) >= self::MAX_ENTRIES)
                        throw new RuntimeException('Backup size/entry safety limit exceeded.');
                    $hash = hash_file('sha256',$abs);
                    if (!is_string($hash) || !$zip->addFile($abs,'site/'.$rel))
                        throw new RuntimeException('Cannot archive file.');
                    $manifestFiles['site/'.$rel] = ['size'=>$size, 'sha256'=>$hash];
                }
                $manifest = [
                    'format'=>'84pad-full-backup-v1',
                    'created_utc'=>gmdate('c'),
                    'core_version'=>(string)config('84pad.core_version', 'unknown'),
                    'database_scope'=>'mysql-base-tables',
                    'files'=>$manifestFiles,
                ];
                if (!$zip->addFromString('manifest.json',json_encode($manifest, JSON_THROW_ON_ERROR)))
                    throw new RuntimeException('Manifest creation failed.');
            } finally { $zip->close(); }
            $this->verifyZip($zipPath);
            $this->encrypt($zipPath,$out);
            $data = $this->metadata($id,$reason,count($manifestFiles));
            if ($reason === 'scheduled') {
                $s = $this->settings();
                $s['last_success'] = time();
                $this->writeJson($this->directory().'/settings.json',$s);
            }
            return $data;
        } catch (\Throwable $e) {
            @unlink($out);
            throw $e;
        } finally {
            @unlink($zipPath);
            @unlink($sqlPath);
        }
    }

    private function siteFiles(): array
    {
        $root = base_path();
        $files = [];
        foreach (['.env','artisan','composer.json','composer.lock'] as $file) {
            if (is_file($root.'/'.$file) && !is_link($root.'/'.$file)) $files[$file] = $root.'/'.$file;
        }
        foreach (['app','bootstrap','config','database','plugins','public','resources','routes','vendor','storage/app/public'] as $dir) {
            $absolute = $root.'/'.$dir;
            if (!is_dir($absolute) || is_link($absolute)) continue;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $entry) {
                if (!$entry->isFile() || $entry->isLink()) continue;
                $rel = str_replace('\\','/',substr($entry->getPathname(),strlen($root)+1));
                $segments = explode('/',$rel);
                if (array_intersect($segments,['.git','node_modules','cache','logs','sessions','84pad-backups']) !== []) continue;
                $files[$rel] = $entry->getPathname();
            }
        }
        return $files;
    }

    private function databaseDump(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql')
            throw new RuntimeException('Backup currently supports MySQL/MariaDB.');
        $handle = fopen($path,'xb');
        if (!$handle) throw new RuntimeException('Cannot write SQL dump.');
        @chmod($path,0600);
        try {
            fwrite($handle,"-- 84PAD full-table snapshot\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
            try {
                $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
                foreach ($tables as $row) {
                    $name = chr(96).str_replace(chr(96),chr(96).chr(96),(string)$row[0]).chr(96);
                    $create = $pdo->query('SHOW CREATE TABLE '.$name)->fetch(PDO::FETCH_NUM);
                    if (!is_string($create[1] ?? null)) throw new RuntimeException('Table definition missing.');
                    fwrite($handle,"\nDROP TABLE IF EXISTS ".$name.";\n".$create[1].";\n");
                    $query = $pdo->query('SELECT * FROM '.$name);
                    while (($values = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
                        $columns = array_map(static fn ($k) => chr(96).str_replace(chr(96),chr(96).chr(96),$k).chr(96),array_keys($values));
                        $escaped = [];
                        foreach ($values as $value) {
                            $q = $value === null ? 'NULL' : $pdo->quote((string)$value);
                            if ($q === false) throw new RuntimeException('Unable to encode SQL cell.');
                            $escaped[] = $q;
                        }
                        fwrite($handle,'INSERT INTO '.$name.' ('.implode(',',$columns).') VALUES ('.implode(',',$escaped).");\n");
                        if (ftell($handle) > self::MAX_BYTES) throw new RuntimeException('Database is too large.');
                    }
                    $query->closeCursor();
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            fwrite($handle,"SET FOREIGN_KEY_CHECKS=1;\n");
        } finally { fclose($handle); }
    }

    private function verifyZip(string $path): void
    {
        if (!class_exists(ZipArchive::class) || !is_file($path) || is_link($path))
            throw new RuntimeException('Invalid ZIP.');
        $zip = new ZipArchive();
        if ($zip->open($path,ZipArchive::RDONLY) !== true) throw new RuntimeException('Cannot open ZIP.');
        try {
            $raw = $zip->getFromName('manifest.json');
            if (!is_string($raw) || strlen($raw)>24000000) throw new RuntimeException('Missing manifest.');
            $m = json_decode($raw,true,32);
            if (!is_array($m) || ($m['format'] ?? null) !== '84pad-full-backup-v1'
                || !is_array($m['files'] ?? null) || !isset($m['files']['site/database.sql'])
                || count($m['files']) > self::MAX_ENTRIES
                || $zip->numFiles !== count($m['files'])+1) throw new RuntimeException('Invalid backup manifest.');
            $seen=[]; $total=0;
            for ($i=0;$i<$zip->numFiles;$i++) {
                $s = $zip->statIndex($i);
                $name = $s['name'] ?? null;
                if (!is_string($name) || isset($seen[$name]) || !is_int($s['size'] ?? null)
                    || ($name !== 'manifest.json' && !preg_match('~^site/(?:[a-zA-Z0-9_.-]+/)*[a-zA-Z0-9_.-]+$~D',$name))
                    || str_contains($name,'..') || str_contains($name,'//'))
                    throw new RuntimeException('Unsafe ZIP entry.');
                $seen[$name]=true;
                if ($name === 'manifest.json') continue;
                $entry = $m['files'][$name] ?? null;
                if (!is_array($entry) || ($entry['size'] ?? null) !== $s['size']
                    || !is_string($entry['sha256'] ?? null)
                    || !preg_match('/^[a-f0-9]{64}$/D',$entry['sha256'])) throw new RuntimeException('ZIP metadata mismatch.');
                $opsys=0; $attr=0;
                if ($zip->getExternalAttributesIndex($i,$opsys,$attr)
                    && (($attr>>16)&0170000)===0120000) throw new RuntimeException('Archive symlinks forbidden.');
                $total += $s['size'];
                if ($total > self::MAX_BYTES) throw new RuntimeException('Archive too large.');
                $stream = $zip->getStream($name);
                if ($stream === false) throw new RuntimeException('Corrupt file stream.');
                $hash=hash_init('sha256'); hash_update_stream($hash,$stream); fclose($stream);
                if (!hash_equals($entry['sha256'],hash_final($hash))) throw new RuntimeException('File checksum invalid.');
            }
        } finally { $zip->close(); }
    }

    private function encrypt(string $source,string $destination): void
    {
        $in=fopen($source,'rb'); $out=fopen($destination,'xb');
        if (!$in || !$out) throw new RuntimeException('Encryption streams unavailable.');
        @chmod($destination,0600);
        try {
            $salt=random_bytes(16); [$state,$header]=sodium_crypto_secretstream_xchacha20poly1305_init_push($this->key($salt));
            fwrite($out,self::MAGIC.$salt.$header);
            do {
                $block=fread($in,self::CHUNK);
                if ($block === false) throw new RuntimeException('Read failed.');
                $final=feof($in);
                $cipher=sodium_crypto_secretstream_xchacha20poly1305_push($state,$block,'',$final ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : 0);
                fwrite($out,pack('N',strlen($cipher)).$cipher);
            } while (!$final);
        } finally { fclose($in); fclose($out); }
    }

    private function decrypt(string $source,string $destination): void
    {
        $in=fopen($source,'rb'); $out=fopen($destination,'xb');
        if (!$in || !$out) throw new RuntimeException('Decryption streams unavailable.');
        @chmod($destination,0600);
        try {
            if (fread($in,8)!==self::MAGIC) throw new RuntimeException('Invalid encrypted backup.');
            $salt=fread($in,16); $header=fread($in,SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            if (strlen($salt)!==16 || strlen($header)!==SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES)
                throw new RuntimeException('Truncated encryption header.');
            $state=sodium_crypto_secretstream_xchacha20poly1305_init_pull($header,$this->key($salt));
            $done=false; $total=0;
            while (!feof($in)) {
                $len=fread($in,4);
                if ($len === '') break;
                if (strlen($len)!==4) throw new RuntimeException('Truncated frame.');
                $size=unpack('N',$len)[1];
                if ($size<17 || $size>self::CHUNK+17) throw new RuntimeException('Bad frame size.');
                $cipher='';
                while (strlen($cipher)<$size) {
                    $part=fread($in,$size-strlen($cipher));
                    if (!is_string($part) || $part==='') throw new RuntimeException('Incomplete frame.');
                    $cipher.=$part;
                }
                $result=sodium_crypto_secretstream_xchacha20poly1305_pull($state,$cipher);
                if ($result === false) throw new RuntimeException('Backup authentication failed.');
                [$plain,$tag]=$result;
                $total+=strlen($plain);
                if ($total>self::MAX_BYTES) throw new RuntimeException('Backup size exceeded.');
                fwrite($out,$plain);
                if ($tag===SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                    $done=true;
                    if (fread($in,1)!=='') throw new RuntimeException('Data after final frame.');
                    break;
                }
            }
            if (!$done) throw new RuntimeException('Missing final authentication frame.');
        } finally { fclose($in); fclose($out); }
    }

    private function key(string $salt): string
    {
        if (!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push'))
            throw new RuntimeException('Libsodium extension required.');
        $appKey=(string)config('app.key','');
        if (str_starts_with($appKey,'base64:')) {
            $decoded=base64_decode(substr($appKey,7),true);
            $appKey=$decoded===false ? '' : $decoded;
        }
        if (strlen($appKey)<32) throw new RuntimeException('APP_KEY not configured.');
        return hash_hkdf('sha256',$appKey,SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES,'84pad-backup-v1',$salt);
    }

    private function metadata(string $id,string $reason,int $count): array
    {
        $path=$this->directory().'/'.$id.'.84bk';
        $data=['id'=>$id,'created_at'=>time(),'reason'=>$reason,'files'=>$count,
            'bytes'=>filesize($path),'sha256'=>hash_file('sha256',$path),'status'=>'verified'];
        $this->writeJson($this->directory().'/'.$id.'.json',$data);
        return $data;
    }

    private function prune(): void
    {
        foreach ($this->list() as $item) {
            if (is_int($item['created_at'] ?? null) && $item['created_at'] < time()-self::LIFE) {
                @unlink($this->directory().'/'.$item['id'].'.84bk');
                @unlink($this->directory().'/'.$item['id'].'.json');
            }
        }
    }

    private function location(string $id): string
    {
        if (!$this->validId($id) || !is_file($this->directory().'/'.$id.'.json')
            || !is_file($this->directory().'/'.$id.'.84bk')
            || is_link($this->directory().'/'.$id.'.84bk')) throw new RuntimeException('Backup not found.');
        return $this->directory().'/'.$id.'.84bk';
    }

    private function id(): string { return 'b'.gmdate('YmdHis').'-'.bin2hex(random_bytes(8)); }
    private function validId(mixed $id): bool { return is_string($id) && (bool)preg_match('/^b[0-9]{14}-[a-f0-9]{16}$/D',$id); }

    private function writeJson(string $path,array $data): void
    {
        $tmp=$path.'.'.bin2hex(random_bytes(6)).'.tmp';
        if (file_put_contents($tmp,json_encode($data,JSON_THROW_ON_ERROR),LOCK_EX)===false)
            throw new RuntimeException('Could not write metadata.');
        @chmod($tmp,0600);
        if (!rename($tmp,$path)) {
            @unlink($tmp); throw new RuntimeException('Cannot publish metadata.');
        }
    }

    private function withLock(callable $fn,bool $wait=true): mixed
    {
        $h=fopen($this->directory().'/.lock','c+');
        if (!$h) throw new RuntimeException('Cannot open backup lock.');
        try {
            if (!flock($h,LOCK_EX|($wait?0:LOCK_NB))) return null;
            try { return $fn(); } finally { flock($h,LOCK_UN); }
        } finally { fclose($h); }
    }
}
