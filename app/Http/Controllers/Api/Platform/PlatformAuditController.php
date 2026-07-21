<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PlatformAuditController extends Controller
{
    public function index(): JsonResponse
    {
        $page = max(1, (int) request()->get('page', 1));
        $perPage = min(100, max(1, (int) request()->get('per_page', 25)));
        $action = request()->string('action')->trim()->toString();
        $search = request()->string('search')->trim()->toString();
        
        $query = AuditLog::query()
            ->orderByDesc('created_at');
        
        // Filter by action/event
        if ($action && $action !== 'all') {
            $query->where('event', $action);
        }
        
        // Search by actor email or event
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        
        $total = $query->count();
        $offset = ($page - 1) * $perPage;
        $lastPage = (int) ceil($total / $perPage);
        
        $logs = $query
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function (AuditLog $log) {
                // Get actor information
                $actor = null;
                if ($log->actor_id && $log->actor_type === 'App\\Models\\User') {
                    $actor = User::find($log->actor_id);
                }
                
                return [
                    'id' => $log->id,
                    'actor_name' => $actor?->name ?? 'System',
                    'actor_email' => $actor?->email ?? null,
                    'action' => $log->event,
                    'resource_type' => $log->metadata['resource_type'] ?? null,
                    'resource_id' => $log->metadata['resource_id'] ?? null,
                    'resource_name' => $log->metadata['resource_name'] ?? null,
                    'description' => ucfirst(str_replace('_', ' ', $log->event)),
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at->toISOString(),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $logs,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function show(int $log): JsonResponse
    {
        $auditLog = AuditLog::findOrFail($log);
        
        // Get actor information
        $actor = null;
        if ($auditLog->actor_id && $auditLog->actor_type === 'App\\Models\\User') {
            $actor = User::find($auditLog->actor_id);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $auditLog->id,
                'actor_name' => $actor?->name ?? 'System',
                'actor_email' => $actor?->email ?? null,
                'action' => $auditLog->event,
                'resource_type' => $auditLog->metadata['resource_type'] ?? null,
                'resource_id' => $auditLog->metadata['resource_id'] ?? null,
                'resource_name' => $auditLog->metadata['resource_name'] ?? null,
                'description' => ucfirst(str_replace('_', ' ', $auditLog->event)),
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
                'metadata' => $auditLog->metadata,
                'correlation_id' => $auditLog->correlation_id,
                'store_id' => $auditLog->store_id,
                'created_at' => $auditLog->created_at->toISOString(),
            ],
        ]);
    }
}

