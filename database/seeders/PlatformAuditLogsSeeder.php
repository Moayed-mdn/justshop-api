<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * PlatformAuditLogsSeeder
 * 
 * Seeds audit log entries for platform dashboard testing.
 */
class PlatformAuditLogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding platform audit logs...');

        // Get some users to act as actors
        $actors = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'store_admin']);
        })->limit(5)->get();

        if ($actors->isEmpty()) {
            $this->command->warn('No admin users found. Run StoreSeeder and PlatformUsersSeeder first.');
            return;
        }

        $stores = Store::limit(5)->get();
        
        $events = [
            'user.created',
            'user.updated',
            'user.suspended',
            'user.activated',
            'user.deleted',
            'store.created',
            'store.updated',
            'store.suspended',
            'store.activated',
            'store.deleted',
            'feature.toggled',
            'setting.updated',
            'permission.granted',
            'permission.revoked',
        ];

        // Create audit logs for the last 30 days
        for ($i = 0; $i < 100; $i++) {
            $actor = $actors->random();
            $event = $events[array_rand($events)];
            $store = $stores->isNotEmpty() ? $stores->random() : null;
            
            // Determine resource based on event
            [$resourceType, $action] = explode('.', $event);
            $resourceId = rand(1, 50);
            
            AuditLog::create([
                'event' => $event,
                'actor_id' => $actor->id,
                'actor_type' => User::class,
                'membership_id' => null,
                'store_id' => $store?->id,
                'correlation_id' => Str::uuid()->toString(),
                'ip_address' => $this->randomIp(),
                'user_agent' => $this->randomUserAgent(),
                'metadata' => [
                    'resource_type' => ucfirst($resourceType),
                    'resource_id' => $resourceId,
                    'resource_name' => ucfirst($resourceType) . ' ' . $resourceId,
                    'action' => $action,
                    'reason' => $this->randomReason($action),
                ],
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
            ]);
        }

        $total = AuditLog::count();
        $this->command->info("✅ Platform audit logs seeding complete! Total logs: {$total}");
    }

    private function randomIp(): string
    {
        return implode('.', [
            rand(1, 255),
            rand(0, 255),
            rand(0, 255),
            rand(1, 255),
        ]);
    }

    private function randomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
        ];
        
        return $userAgents[array_rand($userAgents)];
    }

    private function randomReason(string $action): string
    {
        $reasons = [
            'created' => 'New registration',
            'updated' => 'Profile information updated',
            'suspended' => 'Policy violation detected',
            'activated' => 'Suspension lifted',
            'deleted' => 'User requested account deletion',
            'toggled' => 'Administrative decision',
        ];
        
        return $reasons[$action] ?? 'Administrative action';
    }
}

