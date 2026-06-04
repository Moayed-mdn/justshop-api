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
                'title' => ['en' => 'We exist to make great products accessible to everyone', 'ar' => 'نحن هنا لنجعل المنتجات الرائعة في متناول الجميع'],
                'subtitle' => [
                    'en' => 'Founded in 2019, JustShop started in a small warehouse with one obsession: deliver premium-quality goods at honest prices, fast.',
                    'ar' => 'تأسست JustShop عام 2019 في مستودع صغير بهاجس واحد: تقديم منتجات عالية الجودة بأسعار عادلة وبسرعة.',
                ],
                'content' => [
                    'en' => [
                        'headline' => 'We exist to make great products accessible to everyone',
                        'subheadline' => 'Founded in 2019, JustShop started in a small warehouse with one obsession: deliver premium-quality goods at honest prices, fast.',
                        'mission' => 'Our mission is simple — remove the gap between quality and affordability, and put the customer at the center of everything we do.',
                        'cta_primary' => ['label' => 'Shop Best Sellers', 'url' => '/'],
                        'cta_secondary' => ['label' => 'New Arrivals', 'url' => '/'],
                    ],
                    'ar' => [
                        'headline' => 'نحن هنا لنجعل المنتجات الرائعة في متناول الجميع',
                        'subheadline' => 'تأسست JustShop عام 2019 في مستودع صغير بهاجس واحد: تقديم منتجات عالية الجودة بأسعار عادلة وبسرعة.',
                        'mission' => 'مهمتنا بسيطة — إزالة الفجوة بين الجودة والقدرة على تحمّل التكاليف، ووضع العميل في مركز كل ما نفعله.',
                        'cta_primary' => ['label' => 'تسوق الأكثر مبيعًا', 'url' => '/'],
                        'cta_secondary' => ['label' => 'وصل حديثًا', 'url' => '/'],
                    ],
                ],
                'settings' => ['layout' => 'full'],
                'is_active' => true,
            ],
        );

        $aboutPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'story_about',
            ],
            [
                'section_type' => 'content',
                'sort_order' => 1,
                'title' => ['en' => 'Our Story', 'ar' => 'قصتنا'],
                'subtitle' => [
                    'en' => 'From a garage idea to thousands of happy customers.',
                    'ar' => 'من فكرة في مرآب إلى آلاف العملاء السعداء.',
                ],
                'content' => [
                    'en' => [
                        'body' => 'Our founders Sara and Khalid were tired of choosing between cheap products that break and expensive ones that drain your wallet. In 2019 they packed their savings, sourced directly from ethical manufacturers, and shipped their first 50 orders from a shared warehouse in Austin. Five years later JustShop ships over 8,000 orders a month across North America — still obsessing over every detail from material sourcing to the last-mile delivery experience.',
                        'stats' => [
                            ['value' => '8,000+', 'label' => 'Orders per month'],
                            ['value' => '4.8★', 'label' => 'Average review score'],
                            ['value' => '96%', 'label' => 'On-time delivery rate'],
                            ['value' => '60-day', 'label' => 'Hassle-free returns'],
                        ],
                    ],
                    'ar' => [
                        'body' => 'سئم مؤسسانا سارة وخالد من الاختيار بين منتجات رخيصة تتكسر ومنتجات باهظة تستنزف الميزانية. في 2019 جمعا مدخراتهما وتعاقدا مباشرة مع مصنّعين أخلاقيين وأرسلا أول 50 طلب من مستودع مشترك في أوستن. بعد خمس سنوات، يشحن JustShop أكثر من 8,000 طلب شهريًا عبر أمريكا الشمالية.',
                        'stats' => [
                            ['value' => '+8,000', 'label' => 'طلب شهريًا'],
                            ['value' => '4.8★', 'label' => 'متوسط تقييم العملاء'],
                            ['value' => '96%', 'label' => 'معدل التوصيل في الموعد'],
                            ['value' => '60 يومًا', 'label' => 'إرجاع بدون متاعب'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'split'],
                'is_active' => true,
            ],
        );

        $aboutPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'quality_about',
            ],
            [
                'section_type' => 'features',
                'sort_order' => 2,
                'title' => ['en' => 'Quality you can verify', 'ar' => 'جودة يمكنك التحقق منها'],
                'subtitle' => [
                    'en' => 'We do not just say our products are great — we prove it.',
                    'ar' => 'لا نقول فحسب إن منتجاتنا رائعة — بل نثبت ذلك.',
                ],
                'content' => [
                    'en' => [
                        ['icon' => 'shield-check', 'title' => 'Direct-source manufacturing', 'body' => 'Every product is sourced directly from ISO-certified factories. No middlemen, no markups, no compromises.'],
                        ['icon' => 'flask', 'title' => '3-stage quality testing', 'body' => 'Products go through factory QC, an independent third-party lab, and a final in-house inspection before they ship.'],
                        ['icon' => 'certificate', 'title' => 'Safety certified', 'body' => 'All applicable products carry CE, FCC, or ASTM certifications depending on category.'],
                        ['icon' => 'leaf', 'title' => 'Sustainable packaging', 'body' => '100% recyclable packaging since 2022. We offset remaining carbon through verified reforestation credits.'],
                        ['icon' => 'cpu', 'title' => 'AI-assisted fit & match', 'body' => 'Our AI tools help you find the right size, style, or spec — transparently, with no personal data stored.'],
                        ['icon' => 'users', 'title' => 'Real customer reviews', 'body' => 'Every review is from a verified purchaser. We do not filter negatives — you see everything.'],
                    ],
                    'ar' => [
                        ['icon' => 'shield-check', 'title' => 'تصنيع من المصدر المباشر', 'body' => 'كل منتج مصدره مباشرة من مصانع معتمدة بـ ISO. بلا وسطاء، بلا هوامش مبالغ فيها.'],
                        ['icon' => 'flask', 'title' => 'اختبار جودة بثلاث مراحل', 'body' => 'تخضع المنتجات لمراقبة جودة المصنع، ومختبر طرف ثالث مستقل، وفحص داخلي نهائي.'],
                        ['icon' => 'certificate', 'title' => 'معتمد سلامة', 'body' => 'جميع المنتجات المعنية تحمل شهادات CE أو FCC أو ASTM حسب الفئة.'],
                        ['icon' => 'leaf', 'title' => 'تغليف مستدام', 'body' => 'تغليف قابل للتدوير 100% منذ 2022. نعوّض الكربون المتبقي عبر ائتمانات إعادة التشجير.'],
                        ['icon' => 'cpu', 'title' => 'مطابقة بمساعدة الذكاء الاصطناعي', 'body' => 'تساعدك أدواتنا الذكية على اختيار المقاس أو الطراز المناسب — بشفافية كاملة ودون حفظ بيانات شخصية.'],
                        ['icon' => 'users', 'title' => 'تقييمات حقيقية من عملاء موثّقين', 'body' => 'كل تقييم صادر من مشترٍ موثّق. لا نحذف السلبيات — ترى كل شيء.'],
                    ],
                ],
                'settings' => ['layout' => 'grid', 'columns' => 3],
                'is_active' => true,
            ],
        );

        $aboutPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'customer_promise_about',
            ],
            [
                'section_type' => 'content',
                'sort_order' => 3,
                'title' => ['en' => 'Our promise to you', 'ar' => 'وعدنا لك'],
                'subtitle' => [
                    'en' => 'Customer service is not a department — it is our entire company.',
                    'ar' => 'خدمة العملاء ليست قسمًا — إنها شركتنا بأكملها.',
                ],
                'content' => [
                    'en' => [
                        'promises' => [
                            ['title' => '< 2 hr response time', 'body' => 'Our support team replies within 2 hours on business days, and within 6 hours on weekends.'],
                            ['title' => '60-day no-questions returns', 'body' => 'Changed your mind? Received a defect? Ship it back within 60 days for a full refund — no forms, no arguing.'],
                            ['title' => 'Price match guarantee', 'body' => 'Find the same product cheaper on Amazon or any major retailer? We will match it, same day.'],
                            ['title' => 'Order protection', 'body' => 'Every order is insured against loss, damage, or delay. If something goes wrong we reship or refund — automatically.'],
                        ],
                    ],
                    'ar' => [
                        'promises' => [
                            ['title' => 'وقت استجابة أقل من ساعتين', 'body' => 'يرد فريق الدعم خلال ساعتين في أيام العمل، وخلال 6 ساعات في عطلات نهاية الأسبوع.'],
                            ['title' => 'إرجاع 60 يومًا بلا أسئلة', 'body' => 'غيّرت رأيك؟ استلمت منتجًا معيبًا؟ أعده خلال 60 يومًا لاسترداد كامل المبلغ.'],
                            ['title' => 'ضمان مطابقة السعر', 'body' => 'وجدت المنتج بسعر أرخص في أمازون أو أي متجر كبير؟ سنطابق السعر في نفس اليوم.'],
                            ['title' => 'حماية الطلبات', 'body' => 'كل طلب مؤمّن ضد الفقدان والتلف والتأخير. إذا حدث خطأ نعيد الشحن أو نسترد المبلغ تلقائيًا.'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'list'],
                'is_active' => true,
            ],
        );

        $aboutPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'sustainability_about',
            ],
            [
                'section_type' => 'content',
                'sort_order' => 4,
                'title' => ['en' => 'Sustainability — verified, not vague', 'ar' => 'الاستدامة — موثّقة لا مجرد شعار'],
                'subtitle' => [
                    'en' => '2026 shoppers deserve real data, not marketing copy.',
                    'ar' => 'يستحق المتسوقون في 2026 بيانات حقيقية، لا نصوصًا تسويقية.',
                ],
                'content' => [
                    'en' => [
                        'metrics' => [
                            ['label' => 'Carbon offset', 'value' => '100%', 'note' => 'Verified via Gold Standard credits'],
                            ['label' => 'Recyclable packaging', 'value' => '100%', 'note' => 'FSC-certified materials since 2022'],
                            ['label' => 'Ethical factories', 'value' => '34', 'note' => 'All SA8000-audited suppliers'],
                            ['label' => 'Trees planted in 2025', 'value' => '12,400', 'note' => 'Via One Tree Planted partnership'],
                        ],
                        'disclosure' => 'We use AI to optimise delivery routes (reducing fuel use by ~18%) and for personalised product recommendations. No biometric data is ever collected or stored.',
                    ],
                    'ar' => [
                        'metrics' => [
                            ['label' => 'تعويض الكربون', 'value' => '100%', 'note' => 'موثّق عبر ائتمانات Gold Standard'],
                            ['label' => 'تغليف قابل للتدوير', 'value' => '100%', 'note' => 'مواد معتمدة FSC منذ 2022'],
                            ['label' => 'مصانع أخلاقية', 'value' => '34', 'note' => 'جميع الموردين خاضعون لتدقيق SA8000'],
                            ['label' => 'شجرة مزروعة في 2025', 'value' => '12,400', 'note' => 'عبر شراكة One Tree Planted'],
                        ],
                        'disclosure' => 'نستخدم الذكاء الاصطناعي لتحسين مسارات التوصيل (تخفيض استهلاك الوقود ~18%) وللتوصيات المخصصة. لا يتم جمع أي بيانات بيومترية أو تخزينها.',
                    ],
                ],
                'settings' => ['layout' => 'metrics'],
                'is_active' => true,
            ],
        );

        $aboutPage->sections()->updateOrCreate(
            [
                'store_id' => $store->id,
                'identifier' => 'cta_about',
            ],
            [
                'section_type' => 'cta',
                'sort_order' => 5,
                'title' => ['en' => 'Ready to shop smarter?', 'ar' => 'مستعد للتسوق بذكاء؟'],
                'subtitle' => [
                    'en' => 'Join 200,000+ customers who switched to JustShop. Follow our store for VIP-only deals and early access to new drops.',
                    'ar' => 'انضم إلى أكثر من 200,000 عميل اختاروا JustShop. تابع متجرنا للحصول على عروض VIP وأسبقية الوصول إلى المنتجات الجديدة.',
                ],
                'content' => [
                    'en' => [
                        'ctas' => [
                            ['label' => 'Shop Best Sellers', 'url' => '/', 'style' => 'primary'],
                            ['label' => 'New Arrivals', 'url' => '/', 'style' => 'secondary'],
                            ['label' => 'Follow Our Store', 'url' => '/', 'style' => 'outline'],
                        ],
                        'trust_badges' => [
                            'Free shipping over $49',
                            '60-day returns',
                            'Price match guarantee',
                            'Secure checkout',
                        ],
                    ],
                    'ar' => [
                        'ctas' => [
                            ['label' => 'تسوق الأكثر مبيعًا', 'url' => '/', 'style' => 'primary'],
                            ['label' => 'وصل حديثًا', 'url' => '/', 'style' => 'secondary'],
                            ['label' => 'تابع متجرنا', 'url' => '/', 'style' => 'outline'],
                        ],
                        'trust_badges' => [
                            'شحن مجاني فوق $49',
                            'إرجاع 60 يومًا',
                            'ضمان مطابقة السعر',
                            'دفع آمن',
                        ],
                    ],
                ],
                'settings' => ['layout' => 'centered'],
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
