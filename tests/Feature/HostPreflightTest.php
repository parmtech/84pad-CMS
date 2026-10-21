<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HostPreflightTest extends TestCase
{
    public function test_doctor_passes_on_a_healthy_ci_environment(): void
    {
        $this->artisan('84pad:doctor')->assertExitCode(0);
    }

    public function test_doctor_rejects_a_missing_application_key(): void
    {
        Config::set('app.key', '');

        $this->artisan('84pad:doctor')->assertExitCode(1);
    }

    public function test_doctor_rejects_insecure_production_configuration(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.url', 'http://example.test');
        Config::set('app.debug', true);

        $this->artisan('84pad:doctor')->assertExitCode(1);
    }

    public function test_doctor_rejects_an_unsupported_database_driver(): void
    {
        Config::set('database.default', 'unknown');

        $this->artisan('84pad:doctor')->assertExitCode(1);
    }
}
