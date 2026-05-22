<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationDriftDetectionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorization_drift_detection_runs_in_warning_mode(): void
    {
        $this->artisan('architecture:detect-authorization-drift')
            ->expectsOutputToContain('Authorization drift detection completed in warning mode.')
            ->assertSuccessful();
    }
}
