<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Policies\BlogPostPolicy;
use App\Policies\BrandPolicy;
use App\Policies\MembershipPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PolicyOwnershipVisibilityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_ownership_report_keeps_visibility_for_membership_compatibility_bridge(): void
    {
        $outputPath = storage_path('app/testing/policy-ownership-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:report-policy-ownership', [
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $report = json_decode((string) File::get($outputPath), true);
        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('entries', $report);
        $this->assertNotEmpty($report['entries']);

        $target = collect($report['entries'])->first(fn (array $entry): bool => ($entry['controller'] ?? null) === 'App\\Http\\Controllers\\Api\\Merchant\\AdminUserController'
            && ($entry['controller_method'] ?? null) === 'index');

        $this->assertNotNull($target);
        $this->assertSame(MembershipPolicy::class, $target['policy_used']);
        $this->assertTrue($target['store_aware']);
        $this->assertFalse($target['generic_currentStore']);
        $this->assertFalse($target['hidden_fallback']);
    }

    public function test_policy_ownership_report_marks_safe_domains_as_explicit_policy_owned(): void
    {
        $outputPath = storage_path('app/testing/policy-ownership-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:report-policy-ownership', [
            '--output' => $outputPath,
        ])->assertSuccessful();

        $report = json_decode((string) File::get($outputPath), true);
        $entries = collect($report['entries']);

        $brandIndex = $entries->first(fn (array $entry): bool => ($entry['controller'] ?? null) === 'App\\Http\\Controllers\\Api\\Merchant\\AdminBrandController'
            && ($entry['controller_method'] ?? null) === 'index');

        $categoryShow = $entries->first(fn (array $entry): bool => ($entry['controller'] ?? null) === 'App\\Http\\Controllers\\Api\\Merchant\\AdminCategoryController'
            && ($entry['controller_method'] ?? null) === 'show');

        $this->assertNotNull($brandIndex);
        $this->assertSame(BrandPolicy::class, $brandIndex['policy_used']);
        $this->assertTrue($brandIndex['policy_invoked']);
        $this->assertTrue($brandIndex['ownership_matches_expected']);
        $this->assertTrue($brandIndex['dual_authorization_path']);
        $this->assertFalse($brandIndex['generic_currentStore']);
        $this->assertFalse($brandIndex['hidden_fallback']);

        $blogIndex = $entries->first(fn (array $entry): bool => ($entry['controller'] ?? null) === 'App\\Http\\Controllers\\Api\\Platform\\AdminBlogController'
            && ($entry['controller_method'] ?? null) === 'index');

        $this->assertNotNull($blogIndex);
        $this->assertSame(BlogPostPolicy::class, $blogIndex['policy_used']);
        $this->assertTrue($blogIndex['policy_invoked']);
        $this->assertTrue($blogIndex['ownership_matches_expected']);
        $this->assertFalse($blogIndex['hidden_fallback']);
        $this->assertSame('explicit_policy', $blogIndex['authorization_source']);

        $this->assertSame(100, $report['normalized_domain_metrics']['brand']['health_score']);
        $this->assertSame(100, $report['normalized_domain_metrics']['cms_blog']['health_score']);
    }
}
