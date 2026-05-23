<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Auth\DeviceTrustRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceTrustManager
{
    public function track(Request $request, User $user): void
    {
        $deviceId = $request->header('X-Device-ID') ?? 'unknown';
        
        DeviceTrustRecord::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'device_type' => $request->header('User-Agent') ?? 'unknown',
                'ip_address' => $request->ip() ?? 'unknown',
                'last_active_at' => now(),
            ]
        );

        Log::info('auth.device.tracked', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'ip' => $request->ip(),
        ]);
    }

    public function isTrusted(User $user, string $deviceId): bool
    {
        return DeviceTrustRecord::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->where('is_trusted', true)
            ->exists();
    }
}
