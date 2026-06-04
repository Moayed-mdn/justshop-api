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

    private function makeSuperAdmin(): User
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        $admin->assignRole($role);

        return $admin;
    }
}
