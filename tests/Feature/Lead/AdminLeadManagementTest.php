<?php

declare(strict_types=1);

namespace Tests\Feature\Lead;

use App\Enums\Lead\LeadStatusEnum;
use App\Enums\Lead\LeadTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_endpoints_require_authentication(): void
    {
        $this->getJson(route('platform.leads.index'))
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'code' => 'AUTH_002',
            ]);
    }

    public function test_admin_endpoints_require_super_admin_role(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('platform.leads.index'))
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'IDENTITY_DOMAIN_MISMATCH',
            ]);
    }

    public function test_super_admin_can_list_and_filter_leads(): void
    {
        $admin = $this->makeSuperAdmin();

        Lead::query()->create([
            'type' => LeadTypeEnum::CONTACT->value,
            'status' => LeadStatusEnum::NEW->value,
            'locale' => 'en',
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'message' => 'Hello',
        ]);

        Lead::query()->create([
            'type' => LeadTypeEnum::CONTACT->value,
            'status' => LeadStatusEnum::SPAM->value,
            'locale' => 'en',
            'name' => 'Spammer',
            'email' => 'spam@example.com',
            'message' => 'Spam',
        ]);

        Lead::query()->create([
            'type' => LeadTypeEnum::DEMO->value,
            'status' => LeadStatusEnum::NEW->value,
            'locale' => 'en',
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'message' => 'Need a demo',
        ]);

        $this->actingAs($admin)
            ->getJson(route('platform.leads.index', ['type' => 'contact']))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin)
            ->getJson(route('platform.leads.index', ['status' => 'spam']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', LeadStatusEnum::SPAM->value);

        $this->actingAs($admin)
            ->getJson(route('platform.leads.index', ['email' => 'jane']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'jane@example.com');
    }

    public function test_super_admin_can_update_status_and_resolution_fields(): void
    {
        $admin = $this->makeSuperAdmin();

        /** @var Lead $lead */
        $lead = Lead::query()->create([
            'type' => LeadTypeEnum::CONTACT->value,
            'status' => LeadStatusEnum::NEW->value,
            'locale' => 'en',
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'message' => 'Hello',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('platform.leads.status', ['lead' => $lead->id]), [
                'status' => LeadStatusEnum::CONTACTED->value,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', LeadStatusEnum::CONTACTED->value)
            ->assertJsonPath('data.resolved_by.id', $admin->id);

        $lead->refresh();
        $resolvedAt = $lead->resolved_at;

        $this->assertNotNull($resolvedAt);
        $this->assertSame($admin->id, $lead->resolved_by);
        $this->assertNotNull($lead->contacted_at);

        $this->actingAs($admin)
            ->patchJson(route('platform.leads.status', ['lead' => $lead->id]), [
                'status' => LeadStatusEnum::ARCHIVED->value,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', LeadStatusEnum::ARCHIVED->value);

        $lead->refresh();
        $this->assertNotNull($lead->archived_at);
        $this->assertSame($resolvedAt?->toISOString(), $lead->resolved_at?->toISOString());

        $this->actingAs($admin)
            ->patchJson(route('platform.leads.status', ['lead' => $lead->id]), [
                'status' => LeadStatusEnum::IN_PROGRESS->value,
                'resolution_notes' => 'Contacting the user now.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', LeadStatusEnum::IN_PROGRESS->value)
            ->assertJsonPath('data.resolution_notes', 'Contacting the user now.');

        $this->actingAs($admin)
             ->patchJson(route('platform.leads.status', ['lead' => $lead->id]), [
                 'status' => LeadStatusEnum::CONTACTED->value,
             ])
             ->assertStatus(200)
             ->assertJsonPath('data.status', LeadStatusEnum::CONTACTED->value);

        $this->actingAs($admin)
            ->patchJson(route('platform.leads.status', ['lead' => $lead->id]), [
                'status' => 'invalid_status',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_super_admin_can_delete_leads(): void
    {
        $admin = $this->makeSuperAdmin();
        /** @var Lead $lead */
        $lead = Lead::factory()->create();

        $this->actingAs($admin)
            ->deleteJson(route('platform.leads.destroy', ['lead' => $lead->id]))
            ->assertStatus(200);

        $this->assertSoftDeleted($lead);
    }

    // ── Show a single lead ────────────────────────────────────────────────

    public function test_super_admin_can_view_a_single_lead(): void
    {
        $admin = $this->makeSuperAdmin();
        /** @var Lead $lead */
        $lead = Lead::factory()->create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        $this->actingAs($admin)
            ->getJson(route('platform.leads.show', ['lead' => $lead->id]))
            ->assertStatus(200)
            ->assertJsonPath('data.id', $lead->id)
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonStructure([
                'data' => ['id', 'type', 'status', 'name', 'email', 'message', 'created_at'],
            ]);
    }

    public function test_unauthenticated_user_cannot_view_a_single_lead(): void
    {
        /** @var Lead $lead */
        $lead = Lead::factory()->create();

        $this->getJson(route('platform.leads.show', ['lead' => $lead->id]))
            ->assertStatus(401);
    }

    public function test_non_super_admin_cannot_view_a_single_lead(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Lead $lead */
        $lead = Lead::factory()->create();

        $this->actingAs($user)
            ->getJson(route('platform.leads.show', ['lead' => $lead->id]))
            ->assertStatus(403);
    }

    // ── 404s for actions on a nonexistent lead ─────────────────────────────

    public function test_viewing_a_nonexistent_lead_returns_404(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->getJson(route('platform.leads.show', ['lead' => 999999]))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_updating_status_of_a_nonexistent_lead_returns_404(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->patchJson(route('platform.leads.status', ['lead' => 999999]), [
                'status' => LeadStatusEnum::CONTACTED->value,
            ])
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_deleting_a_nonexistent_lead_returns_404(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->deleteJson(route('platform.leads.destroy', ['lead' => 999999]))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    // ── List filter validation ──────────────────────────────────────────────

    public function test_list_leads_rejects_invalid_type_filter(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->getJson(route('platform.leads.index', ['type' => 'not_a_real_type']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_list_leads_rejects_created_to_before_created_from(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->getJson(route('platform.leads.index', [
                'created_from' => now()->toDateString(),
                'created_to' => now()->subDays(5)->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['created_to']);
    }

    public function test_list_leads_rejects_per_page_over_maximum(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->getJson(route('platform.leads.index', ['per_page' => 101]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    private function makeSuperAdmin(): User
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        $admin->assignRole($role);

        return $admin;
    }
}
