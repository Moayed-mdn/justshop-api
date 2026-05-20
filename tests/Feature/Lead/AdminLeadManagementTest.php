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
        $this->getJson('/api/v1/admin/leads')
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'error_code' => 'AUTH_002',
            ]);
    }

    public function test_admin_endpoints_require_super_admin_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/admin/leads')
            ->assertStatus(403)
            ->assertJson([
                'status' => false,
                'error_code' => 'HTTP_403',
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
            ->getJson('/api/v1/admin/leads?type=contact')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/leads?status=spam')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', LeadStatusEnum::SPAM->value);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/leads?email=jane')
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
            ->patchJson("/api/v1/admin/leads/{$lead->id}/status", [
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
            ->patchJson("/api/v1/admin/leads/{$lead->id}/status", [
                'status' => LeadStatusEnum::ARCHIVED->value,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', LeadStatusEnum::ARCHIVED->value);

        $lead->refresh();
        $this->assertNotNull($lead->archived_at);
        $this->assertSame($resolvedAt?->toISOString(), $lead->resolved_at?->toISOString());

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/leads/{$lead->id}/status", [
                'status' => LeadStatusEnum::IN_PROGRESS->value,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', LeadStatusEnum::IN_PROGRESS->value);

        $lead->refresh();
        $this->assertNull($lead->archived_at);
        $this->assertNull($lead->resolved_at);
        $this->assertNull($lead->resolved_by);
    }

    public function test_super_admin_can_delete_lead(): void
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
            ->deleteJson("/api/v1/admin/leads/{$lead->id}")
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('lead.deleted'),
                'data' => null,
            ]);

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    private function makeSuperAdmin(): User
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        $admin->assignRole($role);

        return $admin;
    }
}
