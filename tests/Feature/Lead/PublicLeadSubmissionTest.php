<?php

declare(strict_types=1);

namespace Tests\Feature\Lead;

use App\Events\Lead\LeadSubmitted;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PublicLeadSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_contact_submission_stores_lead_and_dispatches_event(): void
    {
        Event::fake([LeadSubmitted::class]);

        $response = $this->withHeader('User-Agent', 'LeadTestAgent/1.0')
            ->postJson(route('public.leads.contact'), $this->validPayload());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => __('lead.submitted'),
                'data' => null,
            ]);

        $this->assertDatabaseHas('leads', [
            'type' => 'contact',
            'status' => 'new',
            'email' => 'jane@example.com',
            'source_page' => '/contact',
            'user_agent' => 'LeadTestAgent/1.0',
        ]);

        /** @var Lead $lead */
        $lead = Lead::query()->firstOrFail();

        $this->assertSame([
            'utm_source' => 'google',
            'utm_campaign' => 'spring-launch',
            'gclid' => 'gclid-123',
            'landing_page' => 'https://example.com/contact',
        ], $lead->metadata);

        Event::assertDispatched(LeadSubmitted::class);
    }

    public function test_validation_failure_returns_project_error_structure(): void
    {
        $response = $this->postJson(route('public.leads.contact'), []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => __('error.validation_failed'),
                'code' => 'VAL_001',
            ])
            ->assertJsonStructure([
                'errors' => ['name', 'email', 'message'],
            ]);
    }

    public function test_honeypot_submission_is_rejected_and_not_stored(): void
    {
        $payload = $this->validPayload();
        $payload['website'] = 'https://spam.example.com';

        $response = $this->postJson(route('public.leads.contact'), $payload);

        $response->assertStatus(422)
            ->assertJsonPath('errors.website.0', 'The selected website is invalid.');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_rate_limiting_blocks_requests_after_configured_threshold(): void
    {
        Config::set('lead.spam.throttle_max_attempts', 1);
        Config::set('lead.spam.throttle_decay_minutes', 1);

        $this->postJson(route('public.leads.contact'), $this->validPayload(message: 'First message'))
            ->assertStatus(201);

        $response = $this->postJson(route('public.leads.contact'), $this->validPayload(
            email: 'other@example.com',
            message: 'Second message'
        ));

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'code' => 'AUTH_008',
            ]);
    }

    public function test_duplicate_detection_blocks_same_submission_within_window(): void
    {
        Config::set('lead.spam.duplicate_window_minutes', 30);

        $this->postJson('/api/v1/leads/contact', $this->validPayload())
            ->assertStatus(201);

        $response = $this->postJson('/api/v1/leads/contact', $this->validPayload());

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => __('error.validation_failed'),
                'error_code' => 'VAL_001',
            ])
            ->assertJsonPath('errors.message.0', __('lead.duplicate_submission'));

        $this->assertDatabaseCount('leads', 1);
    }

    private function validPayload(
        string $email = 'jane@example.com',
        string $message = 'Need a demo for our team.',
    ): array {
        return [
            'source_page' => '/contact',
            'locale' => 'en',
            'name' => 'Jane Doe',
            'email' => $email,
            'company' => 'Acme Inc',
            'phone' => '+123456789',
            'message' => $message,
            'website' => '',
            'metadata' => [
                'utm_source' => 'google',
                'utm_campaign' => 'spring-launch',
                'gclid' => 'gclid-123',
                'landing_page' => 'https://example.com/contact',
            ],
        ];
    }
}
