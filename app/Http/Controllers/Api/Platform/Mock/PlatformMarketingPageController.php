<?php

namespace App\Http\Controllers\Api\Platform\Mock;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformMarketingPageController extends Controller
{
    private array $mockPages;

    public function __construct()
    {
        $this->mockPages = $this->generateMockData();
    }

    /**
     * List all marketing pages
     */
    public function index(Request $request): JsonResponse
    {
        $pages = $this->mockPages;

        // Filter by search
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $pages = array_filter($pages, function($page) use ($search) {
                $enTitle = strtolower($page['translations']['en']['title'] ?? '');
                $arTitle = strtolower($page['translations']['ar']['title'] ?? '');
                return str_contains($enTitle, $search) || str_contains($arTitle, $search);
            });
            $pages = array_values($pages);
        }

        // Filter by type
        if ($request->filled('type')) {
            $type = $request->input('type');
            $pages = array_filter($pages, fn($page) => $page['type'] === $type);
            $pages = array_values($pages);
        }

        // Pagination
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 25);
        $total = count($pages);
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        $paginatedPages = array_slice($pages, $offset, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedPages,
            'meta' => [
                'current_page' => (int) $page,
                'from' => $offset + 1,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => min($offset + $perPage, $total),
                'total' => $total,
            ],
        ]);
    }

    /**
     * Get a single marketing page
     */
    public function show(int $id): JsonResponse
    {
        $page = collect($this->mockPages)->firstWhere('id', $id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    /**
     * Create a new marketing page
     */
    public function store(Request $request): JsonResponse
    {
        $newId = max(array_column($this->mockPages, 'id')) + 1;

        $page = [
            'id' => $newId,
            'type' => $request->input('type', 'general'),
            'is_published' => false,
            'published_at' => null,
            'translations' => $request->input('translations', []),
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $page,
        ], 201);
    }

    /**
     * Update a marketing page
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $page = collect($this->mockPages)->firstWhere('id', $id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        $updated = array_merge($page, [
            'type' => $request->input('type', $page['type']),
            'translations' => $request->input('translations', $page['translations']),
            'updated_at' => now()->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $updated,
        ]);
    }

    /**
     * Delete a marketing page
     */
    public function destroy(int $id): JsonResponse
    {
        $page = collect($this->mockPages)->firstWhere('id', $id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully',
        ]);
    }

    /**
     * Publish a marketing page
     */
    public function publish(int $id): JsonResponse
    {
        $page = collect($this->mockPages)->firstWhere('id', $id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        $page['is_published'] = true;
        $page['published_at'] = now()->toISOString();
        $page['updated_at'] = now()->toISOString();

        return response()->json([
            'success' => true,
            'message' => 'Page published successfully',
            'data' => $page,
        ]);
    }

    /**
     * Unpublish a marketing page
     */
    public function unpublish(int $id): JsonResponse
    {
        $page = collect($this->mockPages)->firstWhere('id', $id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        $page['is_published'] = false;
        $page['published_at'] = null;
        $page['updated_at'] = now()->toISOString();

        return response()->json([
            'success' => true,
            'message' => 'Page unpublished successfully',
            'data' => $page,
        ]);
    }

    /**
     * Generate mock marketing pages
     */
    private function generateMockData(): array
    {
        return [
            // General Pages
            [
                'id' => 1,
                'type' => 'general',
                'is_published' => true,
                'published_at' => '2026-01-01T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'About Us',
                        'slug' => 'about-us',
                        'content' => '<h1>About Us</h1><p>We are a leading multi-tenant e-commerce platform provider.</p><h2>Our Mission</h2><p>Empower businesses to succeed online.</p>',
                        'meta_title' => 'About Us - Multi-Tenant Platform',
                        'meta_description' => 'Learn about our company and mission',
                    ],
                    'ar' => [
                        'title' => 'من نحن',
                        'slug' => 'about-us-ar',
                        'content' => '<h1>من نحن</h1><p>نحن مزود منصة تجارة إلكترونية متعددة المستأجرين رائد.</p>',
                        'meta_title' => 'من نحن - منصة متعددة المستأجرين',
                        'meta_description' => 'تعرف على شركتنا ومهمتنا',
                    ],
                ],
                'created_at' => '2026-01-01T00:00:00.000000Z',
                'updated_at' => '2026-01-15T00:00:00.000000Z',
            ],
            [
                'id' => 2,
                'type' => 'general',
                'is_published' => true,
                'published_at' => '2026-01-02T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'Contact Us',
                        'slug' => 'contact-us',
                        'content' => '<h1>Contact Us</h1><p>Get in touch with our team.</p><h2>Email</h2><p>support@platform.com</p><h2>Phone</h2><p>+1 (555) 123-4567</p>',
                        'meta_title' => 'Contact Us',
                        'meta_description' => 'Get in touch with our team',
                    ],
                    'ar' => [
                        'title' => 'اتصل بنا',
                        'slug' => 'contact-us-ar',
                        'content' => '<h1>اتصل بنا</h1><p>تواصل مع فريقنا.</p>',
                        'meta_title' => 'اتصل بنا',
                        'meta_description' => 'تواصل مع فريقنا',
                    ],
                ],
                'created_at' => '2026-01-02T00:00:00.000000Z',
                'updated_at' => '2026-01-16T00:00:00.000000Z',
            ],
            [
                'id' => 3,
                'type' => 'general',
                'is_published' => true,
                'published_at' => '2026-01-03T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'Privacy Policy',
                        'slug' => 'privacy-policy',
                        'content' => '<h1>Privacy Policy</h1><p>Your privacy is important to us.</p><h2>Data Collection</h2><p>We collect minimal data necessary for service provision.</p>',
                        'meta_title' => 'Privacy Policy',
                        'meta_description' => 'Read our privacy policy',
                    ],
                    'ar' => [
                        'title' => 'سياسة الخصوصية',
                        'slug' => 'privacy-policy-ar',
                        'content' => '<h1>سياسة الخصوصية</h1><p>خصوصيتك مهمة بالنسبة لنا.</p>',
                        'meta_title' => 'سياسة الخصوصية',
                        'meta_description' => 'اقرأ سياسة الخصوصية الخاصة بنا',
                    ],
                ],
                'created_at' => '2026-01-03T00:00:00.000000Z',
                'updated_at' => '2026-01-17T00:00:00.000000Z',
            ],
            [
                'id' => 4,
                'type' => 'general',
                'is_published' => true,
                'published_at' => '2026-01-04T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'Terms of Service',
                        'slug' => 'terms-of-service',
                        'content' => '<h1>Terms of Service</h1><p>By using our platform, you agree to these terms.</p>',
                        'meta_title' => 'Terms of Service',
                        'meta_description' => 'Read our terms of service',
                    ],
                    'ar' => [
                        'title' => 'شروط الخدمة',
                        'slug' => 'terms-of-service-ar',
                        'content' => '<h1>شروط الخدمة</h1><p>باستخدام منصتنا، فإنك توافق على هذه الشروط.</p>',
                        'meta_title' => 'شروط الخدمة',
                        'meta_description' => 'اقرأ شروط الخدمة الخاصة بنا',
                    ],
                ],
                'created_at' => '2026-01-04T00:00:00.000000Z',
                'updated_at' => '2026-01-18T00:00:00.000000Z',
            ],
            // Platform-Specific Pages
            [
                'id' => 5,
                'type' => 'platform_specific',
                'is_published' => true,
                'published_at' => '2026-01-05T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'Platform Features',
                        'slug' => 'features',
                        'content' => '<h1>Platform Features</h1><h2>Multi-Tenant Architecture</h2><p>Powerful multi-tenant support.</p><h2>Scalability</h2><p>Scale to millions of users.</p>',
                        'meta_title' => 'Platform Features',
                        'meta_description' => 'Explore our powerful features',
                    ],
                    'ar' => [
                        'title' => 'ميزات المنصة',
                        'slug' => 'features-ar',
                        'content' => '<h1>ميزات المنصة</h1><h2>بنية متعددة المستأجرين</h2><p>دعم قوي متعدد المستأجرين.</p>',
                        'meta_title' => 'ميزات المنصة',
                        'meta_description' => 'استكشف ميزاتنا القوية',
                    ],
                ],
                'created_at' => '2026-01-05T00:00:00.000000Z',
                'updated_at' => '2026-01-19T00:00:00.000000Z',
            ],
            [
                'id' => 6,
                'type' => 'platform_specific',
                'is_published' => true,
                'published_at' => '2026-01-06T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'Pricing Plans',
                        'slug' => 'pricing',
                        'content' => '<h1>Pricing Plans</h1><h2>Basic - $29/month</h2><p>Perfect for small businesses.</p><h2>Pro - $99/month</h2><p>For growing businesses.</p><h2>Enterprise - Custom</h2><p>For large organizations.</p>',
                        'meta_title' => 'Pricing Plans',
                        'meta_description' => 'Affordable pricing for every business',
                    ],
                    'ar' => [
                        'title' => 'خطط التسعير',
                        'slug' => 'pricing-ar',
                        'content' => '<h1>خطط التسعير</h1><h2>الأساسية - 29 دولار/شهر</h2><p>مثالي للشركات الصغيرة.</p>',
                        'meta_title' => 'خطط التسعير',
                        'meta_description' => 'أسعار معقولة لكل عمل',
                    ],
                ],
                'created_at' => '2026-01-06T00:00:00.000000Z',
                'updated_at' => '2026-01-20T00:00:00.000000Z',
            ],
            [
                'id' => 7,
                'type' => 'platform_specific',
                'is_published' => false,
                'published_at' => null,
                'translations' => [
                    'en' => [
                        'title' => 'Enterprise Solutions',
                        'slug' => 'enterprise',
                        'content' => '<h1>Enterprise Solutions</h1><p>Tailored solutions for enterprise clients.</p>',
                        'meta_title' => 'Enterprise Solutions',
                        'meta_description' => 'Enterprise-grade platform solutions',
                    ],
                    'ar' => [
                        'title' => 'حلول المؤسسات',
                        'slug' => 'enterprise-ar',
                        'content' => '<h1>حلول المؤسسات</h1><p>حلول مخصصة لعملاء المؤسسات.</p>',
                        'meta_title' => 'حلول المؤسسات',
                        'meta_description' => 'حلول منصة على مستوى المؤسسات',
                    ],
                ],
                'created_at' => '2026-01-07T00:00:00.000000Z',
                'updated_at' => '2026-01-21T00:00:00.000000Z',
            ],
            [
                'id' => 8,
                'type' => 'general',
                'is_published' => false,
                'published_at' => null,
                'translations' => [
                    'en' => [
                        'title' => 'Careers',
                        'slug' => 'careers',
                        'content' => '<h1>Careers</h1><p>Join our team!</p><h2>Open Positions</h2><ul><li>Software Engineer</li><li>Product Manager</li><li>UX Designer</li></ul>',
                        'meta_title' => 'Careers',
                        'meta_description' => 'Join our growing team',
                    ],
                    'ar' => [
                        'title' => 'الوظائف',
                        'slug' => 'careers-ar',
                        'content' => '<h1>الوظائف</h1><p>انضم إلى فريقنا!</p>',
                        'meta_title' => 'الوظائف',
                        'meta_description' => 'انضم إلى فريقنا المتنامي',
                    ],
                ],
                'created_at' => '2026-01-08T00:00:00.000000Z',
                'updated_at' => '2026-01-22T00:00:00.000000Z',
            ],
            [
                'id' => 9,
                'type' => 'general',
                'is_published' => true,
                'published_at' => '2026-01-09T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'FAQ',
                        'slug' => 'faq',
                        'content' => '<h1>Frequently Asked Questions</h1><h2>How do I get started?</h2><p>Simply sign up and follow our onboarding guide.</p><h2>What payment methods do you accept?</h2><p>We accept all major credit cards and PayPal.</p>',
                        'meta_title' => 'FAQ',
                        'meta_description' => 'Frequently asked questions',
                    ],
                    'ar' => [
                        'title' => 'الأسئلة الشائعة',
                        'slug' => 'faq-ar',
                        'content' => '<h1>الأسئلة الشائعة</h1><h2>كيف أبدأ؟</h2><p>ببساطة قم بالتسجيل واتبع دليل الإعداد الخاص بنا.</p>',
                        'meta_title' => 'الأسئلة الشائعة',
                        'meta_description' => 'الأسئلة المتداولة',
                    ],
                ],
                'created_at' => '2026-01-09T00:00:00.000000Z',
                'updated_at' => '2026-01-23T00:00:00.000000Z',
            ],
            [
                'id' => 10,
                'type' => 'platform_specific',
                'is_published' => true,
                'published_at' => '2026-01-10T00:00:00.000000Z',
                'translations' => [
                    'en' => [
                        'title' => 'Success Stories',
                        'slug' => 'success-stories',
                        'content' => '<h1>Success Stories</h1><p>See how businesses succeed with our platform.</p><h2>Case Study: Fashion Store</h2><p>Increased sales by 300% in 6 months.</p>',
                        'meta_title' => 'Success Stories',
                        'meta_description' => 'Customer success stories',
                    ],
                    'ar' => [
                        'title' => 'قصص النجاح',
                        'slug' => 'success-stories-ar',
                        'content' => '<h1>قصص النجاح</h1><p>شاهد كيف تنجح الشركات مع منصتنا.</p>',
                        'meta_title' => 'قصص النجاح',
                        'meta_description' => 'قصص نجاح العملاء',
                    ],
                ],
                'created_at' => '2026-01-10T00:00:00.000000Z',
                'updated_at' => '2026-01-24T00:00:00.000000Z',
            ],
        ];
    }
}
