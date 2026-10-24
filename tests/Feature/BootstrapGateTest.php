<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class BootstrapGateTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryRoot = sys_get_temp_dir().'/84pad-bootstrap-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->temporaryRoot.'/public');
        File::ensureDirectoryExists($this->temporaryRoot.'/storage/app/private');

        copy(
            base_path('public/84pad-bootstrap.php'),
            $this->temporaryRoot.'/public/84pad-bootstrap.php'
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryRoot);
        parent::tearDown();
    }

    private function visit(bool $secure): string
    {
        $process = new Process([
            PHP_BINARY,
            base_path('tests/Fixtures/BootSetupGet.php'),
            $this->temporaryRoot.'/public/84pad-bootstrap.php',
            $secure ? 'yes' : 'no',
        ]);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        return $process->getOutput();
    }

    public function test_empty_private_folder_opens_hosting_wizard_without_entering_setup_key(): void
    {
        $this->assertStringContainsString('Your space begins here', $this->visit(true));
    }

    public function test_wizard_is_only_available_over_https(): void
    {
        $this->assertStringContainsString('requires a direct HTTPS', $this->visit(false));
        $this->assertStringContainsString('Your space begins here', $this->visit(true));
    }

    public function test_existing_environment_file_disables_setup_without_overwriting(): void
    {
        file_put_contents($this->temporaryRoot.'/.env', 'APP_ENV=production'."\n");

        $this->assertStringContainsString('allow-84pad-', $this->visit(true));
        $this->assertStringNotContainsString('name="setup_token"', $this->visit(true));
        $this->assertSame(
            'APP_ENV=production'."\n",
            file_get_contents($this->temporaryRoot.'/.env')
        );
    }

    public function test_legacy_short_token_file_does_not_grant_installation(): void
    {
        file_put_contents(
            $this->temporaryRoot.'/storage/app/private/first-run-token',
            'short-key'
        );
        $this->assertSame('', $this->visit(true));
    }
}
