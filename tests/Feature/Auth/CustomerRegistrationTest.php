<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_fails_validation_for_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_customer_registration_fails_validation_for_duplicate_email(): void
    {
        User::factory()->customer()->create(['email' => 'taken-customer@example.com']);

        $response = $this->postJson('/api/v1/customer/auth/register', [
            'name' => 'Repeat Customer',
            'email' => 'taken-customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(1, User::where('email', 'taken-customer@example.com')->count());
    }

    public function test_customer_registration_fails_validation_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/register', [
            'name' => 'New Customer',
            'email' => 'customer-mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'something-else',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'customer-mismatch@example.com']);
    }

    public function test_registered_customer_has_no_onboarding_step_unlike_merchant_registration(): void
    {
        // RegisterCustomerAction sets onboarding_step to null (as opposed to
        // RegisterUserAction's merchant path, which sets PENDING_VERIFICATION).
        // This is what makes IdentityContextResolver treat the account as a
        // CUSTOMER actor rather than a MERCHANT candidate.
        $response = $this->postJson('/api/v1/customer/auth/register', [
            'name' => 'Isolation Customer',
            'email' => 'isolation-customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'isolation-customer@example.com',
            'onboarding_step' => null,
        ]);
    }
}
