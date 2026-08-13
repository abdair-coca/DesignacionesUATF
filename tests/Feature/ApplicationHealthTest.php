<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationHealthTest extends TestCase
{
    public function test_health_endpoint_is_public_and_reports_application_status(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonMissingPath('checks.jachasun');
    }

    public function test_health_command_reports_success_without_exposing_secrets(): void
    {
        $this->artisan('app:health')
            ->expectsOutputToContain('Estado: ok')
            ->expectsOutputToContain('database: ok')
            ->assertExitCode(0);
    }

    public function test_health_command_returns_failure_when_app_key_is_missing(): void
    {
        config(['app.key' => null]);

        $this->artisan('app:health')
            ->expectsOutputToContain('Estado: degraded')
            ->expectsOutputToContain('app_key: failed')
            ->assertExitCode(1);
    }
}
