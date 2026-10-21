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

    public function test_missing_private_token_disables_bootstrap(): void
    {
        $this->assertSame('', $this->visit(true));
    }

    public function test_valid_token_only_allows_https(): void
    {
        file_put_contents(
            $this->temporaryRoot.'/storage/app/private/first-run-token',
            str_repeat('Q', 48)
        );
        $this->assertStringContainsString('requires a direct HTTPS', $this->visit(false));
        $this->assertStringContainsString('Your space begins here', $this->visit(true));
    }

    public function test_existing_environment_file_disables_setup_without_overwriting(): void
    {
        file_put_contents(
            $this->temporaryRoot.'/storage/app/private/first-run-token',
            str_repeat('Q', 48)
        );
        file_put_contents($this->temporaryRoot.'/.env', 'APP_ENV=production'."\n");

        $this->assertSame('', $this->visit(true));
        $this->assertSame(
            'APP_ENV=production'."\n",
            file_get_contents($this->temporaryRoot.'/.env')
        );
    }

    public function test_weak_token_does_not_unlock_setup(): void
    {
        file_put_contents(
            $this->temporaryRoot.'/storage/app/private/first-run-token',
            'short-key'
        );
        $this->assertSame('', $this->visit(true));
    }
}
