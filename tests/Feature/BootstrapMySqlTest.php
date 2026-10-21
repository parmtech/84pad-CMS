<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class BootstrapMySqlTest extends TestCase
{
    public function test_no_ssh_form_initialises_only_a_new_empty_ci_database(): void
    {
        $rootPassword = getenv('CI_BOOTSTRAP_ROOT_PASSWORD');
        if (getenv('GITHUB_ACTIONS') !== 'true'
            || ! is_string($rootPassword) || $rootPassword === ''
            || ! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('Requires temporary MySQL CI service.');
        }

        $dbName = 'pad_bootstrap_ci_'.bin2hex(random_bytes(6));
        $token = str_repeat('R', 48);
        $username = 'padtester';
        $userPassword = 'test_only_password';
        $temporaryRoot = sys_get_temp_dir().'/84pad-new-install-'.bin2hex(random_bytes(8));

        $pdo = new PDO(
            'mysql:host=127.0.0.1;port=3306;charset=utf8mb4',
            'root',
            $rootPassword,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $quotedDatabase = chr(96).$dbName.chr(96);
        $pdo->exec('CREATE DATABASE '.$quotedDatabase.' CHARACTER SET utf8mb4');
        try {
            $pdo->exec("GRANT ALL PRIVILEGES ON ".$quotedDatabase.".* TO 'padtester'@'%'");
            File::ensureDirectoryExists($temporaryRoot.'/public');
            File::ensureDirectoryExists($temporaryRoot.'/storage/app/private');
            copy(
                base_path('public/84pad-bootstrap.php'),
                $temporaryRoot.'/public/84pad-bootstrap.php'
            );
            file_put_contents(
                $temporaryRoot.'/storage/app/private/first-run-token',
                $token
            );

            $env = [
                'CI_BOOTSTRAP_DB_HOST' => '127.0.0.1',
                'CI_BOOTSTRAP_DB_NAME' => $dbName,
                'CI_BOOTSTRAP_DB_USER' => $username,
                'CI_BOOTSTRAP_DB_PASSWORD' => $userPassword,
                'CI_BOOTSTRAP_SETUP_TOKEN' => $token,
            ];
            $command = [
                PHP_BINARY,
                base_path('tests/Fixtures/BootSetupPost.php'),
                $temporaryRoot.'/public/84pad-bootstrap.php',
            ];

            $first = new Process($command, null, $env);
            $first->run();
            $this->assertTrue($first->isSuccessful(), $first->getErrorOutput());
            $environmentFile = $temporaryRoot.'/.env';
            $this->assertFileExists($environmentFile);
            $saved = file_get_contents($environmentFile);
            $this->assertStringContainsString('APP_KEY=base64:', $saved);
            $this->assertStringContainsString('DB_DATABASE="'.$dbName.'"', $saved);
            $this->assertStringContainsString('APP_DEBUG=false', $saved);
            $this->assertStringContainsString('PAD_SETUP_TOKEN=', $saved);

            // A second valid token must never overwrite the APP_KEY.
            $second = new Process($command, null, $env);
            $second->run();
            $this->assertTrue($second->isSuccessful(), $second->getErrorOutput());
            $this->assertSame($saved, file_get_contents($environmentFile));

            $other = new PDO(
                'mysql:host=127.0.0.1;port=3306;dbname='.$dbName.';charset=utf8mb4',
                $username,
                $userPassword,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->assertSame(false, $other->query('SHOW TABLES')->fetchColumn());
        } finally {
            $pdo->exec('DROP DATABASE IF EXISTS '.$quotedDatabase);
            File::deleteDirectory($temporaryRoot);
        }
    }
}
