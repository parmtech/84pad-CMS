<?php

namespace Tests\Feature;

use App\Support\Installer\CpanelDatabaseBootstrap;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class BootstrapWizardTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/84pad-wizard-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->root.'/public');
        File::ensureDirectoryExists($this->root.'/app/Support/Installer');
        File::ensureDirectoryExists($this->root.'/storage/app/private');
        File::ensureDirectoryExists($this->root.'/bootstrap/cache');
        File::ensureDirectoryExists($this->root.'/vendor');
        file_put_contents($this->root.'/vendor/autoload.php', '<?php // fixture');
        copy(base_path('public/84pad-bootstrap.php'), $this->root.'/public/84pad-bootstrap.php');
        copy(
            base_path('app/Support/Installer/CpanelDatabaseBootstrap.php'),
            $this->root.'/app/Support/Installer/CpanelDatabaseBootstrap.php'
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_wizard_shows_both_modes_and_credential_instructions(): void
    {
        $process = new Process([
            PHP_BINARY,
            base_path('tests/Fixtures/BootSetupGet.php'),
            $this->root.'/public/84pad-bootstrap.php',
            'yes',
        ]);
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $output = $process->getOutput();
        $this->assertStringContainsString('Your space begins here', $output);
        $this->assertStringContainsString('<html lang="en">', $output);
        $this->assertStringContainsString('/css/84pad-installer.css', $output);
        $this->assertStringContainsString('data-host-ready="', $output);
        $this->assertStringContainsString('Setup companion', $output);
        $this->assertStringContainsString('Hosting readiness', $output);
        $this->assertStringContainsString('Composer dependencies', $output);
        $this->assertStringContainsString('name="connection_mode" value="cpanel"', $output);
        $this->assertStringContainsString('name="connection_mode" value="manual"', $output);
        $this->assertStringContainsString('Manage API Tokens', $output);
        $this->assertStringContainsString('allow-84pad-', $output);
        $this->assertStringNotContainsString('name="setup_token"', $output);
        $this->assertStringContainsString('/js/84pad-bootstrap-wizard.js', $output);
        $this->assertStringNotContainsString('PAD_SETUP_TOKEN=', $output);
        $this->assertFileDoesNotExist($this->root.'/.env');
    }

    public function test_missing_runtime_dependencies_block_setup_without_writing_secrets(): void
    {
        File::delete($this->root.'/vendor/autoload.php');
        $process = new Process([
            PHP_BINARY,
            base_path('tests/Fixtures/BootCpanelRejectedPost.php'),
            $this->root.'/public/84pad-bootstrap.php',
        ]);
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertStringContainsString('Hosting requirements failed', $process->getOutput());
        $this->assertFileDoesNotExist($this->root.'/.env');
    }

    public function test_cpanel_host_cannot_target_private_or_loopback_addresses(): void
    {
        $this->expectException(RuntimeException::class);
        CpanelDatabaseBootstrap::provision([
            'cpanel_host' => '127.0.0.1',
            'cpanel_username' => 'account',
            'cpanel_token' => str_repeat('A', 36),
            'db_suffix' => 'padcore',
            'db_user_suffix' => 'paduser',
            'db_password' => str_repeat('B', 24),
            'db_host' => 'localhost',
        ]);
    }

    public function test_invalid_cpanel_setup_does_not_write_environment(): void
    {
        $process = new Process([
            PHP_BINARY,
            base_path('tests/Fixtures/BootCpanelRejectedPost.php'),
            $this->root.'/public/84pad-bootstrap.php',
        ]);
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertStringContainsString('cPanel', $process->getOutput());
        $this->assertFileDoesNotExist($this->root.'/.env');
        $this->assertStringNotContainsString(str_repeat('A', 48), $process->getOutput());
    }
}
