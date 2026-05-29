<?php

namespace Database\Seeders;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StorefrontRuntimeDevSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('slug', 'merchant-store')->first();
        $creator = User::query()->where('email', 'merchant@test.com')->first() ?? User::query()->first();

        if (!$store || !$creator) {
            $this->command?->warn('StorefrontRuntimeDevSeeder skipped because the default store or creator user is missing.');
            return;
        }

        $aboutPage = StoreMarketingPage::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'slug->en' => 'about-us',
            ],
            [
                'title' => ['en' => 'About Us', 'ar' => 'من نحن'],
                'slug' => ['en' => 'about-us', 'ar' => 'about-us-ar'],
                'excerpt' => [
                    'en' => 'Learn how this seeded tenant storefront is wired for runtime rendering.',
                    'ar' => 'تعرف على كيفية تجهيز هذا المتجر التجريبي للعمل عبر واجهة التشغيل.',
                ],
                'content' => [],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now()->subDay(),
                'seo' => [
                    'meta_title' => ['en' => 'About Us', 'ar' => 'من نحن'],
                    'meta_description' => [
                        'en' => 'Development storefront about page for the seeded tenant.',
                        'ar' => 'صفحة تعريفية لمتجر التطوير الخاص بالمستأجر التجريبي.',
                    ],
                    'robots' => 'index,follow',
                    'twitter_card' => 'summary_large_image',
                    'structured_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebPage',
                    ],
                ],
                'template' => MarketingPageTemplateEnum::GENERIC,
                'sort_order' => 0,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ],
        );

        $aboutPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'hero_about',
            ],
            [
                'section_type' => 'hero',
                'sort_order' => 0,
                'title' => ['en' => 'Built for modern merchants', 'ar' => 'مصمم للتجار العصريين'],
                'subtitle' => [
                    'en' => 'This seeded storefront proves the Laravel runtime can render tenant-aware pages locally.',
                    'ar' => 'يوضح هذا المتجر التجريبي أن واجهة لارافيل تستطيع عرض صفحات خاصة بالمستأجر محليًا.',
                ],
                'content' => [
                    'en' => ['headline' => 'Built for modern merchants', 'subheadline' => 'Seeded runtime content for local development.'],
                    'ar' => ['headline' => 'مصمم للتجار العصريين', 'subheadline' => 'محتوى تشغيل تجريبي للتطوير المحلي.'],
                ],
                'settings' => ['layout' => 'full'],
                'is_active' => true,
            ],
        );

        $aboutPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'features_about',
            ],
            [
                'section_type' => 'features',
                'sort_order' => 1,
                'title' => ['en' => 'Runtime features', 'ar' => 'ميزات واجهة التشغيل'],
                'subtitle' => [
                    'en' => 'A minimal feature list that renders through the storefront runtime section registry.',
                    'ar' => 'قائمة ميزات بسيطة يتم عرضها عبر سجل أقسام واجهة التشغيل.',
                ],
                'content' => [
                    'en' => [
                        'Tenant-aware routing',
                        'SSR page payload delivery',
                        'Runtime navigation and SEO',
                    ],
                    'ar' => [
                        'توجيه خاص بالمستأجر',
                        'تسليم حمولة الصفحات عبر SSR',
                        'تنقل وتهيئة SEO عبر واجهة التشغيل',
                    ],
                ],
                'settings' => ['layout' => 'grid'],
                'is_active' => true,
            ],
        );

        $demoPage = StoreMarketingPage::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'slug->en' => 'demo',
            ],
            [
                'title' => ['en' => 'Showcase', 'ar' => 'العرض التوضيحي'],
                'slug' => ['en' => 'demo', 'ar' => 'demo-ar'],
                'excerpt' => [
                    'en' => 'A seeded demo page for local storefront runtime validation.',
                    'ar' => 'صفحة عرض تجريبية مزروعة للتحقق من واجهة تشغيل المتجر محليًا.',
                ],
                'content' => [],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now()->subHours(12),
                'seo' => [
                    'meta_title' => ['en' => 'Showcase', 'ar' => 'العرض التوضيحي'],
                    'meta_description' => [
                        'en' => 'Seeded demo page for the tenant storefront runtime.',
                        'ar' => 'صفحة عرض تجريبية مزروعة لمتجر المستأجر عبر واجهة التشغيل.',
                    ],
                    'robots' => 'index,follow',
                    'twitter_card' => 'summary_large_image',
                    'structured_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebPage',
                    ],
                ],
                'template' => MarketingPageTemplateEnum::LANDING,
                'sort_order' => 1,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ],
        );

        $demoPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'hero_demo',
            ],
            [
                'section_type' => 'hero',
                'sort_order' => 0,
                'title' => ['en' => 'Local runtime showcase', 'ar' => 'عرض محلي لواجهة التشغيل'],
                'subtitle' => [
                    'en' => 'Use this page to confirm seeded tenant content resolves through the runtime APIs.',
                    'ar' => 'استخدم هذه الصفحة للتأكد من أن محتوى المستأجر المزروع يتم حله عبر واجهات التشغيل.',
                ],
                'content' => [
                    'en' => ['headline' => 'Local runtime demo', 'subheadline' => 'Seeded page content for justshop-frontend development.'],
                    'ar' => ['headline' => 'عرض محلي لواجهة التشغيل', 'subheadline' => 'محتوى صفحة مزروع لتطوير justshop-frontend.'],
                ],
                'settings' => ['layout' => 'full'],
                'is_active' => true,
            ],
        );

        $demoPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'features_demo',
            ],
            [
                'section_type' => 'features',
                'sort_order' => 1,
                'title' => ['en' => 'What to verify', 'ar' => 'ما الذي يجب التحقق منه'],
                'subtitle' => [
                    'en' => 'Suggested local checks after migrate:fresh --seed.',
                    'ar' => 'فحوصات محلية مقترحة بعد تشغيل migrate:fresh --seed.',
                ],
                'content' => [
                    'en' => [
                        'Open the tenant home page',
                        'Open /about-us and /demo',
                        'Verify category and product pages resolve',
                    ],
                    'ar' => [
                        'افتح الصفحة الرئيسية للمستأجر',
                        'افتح /about-us و /demo',
                        'تحقق من عمل صفحات التصنيف والمنتج',
                    ],
                ],
                'settings' => ['layout' => 'grid'],
                'is_active' => true,
            ],
        );
    }
}
