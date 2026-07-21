<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Platform Dashboard Controller
 * 
 * Wave 6: Platform authority domain controller.
 * Platform authority is INDEPENDENT from merchant authority.
 */
class PlatformDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Get real counts
        $totalUsers = User::count();
        $totalStores = Store::count();
        $totalLeads = Lead::count();
        
        // Calculate revenue (TODO: Implement when orders table has revenue tracking)
        $totalRevenue = 0;
        
        // Calculate trends (compare to 30 days ago)
        $thirtyDaysAgo = now()->subDays(30);
        
        $usersThisMonth = User::where('created_at', '>=', $thirtyDaysAgo)->count();
        $usersPreviousMonth = User::where('created_at', '<', $thirtyDaysAgo)
            ->where('created_at', '>=', now()->subDays(60))
            ->count();
        $usersTrend = $this->calculateTrend($usersThisMonth, $usersPreviousMonth);
        
        $storesThisMonth = Store::where('created_at', '>=', $thirtyDaysAgo)->count();
        $storesPreviousMonth = Store::where('created_at', '<', $thirtyDaysAgo)
            ->where('created_at', '>=', now()->subDays(60))
            ->count();
        $storesTrend = $this->calculateTrend($storesThisMonth, $storesPreviousMonth);
        
        $leadsThisMonth = Lead::where('created_at', '>=', $thirtyDaysAgo)->count();
        $leadsPreviousMonth = Lead::where('created_at', '<', $thirtyDaysAgo)
            ->where('created_at', '>=', now()->subDays(60))
            ->count();
        $leadsTrend = $this->calculateTrend($leadsThisMonth, $leadsPreviousMonth);
        
        return response()->json([
            'success' => true,
            'data' => [
                'totalUsers' => $totalUsers,
                'totalStores' => $totalStores,
                'totalRevenue' => $totalRevenue,
                'totalLeads' => $totalLeads,
                'usersTrend' => $usersTrend,
                'storesTrend' => $storesTrend,
                'revenueTrend' => [
                    'change' => 0,
                    'direction' => 'neutral', // TODO: Calculate when revenue tracking is implemented
                ],
                'leadsTrend' => $leadsTrend,
            ],
        ]);
    }

    public function cmsStats(): JsonResponse
    {
        // Get real CMS statistics
        
        // Blog posts use is_published boolean
        $blogPublished = DB::table('blog_posts')->where('is_published', true)->count();
        $blogDraft = DB::table('blog_posts')->where('is_published', false)->whereNull('deleted_at')->count();
        $blogArchived = DB::table('blog_posts')->whereNotNull('deleted_at')->count();
        
        // Platform marketing pages use status enum
        $pageStats = DB::table('platform_marketing_pages')
            ->select('status', DB::raw('count(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        
        // CMS documents use is_published boolean
        $docsPublished = DB::table('cms_documents')->where('is_published', true)->count();
        $docsDraft = DB::table('cms_documents')->where('is_published', false)->whereNull('deleted_at')->count();
        $docsArchived = DB::table('cms_documents')->whereNotNull('deleted_at')->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'blog' => [
                    'total' => $blogPublished + $blogDraft + $blogArchived,
                    'published' => $blogPublished,
                    'draft' => $blogDraft,
                    'archived' => $blogArchived,
                ],
                'pages' => [
                    'total' => array_sum($pageStats),
                    'published' => $pageStats['published'] ?? 0,
                    'draft' => $pageStats['draft'] ?? 0,
                    'archived' => $pageStats['archived'] ?? 0,
                ],
                'docs' => [
                    'total' => $docsPublished + $docsDraft + $docsArchived,
                    'published' => $docsPublished,
                    'draft' => $docsDraft,
                    'archived' => $docsArchived,
                ],
            ],
        ]);
    }
    
    /**
     * Calculate trend percentage and direction
     */
    private function calculateTrend(int $current, int $previous): array
    {
        if ($previous === 0) {
            return [
                'change' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }
        
        $change = (($current - $previous) / $previous) * 100;
        
        return [
            'change' => round(abs($change), 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }
}

