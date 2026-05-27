<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_advances_onboarding_step(): void
    {
        /** @var User $user */
        $user = User::factory()->pendingVerification()->create();
        
        $verificationUrl = URL::temporarySignedRoute(
            'merchant.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200);
        
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertEquals(OnboardingStepEnum::CREATE_STORE, $user->onboarding_step);
    }
}
