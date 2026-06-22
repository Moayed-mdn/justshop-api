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

        $this->seedHomePage($store, $creator);
        $this->seedAboutPage($store, $creator);
        $this->seedContactPage($store, $creator);
        $this->seedFaqPage($store, $creator);
        $this->seedSummerSalePage($store, $creator);
    }

    private function seedHomePage(Store $store, User $creator): void
    {
        $page = StoreMarketingPage::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'slug->en' => 'home',
            ],
            [
                'title' => ['en' => 'Home', 'ar' => 'الرئيسية'],
                'slug' => ['en' => 'home', 'ar' => 'home'],
                'excerpt' => [
                    'en' => 'JustShop — premium quality products at honest prices, delivered fast.',
                    'ar' => 'JustShop — منتجات عالية الجودة بأسعار عادلة، توصيل سريع.',
                ],
                'content' => [],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now()->subDays(7),
                'seo' => [
                    'meta_title' => ['en' => 'JustShop Demo — Premium Products, Honest Prices', 'ar' => 'JustShop — منتجات ممتازة بأسعار منصفة'],
                    'meta_description' => [
                        'en' => 'Shop premium-quality products with fast shipping, 60-day returns, and price-match guarantee. Join 200,000+ happy customers.',
                        'ar' => 'تسوق منتجات عالية الجودة مع شحن سريع وإرجاع لمدة 60 يوم وضمان مطابقة السعر. انضم إلى أكثر من 200,000 عميل سعيد.',
                    ],
                    'canonical_url' => config('app.url'),
                    'og_image' => null,
                    'og_title' => ['en' => 'JustShop Demo — Shop Premium Products', 'ar' => 'JustShop — تسوق منتجات ممتازة'],
                    'og_description' => [
                        'en' => 'Premium-quality products at honest prices. Free shipping over $49.',
                        'ar' => 'منتجات عالية الجودة بأسعار عادلة. شحن مجاني للطلبات فوق $49.',
                    ],
                    'robots' => 'index,follow',
                    'twitter_card' => 'summary_large_image',
                    'structured_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Store',
                        'name' => 'JustShop Demo',
                        'url' => config('app.url'),
                        'description' => 'Premium-quality products at honest prices, delivered fast.',
                        'aggregateRating' => [
                            '@type' => 'AggregateRating',
                            'ratingValue' => '4.8',
                            'reviewCount' => '12400',
                            'bestRating' => '5',
                        ],
                    ],
                ],
                'template' => MarketingPageTemplateEnum::LANDING,
                'sort_order' => 0,
                'is_homepage' => true,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ],
        );

        // ── Hero Section ──────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'hero_home'],
            [
                'section_type' => 'hero',
                'sort_order' => 0,
                'title' => ['en' => 'Premium Quality, Honest Prices', 'ar' => 'جودة ممتازة، أسعار منصفة'],
                'subtitle' => [
                    'en' => 'Free shipping on orders over $49 · 60-day risk-free returns · Price match guarantee',
                    'ar' => 'شحن مجاني للطلبات فوق $49 · إرجاع بدون مخاطرة لمدة 60 يوم · ضمان مطابقة السعر',
                ],
                'content' => [
                    'en' => [
                        'headline' => 'Premium Quality, Honest Prices',
                        'subheadline' => 'Free shipping on orders over $49 · 60-day risk-free returns · Price match guarantee',
                        'cta_primary' => ['label' => 'Shop Best Sellers', 'url' => '/products'],
                        'cta_secondary' => ['label' => 'New Arrivals', 'url' => '/products?sort=newest'],
                        'background_style' => 'gradient',
                    ],
                    'ar' => [
                        'headline' => 'جودة ممتازة، أسعار منصفة',
                        'subheadline' => 'شحن مجاني للطلبات فوق $49 · إرجاع بدون مخاطرة لمدة 60 يوم · ضمان مطابقة السعر',
                        'cta_primary' => ['label' => 'تسوق الأكثر مبيعًا', 'url' => '/products'],
                        'cta_secondary' => ['label' => 'وصل حديثًا', 'url' => '/products?sort=newest'],
                        'background_style' => 'gradient',
                    ],
                ],
                'settings' => ['layout' => 'full', 'height' => 'large', 'overlay' => true],
                'is_active' => true,
            ],
        );

        // ── Trust Badges Section ──────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'trust_badges_home'],
            [
                'section_type' => 'features',
                'sort_order' => 1,
                'title' => ['en' => 'Why shop with JustShop?', 'ar' => 'لماذا تتسوق من JustShop؟'],
                'subtitle' => [
                    'en' => 'Thousands of customers trust us — here is why.',
                    'ar' => 'آلاف العملاء يثقون بنا — إليك السبب.',
                ],
                'content' => [
                    'items' => [
                        [
                            'title' => ['en' => 'Free Shipping Over $49', 'ar' => 'شحن مجاني فوق $49'],
                            'body' => ['en' => 'Free standard shipping on all orders over $49. Express shipping available from just $5.99.', 'ar' => 'شحن مجاني لجميع الطلبات فوق $49. الشحن السريع متوفر ابتداءً من $5.99.'],
                            'icon' => 'truck',
                        ],
                        [
                            'title' => ['en' => '60-Day Returns', 'ar' => 'إرجاع لمدة 60 يومًا'],
                            'body' => ['en' => 'Not happy? Return any item within 60 days for a full refund. No questions asked, no restocking fees.', 'ar' => 'غير راضٍ؟ أعد أي منتج خلال 60 يومًا لاسترداد كامل المبلغ. لا أسئلة، لا رسوم إعادة تخزين.'],
                            'icon' => 'rotate-ccw',
                        ],
                        [
                            'title' => ['en' => 'Price Match Guarantee', 'ar' => 'ضمان مطابقة السعر'],
                            'body' => ['en' => 'Found it cheaper elsewhere? We will match the price on the spot and refund the difference within 24 hours.', 'ar' => 'وجدته بسعر أقل في مكان آخر؟ سنطابق السعر فورًا ونرد الفرق خلال 24 ساعة.'],
                            'icon' => 'badge-percent',
                        ],
                        [
                            'title' => ['en' => 'Secure Checkout', 'ar' => 'دفع آمن'],
                            'body' => ['en' => '256-bit SSL encryption. We never store your full card details. PCI DSS Level 1 compliant.', 'ar' => 'تشفير SSL 256 بت. لا نخزن بيانات بطاقتك الكاملة. متوافق مع PCI DSS المستوى 1.'],
                            'icon' => 'lock',
                        ],
                        [
                            'title' => ['en' => '24/7 Customer Support', 'ar' => 'دعم عملاء 24/7'],
                            'body' => ['en' => 'Real humans, real fast. Average response time: under 2 minutes via chat, under 2 hours via email.', 'ar' => 'بشر حقيقيون، استجابة سريعة. متوسط وقت الرد: أقل من دقيقتين عبر الدردشة، أقل من ساعتين عبر البريد.'],
                            'icon' => 'message-square',
                        ],
                        [
                            'title' => ['en' => 'Order Protection', 'ar' => 'حماية الطلبات'],
                            'body' => ['en' => 'Every order is insured against loss, damage, or theft. If anything goes wrong, we reship or refund immediately.', 'ar' => 'كل طلب مؤمّن ضد الفقدان أو التلف أو السرقة. إذا حدث خطأ، نعيد الشحن أو نرد المبلغ فورًا.'],
                            'icon' => 'package',
                        ],
                    ],
                ],
                'settings' => ['layout' => 'grid', 'columns' => 3, 'icon_style' => 'outline'],
                'is_active' => true,
            ],
        );

        // ── Featured Categories Section ───────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'categories_home'],
            [
                'section_type' => 'category_grid',
                'sort_order' => 2,
                'title' => ['en' => 'Shop by Category', 'ar' => 'تسوق حسب التصنيف'],
                'subtitle' => [
                    'en' => 'Browse our curated collections — everything you need in one place.',
                    'ar' => 'تصفح مجموعاتنا المختارة — كل ما تحتاجه في مكان واحد.',
                ],
                'content' => [
                    'categories' => [
                        [
                            'id' => 'cat-electronics',
                            'name' => ['en' => 'Electronics', 'ar' => 'الإلكترونيات'],
                            'slug' => 'electronics',
                            'path' => ['en' => '/shop/category/electronics', 'ar' => '/ar/shop/category/electronics'],
                            'productCount' => 24,
                            'image' => null,
                        ],
                        [
                            'id' => 'cat-home-living',
                            'name' => ['en' => 'Home & Living', 'ar' => 'المنزل والمعيشة'],
                            'slug' => 'home-living',
                            'path' => ['en' => '/shop/category/home-living', 'ar' => '/ar/shop/category/home-living'],
                            'productCount' => 36,
                            'image' => null,
                        ],
                        [
                            'id' => 'cat-fashion',
                            'name' => ['en' => 'Fashion', 'ar' => 'الأزياء'],
                            'slug' => 'fashion',
                            'path' => ['en' => '/shop/category/fashion', 'ar' => '/ar/shop/category/fashion'],
                            'productCount' => 48,
                            'image' => null,
                        ],
                        [
                            'id' => 'cat-beauty-health',
                            'name' => ['en' => 'Beauty & Health', 'ar' => 'الجمال والصحة'],
                            'slug' => 'beauty-health',
                            'path' => ['en' => '/shop/category/beauty-health', 'ar' => '/ar/shop/category/beauty-health'],
                            'productCount' => 30,
                            'image' => null,
                        ],
                        [
                            'id' => 'cat-sports-outdoors',
                            'name' => ['en' => 'Sports & Outdoors', 'ar' => 'الرياضة والهواء الطلق'],
                            'slug' => 'sports-outdoors',
                            'path' => ['en' => '/shop/category/sports-outdoors', 'ar' => '/ar/shop/category/sports-outdoors'],
                            'productCount' => 18,
                            'image' => null,
                        ],
                        [
                            'id' => 'cat-toys-games',
                            'name' => ['en' => 'Toys & Games', 'ar' => 'الألعاب'],
                            'slug' => 'toys-games',
                            'path' => ['en' => '/shop/category/toys-games', 'ar' => '/ar/shop/category/toys-games'],
                            'productCount' => 22,
                            'image' => null,
                        ],
                    ],
                ],
                'settings' => ['layout' => 'grid', 'columns' => 3, 'card_style' => 'elevated'],
                'is_active' => true,
            ],
        );

        // ── Testimonials Section ──────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'testimonials_home'],
            [
                'section_type' => 'testimonials',
                'sort_order' => 3,
                'title' => ['en' => 'What our customers say', 'ar' => 'ماذا يقول عملاؤنا'],
                'subtitle' => [
                    'en' => 'Join 200,000+ happy customers who shop with confidence.',
                    'ar' => 'انضم إلى أكثر من 200,000 عميل سعيد يتسوقون بثقة.',
                ],
                'content' => [
                    'testimonials' => [
                        [
                            'quote' => ['en' => 'I was skeptical about the price-match guarantee, but they actually honored it. Saved $34 on a blender I was about to buy on Amazon. Customer for life.', 'ar' => 'كنت متشككًا في ضمان مطابقة السعر، لكنهم وفوا به بالفعل. وفرت 34 دولارًا على خلاط كنت سأشتريه من أمازون. عميل مدى الحياة.'],
                            'author' => ['en' => 'Sarah M.', 'ar' => 'سارة م.'],
                            'role' => ['en' => 'Verified Buyer', 'ar' => 'مشترٍ موثّق'],
                            'rating' => 5,
                            'avatar' => null,
                        ],
                        [
                            'quote' => ['en' => 'Ordered a jacket on Monday, it arrived Wednesday — across the country. The quality exceeded my expectations. The fit was exactly as described.', 'ar' => 'طلبت سترة يوم الاثنين، وصلت الأربعاء — عبر البلاد. الجودة فاقت توقعاتي. المقاس كان مطابقًا تمامًا للوصف.'],
                            'author' => ['en' => 'James K.', 'ar' => 'جيمس ك.'],
                            'role' => ['en' => 'Verified Buyer', 'ar' => 'مشترٍ موثّق'],
                            'rating' => 5,
                            'avatar' => null,
                        ],
                        [
                            'quote' => ['en' => 'I had to return a pair of shoes because they were too small. The process took literally 2 minutes online and the refund hit my account the next day. Incredible.', 'ar' => 'اضطررت لإرجاع حذاء لأنه كان صغيرًا جدًا. العملية استغرقت دقيقتين عبر الإنترنت والمبلغ عاد إلى حسابي في اليوم التالي. مذهل.'],
                            'author' => ['en' => 'Priya R.', 'ar' => 'بريا ر.'],
                            'role' => ['en' => 'Verified Buyer', 'ar' => 'مشترٍ موثّق'],
                            'rating' => 5,
                            'avatar' => null,
                        ],
                        [
                            'quote' => ['en' => 'The customer support chat helped me find the perfect laptop bag in under 5 minutes. They knew their products inside out. Rare these days.', 'ar' => 'ساعدتني الدردشة مع دعم العملاء في العثور على حقيبة لابتوب مثالية في أقل من 5 دقائق. يعرفون منتجاتهم جيدًا. نادر هذه الأيام.'],
                            'author' => ['en' => 'Mike T.', 'ar' => 'مايك ت.'],
                            'role' => ['en' => 'Verified Buyer', 'ar' => 'مشترٍ موثّق'],
                            'rating' => 5,
                            'avatar' => null,
                        ],
                        [
                            'quote' => ['en' => 'I have been a customer for 3 years. The quality has only improved, the prices have stayed fair, and the shipping keeps getting faster. Do not change a thing.', 'ar' => 'أنا عميل منذ 3 سنوات. الجودة تحسنت فقط، والأسعار بقيت عادلة، والشحن أصبح أسرع. لا تغيروا شيئًا.'],
                            'author' => ['en' => 'Elena V.', 'ar' => 'إلينا ف.'],
                            'role' => ['en' => 'VIP Member', 'ar' => 'عضو VIP'],
                            'rating' => 5,
                            'avatar' => null,
                        ],
                    ],
                ],
                'settings' => ['layout' => 'carousel', 'autoplay' => true, 'show_rating' => true],
                'is_active' => true,
            ],
        );

        // ── Newsletter / CTA Section ──────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'newsletter_home'],
            [
                'section_type' => 'cta',
                'sort_order' => 4,
                'title' => ['en' => 'Stay in the loop', 'ar' => 'ابق على اطلاع'],
                'subtitle' => [
                    'en' => 'Be the first to know about new drops, exclusive deals, and VIP-only sales. No spam — ever.',
                    'ar' => 'كن أول من يعرف عن المنتجات الجديدة والعروض الحصرية وتخفيضات VIP. لا بريد مزعج — أبدًا.',
                ],
                'content' => [
                    'ctas' => [
                        [
                            'label' => ['en' => 'Subscribe', 'ar' => 'اشترك'],
                            'url' => '/subscribe',
                            'style' => 'primary',
                        ],
                        [
                            'label' => ['en' => 'Follow on Instagram', 'ar' => 'تابعنا على إنستغرام'],
                            'url' => 'https://instagram.com/justshop',
                            'style' => 'outline',
                        ],
                    ],
                    'trust_badges' => [
                        ['en' => 'Free shipping over $49', 'ar' => 'شحن مجاني فوق $49'],
                        ['en' => '60-day returns', 'ar' => 'إرجاع 60 يومًا'],
                        ['en' => 'Price match guarantee', 'ar' => 'ضمان مطابقة السعر'],
                        ['en' => 'Secure checkout', 'ar' => 'دفع آمن'],
                    ],
                ],
                'settings' => ['layout' => 'split', 'background' => 'brand'],
                'is_active' => true,
            ],
        );
    }

    private function seedAboutPage(Store $store, User $creator): void
    {
        $page = StoreMarketingPage::query()->updateOrCreate(
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
                    'meta_title' => ['en' => 'About Us — JustShop Demo', 'ar' => 'من نحن — JustShop'],
                    'meta_description' => [
                        'en' => 'JustShop started in 2019 with one mission: premium-quality products at honest prices. Read our story, meet our team, and see why 200,000+ customers trust us.',
                        'ar' => 'بدأ JustShop عام 2019 بمهمة واحدة: منتجات عالية الجودة بأسعار عادلة. اقرأ قصتنا وتعرف على فريقنا.',
                    ],
                    'canonical_url' => config('app.url') . '/about-us',
                    'og_image' => null,
                    'og_title' => ['en' => 'About JustShop Demo', 'ar' => 'عن JustShop'],
                    'og_description' => [
                        'en' => 'From a small warehouse in 2019 to 8,000+ orders per month. This is our story.',
                        'ar' => 'من مستودع صغير في 2019 إلى أكثر من 8,000 طلب شهريًا. هذه قصتنا.',
                    ],
                    'robots' => 'index,follow',
                    'twitter_card' => 'summary_large_image',
                    'structured_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'AboutPage',
                        'mainEntity' => [
                            '@type' => 'Organization',
                            'name' => 'JustShop Demo',
                            'foundingDate' => '2019',
                            'description' => 'Premium-quality products at honest prices.',
                        ],
                    ],
                ],
                'template' => MarketingPageTemplateEnum::GENERIC,
                'sort_order' => 1,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ],
        );

        // ── Hero Section ──────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'hero_about'],
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
                        'cta_primary' => ['label' => 'Shop Best Sellers', 'url' => '/products'],
                        'cta_secondary' => ['label' => 'Meet Our Team', 'url' => '#team'],
                    ],
                    'ar' => [
                        'headline' => 'نحن هنا لنجعل المنتجات الرائعة في متناول الجميع',
                        'subheadline' => 'تأسست JustShop عام 2019 في مستودع صغير بهاجس واحد: تقديم منتجات عالية الجودة بأسعار عادلة وبسرعة.',
                        'mission' => 'مهمتنا بسيطة — إزالة الفجوة بين الجودة والقدرة على تحمّل التكاليف، ووضع العميل في مركز كل ما نفعله.',
                        'cta_primary' => ['label' => 'تسوق الأكثر مبيعًا', 'url' => '/products'],
                        'cta_secondary' => ['label' => 'تعرف على فريقنا', 'url' => '#team'],
                    ],
                ],
                'settings' => ['layout' => 'full'],
                'is_active' => true,
            ],
        );

        // ── Our Story Section ─────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'story_about'],
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

        // ── Quality Features Section ──────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'quality_about'],
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

        // ── Customer Promise Section ──────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'customer_promise_about'],
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

        // ── Sustainability Section ────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'sustainability_about'],
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

        // ── Team Section ──────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'team_about'],
            [
                'section_type' => 'gallery',
                'sort_order' => 5,
                'title' => ['en' => 'Meet the team', 'ar' => 'تعرف على الفريق'],
                'subtitle' => [
                    'en' => 'The people behind JustShop who make it all happen.',
                    'ar' => 'الأشخاص الذين يقفون وراء JustShop ويجعلون كل هذا ممكنًا.',
                ],
                'content' => [
                    'en' => [
                        'members' => [
                            ['name' => 'Sara Chen', 'role' => 'Co-Founder & CEO', 'bio' => 'Former supply chain analyst at Walmart. Obsessed with ethical sourcing and operational efficiency.', 'image' => null],
                            ['name' => 'Khalid Al-Rashid', 'role' => 'Co-Founder & COO', 'bio' => 'Ex-Amazon logistics manager. Built our fulfillment network from scratch.', 'image' => null],
                            ['name' => 'Maria Gonzalez', 'role' => 'Head of Product', 'bio' => '15 years in product development. Ensures every item meets our quality bar before it reaches you.', 'image' => null],
                            ['name' => 'David Kim', 'role' => 'VP of Engineering', 'bio' => 'Built e-commerce platforms serving 10M+ users. Leads our storefront and AI initiatives.', 'image' => null],
                            ['name' => 'Aisha Patel', 'role' => 'Customer Experience Director', 'bio' => 'Built a support team that averages 4.9★ CSAT. Answers chats herself every Friday.', 'image' => null],
                            ['name' => 'James Wilson', 'role' => 'Head of Sustainability', 'bio' => 'Environmental scientist turned supply chain optimizer. Made us carbon-neutral in 2024.', 'image' => null],
                        ],
                    ],
                    'ar' => [
                        'members' => [
                            ['name' => 'سارة تشن', 'role' => 'المؤسس المشارك والرئيس التنفيذي', 'bio' => 'محللة سلسلة توريد سابقة في Walmart. شغوفة بالتوريد الأخلاقي والكفاءة التشغيلية.', 'image' => null],
                            ['name' => 'خالد الرشيد', 'role' => 'المؤسس المشارك والمدير التنفيذي للعمليات', 'bio' => 'مدير لوجستي سابق في Amazon. بنى شبكة التوزيع لدينا من الصفر.', 'image' => null],
                            ['name' => 'ماريا غونزاليس', 'role' => 'رئيسة قسم المنتجات', 'bio' => '15 عامًا في تطوير المنتجات. تضمن أن كل عنصر يلبي معايير الجودة لدينا قبل أن يصل إليك.', 'image' => null],
                            ['name' => 'ديفيد كيم', 'role' => 'نائب الرئيس للهندسة', 'bio' => 'بنى منصات تجارة إلكترونية تخدم أكثر من 10 ملايين مستخدم.', 'image' => null],
                            ['name' => 'عائشة باتيل', 'role' => 'مديرة تجربة العملاء', 'bio' => 'بنت فريق دعم بمتوسط 4.9★ في رضا العملاء. تجيب على الدردشات بنفسها كل جمعة.', 'image' => null],
                            ['name' => 'جيمس ويلسون', 'role' => 'رئيس الاستدامة', 'bio' => 'عالم بيئة تحول إلى محسّن لسلسلة التوريد. جعلنا محايدين كربونيًا في 2024.', 'image' => null],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'grid', 'columns' => 3, 'show_bio' => true],
                'is_active' => true,
            ],
        );

        // ── Video Section ─────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'video_about'],
            [
                'section_type' => 'video',
                'sort_order' => 6,
                'title' => ['en' => 'See how we work', 'ar' => 'شاهد كيف نعمل'],
                'subtitle' => [
                    'en' => 'A behind-the-scenes look at our fulfillment center and quality control process.',
                    'ar' => 'نظرة من وراء الكواليس على مركز التوزيع لدينا وعملية مراقبة الجودة.',
                ],
                'content' => [
                    'en' => [
                        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                        'poster_url' => null,
                        'description' => 'Watch our team in action as they inspect, package, and ship your orders with care. Every product goes through our 3-stage quality check before it leaves our warehouse.',
                    ],
                    'ar' => [
                        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                        'poster_url' => null,
                        'description' => 'شاهد فريقنا أثناء فحص المنتجات وتعبئتها وشحن طلباتك بعناية. كل منتج يخضع لفحص جودة ثلاثي المراحل قبل مغادرة مستودعنا.',
                    ],
                ],
                'settings' => ['autoplay' => false, 'controls' => true],
                'is_active' => true,
            ],
        );

        // ── Pricing Section ───────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'pricing_about'],
            [
                'section_type' => 'pricing',
                'sort_order' => 7,
                'title' => ['en' => 'Join JustShop VIP', 'ar' => 'انضم إلى JustShop VIP'],
                'subtitle' => [
                    'en' => 'Get exclusive perks, early access to sales, and VIP-only pricing.',
                    'ar' => 'احصل على امتيازات حصرية ووصول مبكر للتخفيضات وأسعار VIP.',
                ],
                'content' => [
                    'en' => [
                        'plans' => [
                            [
                                'name' => 'Standard',
                                'description' => 'Perfect for occasional shoppers',
                                'price' => 'Free',
                                'currency' => '',
                                'period' => '',
                                'features' => [
                                    'Free shipping over $49',
                                    '60-day returns',
                                    'Price match guarantee',
                                    'Standard customer support',
                                    'Access to all products',
                                ],
                                'cta_label' => 'Shop Now',
                                'cta_url' => '/products',
                                'featured' => false,
                            ],
                            [
                                'name' => 'VIP',
                                'description' => 'Best for frequent shoppers',
                                'price' => '9.99',
                                'currency' => '$',
                                'period' => 'month',
                                'features' => [
                                    'Free shipping on all orders',
                                    '90-day returns',
                                    'Priority customer support',
                                    'Early access to sales (24h early)',
                                    'Exclusive VIP-only deals',
                                    '5% cashback on all purchases',
                                    'Birthday gift every year',
                                ],
                                'cta_label' => 'Start Free Trial',
                                'cta_url' => '/subscribe/vip',
                                'featured' => true,
                                'badge' => 'Most Popular',
                            ],
                            [
                                'name' => 'VIP Plus',
                                'description' => 'For power shoppers',
                                'price' => '19.99',
                                'currency' => '$',
                                'period' => 'month',
                                'features' => [
                                    'All VIP benefits',
                                    'Free express shipping',
                                    '120-day returns',
                                    'Dedicated account manager',
                                    'VIP hotline (call anytime)',
                                    '10% cashback on all purchases',
                                    'Exclusive product previews',
                                    'Free gift wrapping',
                                ],
                                'cta_label' => 'Go Premium',
                                'cta_url' => '/subscribe/vip-plus',
                                'featured' => false,
                            ],
                        ],
                    ],
                    'ar' => [
                        'plans' => [
                            [
                                'name' => 'عادي',
                                'description' => 'مثالي للمتسوقين العرضيين',
                                'price' => 'مجاني',
                                'currency' => '',
                                'period' => '',
                                'features' => [
                                    'شحن مجاني فوق $49',
                                    'إرجاع 60 يومًا',
                                    'ضمان مطابقة السعر',
                                    'دعم عملاء قياسي',
                                    'الوصول إلى جميع المنتجات',
                                ],
                                'cta_label' => 'تسوق الآن',
                                'cta_url' => '/products',
                                'featured' => false,
                            ],
                            [
                                'name' => 'VIP',
                                'description' => 'الأفضل للمتسوقين المتكررين',
                                'price' => '9.99',
                                'currency' => '$',
                                'period' => 'شهر',
                                'features' => [
                                    'شحن مجاني على جميع الطلبات',
                                    'إرجاع 90 يومًا',
                                    'دعم عملاء ذو أولوية',
                                    'وصول مبكر للتخفيضات (24 ساعة مبكرًا)',
                                    'عروض حصرية لـ VIP',
                                    'استرداد نقدي 5% على جميع المشتريات',
                                    'هدية عيد ميلاد كل عام',
                                ],
                                'cta_label' => 'ابدأ تجربة مجانية',
                                'cta_url' => '/subscribe/vip',
                                'featured' => true,
                                'badge' => 'الأكثر شعبية',
                            ],
                            [
                                'name' => 'VIP Plus',
                                'description' => 'للمتسوقين المكثفين',
                                'price' => '19.99',
                                'currency' => '$',
                                'period' => 'شهر',
                                'features' => [
                                    'جميع مزايا VIP',
                                    'شحن سريع مجاني',
                                    'إرجاع 120 يومًا',
                                    'مدير حساب مخصص',
                                    'خط VIP (اتصل في أي وقت)',
                                    'استرداد نقدي 10% على جميع المشتريات',
                                    'معاينات منتجات حصرية',
                                    'تغليف هدايا مجاني',
                                ],
                                'cta_label' => 'احصل على المميز',
                                'cta_url' => '/subscribe/vip-plus',
                                'featured' => false,
                            ],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'grid', 'highlight_featured' => true],
                'is_active' => true,
            ],
        );

        // ── CTA Section ───────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'cta_about'],
            [
                'section_type' => 'cta',
                'sort_order' => 8,
                'title' => ['en' => 'Ready to shop smarter?', 'ar' => 'مستعد للتسوق بذكاء؟'],
                'subtitle' => [
                    'en' => 'Join 200,000+ customers who switched to JustShop. Follow our store for VIP-only deals and early access to new drops.',
                    'ar' => 'انضم إلى أكثر من 200,000 عميل اختاروا JustShop. تابع متجرنا للحصول على عروض VIP وأسبقية الوصول إلى المنتجات الجديدة.',
                ],
                'content' => [
                    'en' => [
                        'ctas' => [
                            ['label' => 'Shop Best Sellers', 'url' => '/products', 'style' => 'primary'],
                            ['label' => 'New Arrivals', 'url' => '/products?sort=newest', 'style' => 'secondary'],
                            ['label' => 'Follow Our Store', 'url' => '#', 'style' => 'outline'],
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
                            ['label' => 'تسوق الأكثر مبيعًا', 'url' => '/products', 'style' => 'primary'],
                            ['label' => 'وصل حديثًا', 'url' => '/products?sort=newest', 'style' => 'secondary'],
                            ['label' => 'تابع متجرنا', 'url' => '#', 'style' => 'outline'],
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
    }

    private function seedContactPage(Store $store, User $creator): void
    {
        $page = StoreMarketingPage::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'slug->en' => 'contact',
            ],
            [
                'title' => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
                'slug' => ['en' => 'contact', 'ar' => 'contact-ar'],
                'excerpt' => [
                    'en' => 'We are here to help. Reach out to our team anytime.',
                    'ar' => 'نحن هنا للمساعدة. تواصل مع فريقنا في أي وقت.',
                ],
                'content' => [],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now()->subDays(2),
                'seo' => [
                    'meta_title' => ['en' => 'Contact Us — JustShop Demo', 'ar' => 'اتصل بنا — JustShop'],
                    'meta_description' => [
                        'en' => 'Get in touch with the JustShop team. We reply within 2 hours during business days.',
                        'ar' => 'تواصل مع فريق JustShop. نرد خلال ساعتين في أيام العمل.',
                    ],
                    'canonical_url' => config('app.url') . '/contact',
                    'robots' => 'index,follow',
                    'twitter_card' => 'summary_large_image',
                ],
                'template' => MarketingPageTemplateEnum::GENERIC,
                'sort_order' => 2,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ],
        );

        // ── Hero Section ──────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'hero_contact'],
            [
                'section_type' => 'hero',
                'sort_order' => 0,
                'title' => ['en' => 'We would love to hear from you', 'ar' => 'يسعدنا التواصل معك'],
                'subtitle' => [
                    'en' => 'Our team typically replies within 2 hours on business days. Whether you have a question, a suggestion, or just want to say hi — we are all ears.',
                    'ar' => 'فريقنا يرد عادة خلال ساعتين في أيام العمل. سواء كان لديك سؤال أو اقتراح أو تريد فقط إلقاء التحية — نحن هنا.',
                ],
                'content' => [
                    'en' => [
                        'headline' => 'We would love to hear from you',
                        'subheadline' => 'Our team typically replies within 2 hours on business days. Whether you have a question, a suggestion, or just want to say hi — we are all ears.',
                    ],
                    'ar' => [
                        'headline' => 'يسعدنا التواصل معك',
                        'subheadline' => 'فريقنا يرد عادة خلال ساعتين في أيام العمل. سواء كان لديك سؤال أو اقتراح أو تريد فقط إلقاء التحية — نحن هنا.',
                    ],
                ],
                'settings' => ['layout' => 'full', 'height' => 'medium'],
                'is_active' => true,
            ],
        );

        // ── Contact Methods Section ────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'methods_contact'],
            [
                'section_type' => 'features',
                'sort_order' => 1,
                'title' => ['en' => 'Ways to reach us', 'ar' => 'طرق التواصل معنا'],
                'subtitle' => [
                    'en' => 'Choose the method that works best for you.',
                    'ar' => 'اختر الطريقة التي تناسبك.',
                ],
                'content' => [
                    'en' => [
                        ['icon' => 'message-circle', 'title' => 'Live Chat', 'body' => 'Click the chat bubble at the bottom right. Average response time: under 2 minutes. Available 24/7.'],
                        ['icon' => 'mail', 'title' => 'Email Us', 'body' => 'support@justshop.test — we respond within 2 hours on business days, 6 hours on weekends.'],
                        ['icon' => 'phone', 'title' => 'Call Us', 'body' => '+1 (555) 123-4567 — Mon–Fri, 9 AM – 8 PM EST. Saturday 10 AM – 6 PM EST. Closed Sunday.'],
                        ['icon' => 'map-pin', 'title' => 'Visit Our Warehouse', 'body' => '1234 Commerce Blvd, Suite 200, Austin, TX 78701. Walk-in customer service: Mon–Fri, 10 AM – 5 PM.'],
                        ['icon' => 'twitter', 'title' => 'Social Media', 'body' => 'DM us on Instagram (@justshop) or X (@justshop). We monitor both channels during business hours.'],
                        ['icon' => 'file-text', 'title' => 'Help Center', 'body' => 'Browse our FAQ and knowledge base for instant answers to common questions — available 24/7.'],
                    ],
                    'ar' => [
                        ['icon' => 'message-circle', 'title' => 'الدردشة المباشرة', 'body' => 'اضغط على فقاعة الدردشة في الزاوية اليمنى السفلية. متوسط وقت الرد: أقل من دقيقتين. متاحة 24/7.'],
                        ['icon' => 'mail', 'title' => 'راسلنا بالبريد', 'body' => 'support@justshop.test — نرد خلال ساعتين في أيام العمل، 6 ساعات في عطلات نهاية الأسبوع.'],
                        ['icon' => 'phone', 'title' => 'اتصل بنا', 'body' => '+1 (555) 123-4567 — الإثنين–الجمعة، 9 صباحًا – 8 مساءً بتوقيت شرق الولايات المتحدة.'],
                        ['icon' => 'map-pin', 'title' => 'زر مستودعنا', 'body' => '1234 Commerce Blvd, Suite 200, Austin, TX 78701. خدمة العملاء المباشرة: الإثنين–الجمعة 10 صباحًا – 5 مساءً.'],
                        ['icon' => 'twitter', 'title' => 'وسائل التواصل الاجتماعي', 'body' => 'راسلنا عبر Instagram (@justshop) أو X (@justshop). نراقب كلا القناتين خلال ساعات العمل.'],
                        ['icon' => 'file-text', 'title' => 'مركز المساعدة', 'body' => 'تصفح الأسئلة الشائعة وقاعدة المعرفة للحصول على إجابات فورية — متاحة 24/7.'],
                    ],
                ],
                'settings' => ['layout' => 'grid', 'columns' => 3],
                'is_active' => true,
            ],
        );

        // ── FAQ Section ───────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'faq_contact'],
            [
                'section_type' => 'faq',
                'sort_order' => 2,
                'title' => ['en' => 'Frequently asked questions', 'ar' => 'الأسئلة الشائعة'],
                'subtitle' => [
                    'en' => 'Quick answers to the most common questions we receive.',
                    'ar' => 'إجابات سريعة على أكثر الأسئلة شيوعًا التي نتلقاها.',
                ],
                'content' => [
                    'en' => [
                        'items' => [
                            ['question' => 'How long does shipping take?', 'answer' => 'Standard shipping takes 3–7 business days within the continental US. Express shipping (2–3 business days) is available from $5.99. International shipping takes 7–14 business days.'],
                            ['question' => 'What is your return policy?', 'answer' => 'We offer a 60-day, no-questions-asked return policy. Simply initiate a return from your account page, print the prepaid label, and drop it off at any UPS location. Refunds are processed within 48 hours of us receiving the item.'],
                            ['question' => 'Do you price match?', 'answer' => 'Yes! If you find the same product (identical brand, model, and condition) at a lower price from a major retailer, we will match it. Contact our support team with a link and we will process the adjustment within 24 hours.'],
                            ['question' => 'Can I change or cancel my order?', 'answer' => 'You can modify or cancel your order within 1 hour of placing it. After that, our warehouse team may have already started packing it. Contact us immediately and we will do our best to accommodate.'],
                            ['question' => 'Is my payment information secure?', 'answer' => 'Absolutely. We use 256-bit SSL encryption and are PCI DSS Level 1 compliant. We never store your full card details. For additional security, we support Apple Pay, Google Pay, and PayPal.'],
                            ['question' => 'Do you ship internationally?', 'answer' => 'Yes, we ship to over 40 countries worldwide. International shipping rates and delivery times vary by destination. Duties and taxes are calculated at checkout and are the responsibility of the buyer.'],
                            ['question' => 'How do I track my order?', 'answer' => 'Once your order ships, you will receive a confirmation email with a tracking number. You can also view the latest status anytime from your account dashboard.'],
                        ],
                    ],
                    'ar' => [
                        'items' => [
                            ['question' => 'كم تستغرق مدة الشحن؟', 'answer' => 'الشحن العادي يستغرق 3–7 أيام عمل داخل الولايات المتحدة. الشحن السريع (2–3 أيام عمل) متوفر ابتداءً من $5.99. الشحن الدولي يستغرق 7–14 يوم عمل.'],
                            ['question' => 'ما هي سياسة الإرجاع؟', 'answer' => 'نقدم سياسة إرجاع لمدة 60 يومًا بدون طرح أسئلة. ببساطة ابدأ الإرجاع من صفحة حسابك، اطبع الملصق المدفوع مسبقًا، وسلّمه في أي موقع UPS. تتم معالجة المبالغ المستردة خلال 48 ساعة من استلامنا للمنتج.'],
                            ['question' => 'هل تطابقون الأسعار؟', 'answer' => 'نعم! إذا وجدت نفس المنتج بسعر أقل من تاجر تجزئة كبير، سنطابقه. اتصل بفريق الدعم وسنقوم بالتعديل خلال 24 ساعة.'],
                            ['question' => 'هل يمكنني تغيير أو إلغاء طلبي؟', 'answer' => 'يمكنك تعديل أو إلغاء طلبك خلال ساعة من تقديمه. بعد ذلك، قد يكون فريق المستودع قد بدأ في تجهيزه. اتصل بنا فورًا وسنبذل قصارى جهدنا.'],
                            ['question' => 'هل معلومات الدفع الخاصة بي آمنة؟', 'answer' => 'بالتأكيد. نستخدم تشفير SSL 256 بت ومتوافقون مع PCI DSS المستوى 1. لا نخزن أبدًا بيانات بطاقتك الكاملة.'],
                            ['question' => 'هل تشحنون دوليًا؟', 'answer' => 'نعم، نشحن إلى أكثر من 40 دولة حول العالم. تختلف تكاليف الشحن وأوقات التوصيل حسب الوجهة.'],
                            ['question' => 'كيف أتتبع طلبي؟', 'answer' => 'بمجرد شحن طلبك، ستتلقى بريدًا إلكترونيًا للتأكيد مع رقم تتبع. يمكنك أيضًا عرض الحالة في أي وقت من لوحة التحكم.'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'accordion', 'show_search' => true],
                'is_active' => true,
            ],
        );

        // ── CTA Section ───────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'cta_contact'],
            [
                'section_type' => 'cta',
                'sort_order' => 3,
                'title' => ['en' => 'Still have a question?', 'ar' => 'لا يزال لديك سؤال؟'],
                'subtitle' => [
                    'en' => 'Our support team is standing by. Start a chat or drop us an email — we are here to help.',
                    'ar' => 'فريق الدعم لدينا في انتظارك. ابدأ محادثة أو أرسل لنا بريدًا إلكترونيًا — نحن هنا للمساعدة.',
                ],
                'content' => [
                    'en' => [
                        'ctas' => [
                            ['label' => 'Start Live Chat', 'url' => '#', 'style' => 'primary'],
                            ['label' => 'Email Support', 'url' => 'mailto:support@justshop.test', 'style' => 'secondary'],
                        ],
                    ],
                    'ar' => [
                        'ctas' => [
                            ['label' => 'ابدأ الدردشة المباشرة', 'url' => '#', 'style' => 'primary'],
                            ['label' => 'راسل الدعم', 'url' => 'mailto:support@justshop.test', 'style' => 'secondary'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'centered', 'background' => 'muted'],
                'is_active' => true,
            ],
        );
    }

    private function seedFaqPage(Store $store, User $creator): void
    {
        $page = StoreMarketingPage::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'slug->en' => 'faq',
            ],
            [
                'title' => ['en' => 'Frequently Asked Questions', 'ar' => 'الأسئلة الشائعة'],
                'slug' => ['en' => 'faq', 'ar' => 'faq-ar'],
                'excerpt' => [
                    'en' => 'Everything you need to know about shopping at JustShop.',
                    'ar' => 'كل ما تحتاج معرفته عن التسوق في JustShop.',
                ],
                'content' => [],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now()->subDays(3),
                'seo' => [
                    'meta_title' => ['en' => 'FAQ — JustShop Demo', 'ar' => 'الأسئلة الشائعة — JustShop'],
                    'meta_description' => [
                        'en' => 'Find answers to common questions about shipping, returns, payments, orders, and more at JustShop.',
                        'ar' => 'اعثر على إجابات للأسئلة الشائعة حول الشحن والإرجاع والدفع والطلبات والمزيد في JustShop.',
                    ],
                    'canonical_url' => config('app.url') . '/faq',
                    'robots' => 'index,follow',
                    'twitter_card' => 'summary',
                ],
                'template' => MarketingPageTemplateEnum::GENERIC,
                'sort_order' => 3,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ],
        );

        // ── Hero Section ──────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'hero_faq'],
            [
                'section_type' => 'hero',
                'sort_order' => 0,
                'title' => ['en' => 'Frequently Asked Questions', 'ar' => 'الأسئلة الشائعة'],
                'subtitle' => [
                    'en' => 'Quick answers to everything you need to know before you buy.',
                    'ar' => 'إجابات سريعة لكل ما تحتاج معرفته قبل الشراء.',
                ],
                'content' => [
                    'en' => [
                        'headline' => 'Frequently Asked Questions',
                        'subheadline' => 'Quick answers to everything you need to know before you buy.',
                    ],
                    'ar' => [
                        'headline' => 'الأسئلة الشائعة',
                        'subheadline' => 'إجابات سريعة لكل ما تحتاج معرفته قبل الشراء.',
                    ],
                ],
                'settings' => ['layout' => 'full', 'height' => 'small'],
                'is_active' => true,
            ],
        );

        // ── Orders Section ─────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'orders_faq'],
            [
                'section_type' => 'faq',
                'sort_order' => 1,
                'title' => ['en' => 'Orders & Shipping', 'ar' => 'الطلبات والشحن'],
                'content' => [
                    'en' => [
                        'items' => [
                            ['question' => 'How do I place an order?', 'answer' => 'Simply browse our catalog, add items to your cart, and proceed to checkout. You can checkout as a guest or create an account for faster future purchases. We accept credit/debit cards, PayPal, Apple Pay, and Google Pay.'],
                            ['question' => 'Can I modify my order after placing it?', 'answer' => 'Yes — you have a 1-hour window to modify or cancel your order. Log into your account, go to "My Orders," and select the order you want to change. After 1 hour, contact our support team and we will try to accommodate.'],
                            ['question' => 'What shipping options do you offer?', 'answer' => 'We offer Standard (3–7 business days, free over $49), Express (2–3 business days, from $5.99), and International (7–14 business days, rates vary by destination).'],
                            ['question' => 'How can I track my package?', 'answer' => 'Once shipped, you will receive a tracking link via email. You can also track from your account dashboard. All carriers provide real-time updates.'],
                            ['question' => 'Do you ship to PO boxes?', 'answer' => 'Standard shipping supports PO boxes. Express and International shipments require a physical street address.'],
                        ],
                    ],
                    'ar' => [
                        'items' => [
                            ['question' => 'كيف أقدم طلبًا؟', 'answer' => 'تصفح كتالوجنا، أضف العناصر إلى عربة التسوق، وتابع إلى الدفع. يمكنك الدفع كضيف أو إنشاء حساب لعمليات شراء أسرع في المستقبل.'],
                            ['question' => 'هل يمكنني تعديل طلبي بعد تقديمه؟', 'answer' => 'نعم — لديك ساعة واحدة لتعديل أو إلغاء طلبك. سجل الدخول إلى حسابك، انتقل إلى "طلباتي"، واختر الطلب الذي تريد تغييره.'],
                            ['question' => 'ما خيارات الشحن المتاحة؟', 'answer' => 'نقدم الشحن العادي (3–7 أيام عمل، مجاني للطلبات فوق $49)، السريع (2–3 أيام عمل، من $5.99)، والدولي (7–14 يوم عمل).'],
                            ['question' => 'كيف أتتبع طردي؟', 'answer' => 'بمجرد الشحن، ستتلقى رابط التتبع عبر البريد الإلكتروني. يمكنك أيضًا التتبع من لوحة تحكم حسابك.'],
                            ['question' => 'هل تشحنون إلى الصناديق البريدية؟', 'answer' => 'الشحن العادي يدعم الصناديق البريدية. الشحن السريع والدولي يتطلبان عنوان شارع فعلي.'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'accordion'],
                'is_active' => true,
            ],
        );

        // ── Returns Section ───────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'returns_faq'],
            [
                'section_type' => 'faq',
                'sort_order' => 2,
                'title' => ['en' => 'Returns & Refunds', 'ar' => 'الإرجاع والاسترداد'],
                'content' => [
                    'en' => [
                        'items' => [
                            ['question' => 'What is your return policy?', 'answer' => 'We offer a 60-day, no-questions-asked return policy. Items must be in original condition with all tags and packaging. Some exclusions apply (underwear, swimwear, and personalized items).'],
                            ['question' => 'How do I start a return?', 'answer' => 'Log into your account, go to "My Orders," select the item you want to return, and click "Return." Print the prepaid shipping label, pack the item securely, and drop it off at any UPS location.'],
                            ['question' => 'How long do refunds take?', 'answer' => 'Refunds are processed within 48 hours of us receiving the returned item. It may take 3–5 additional business days for the refund to appear on your statement, depending on your payment provider.'],
                            ['question' => 'Do you offer exchanges?', 'answer' => 'We do not offer direct exchanges. However, the fastest option is to return the unwanted item and place a new order. Most customers find this quicker than waiting for an exchange.'],
                            ['question' => 'Who pays for return shipping?', 'answer' => 'We provide free return shipping for all domestic returns. For international returns, the customer is responsible for return shipping costs.'],
                        ],
                    ],
                    'ar' => [
                        'items' => [
                            ['question' => 'ما هي سياسة الإرجاع؟', 'answer' => 'نقدم سياسة إرجاع لمدة 60 يومًا بدون طرح أسئلة. يجب أن تكون العناصر في حالتها الأصلية مع جميع العلامات والتغليف.'],
                            ['question' => 'كيف أبدأ عملية الإرجاع؟', 'answer' => 'سجل الدخول إلى حسابك، انتقل إلى "طلباتي"، اختر العنصر الذي تريد إرجاعه، وانقر "إرجاع". اطبع ملصق الشحن المدفوع مسبقًا وسلّمه في أي موقع UPS.'],
                            ['question' => 'كم تستغرق المبالغ المستردة؟', 'answer' => 'تتم معالجة المبالغ المستردة خلال 48 ساعة من استلامنا للمنتج المعاد. قد يستغرق ظهور المبلغ في حسابك 3–5 أيام عمل إضافية.'],
                            ['question' => 'هل تقدمون الاستبدال؟', 'answer' => 'لا نقدم استبدالاً مباشرًا. لكن الخيار الأسرع هو إرجاع العنصر غير المرغوب فيه وتقديم طلب جديد.'],
                            ['question' => 'من يتحمل تكلفة شحن الإرجاع؟', 'answer' => 'نوفر شحن إرجاع مجاني لجميع الإرجاعات المحلية. بالنسبة للإرجاعات الدولية، يتحمل العميل تكاليف شحن الإرجاع.'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'accordion'],
                'is_active' => true,
            ],
        );

        // ── Payments Section ──────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'payments_faq'],
            [
                'section_type' => 'faq',
                'sort_order' => 3,
                'title' => ['en' => 'Payments & Pricing', 'ar' => 'الدفع والأسعار'],
                'content' => [
                    'en' => [
                        'items' => [
                            ['question' => 'What payment methods do you accept?', 'answer' => 'We accept Visa, Mastercard, American Express, Discover, PayPal, Apple Pay, and Google Pay. All payments are processed securely through Stripe.'],
                            ['question' => 'Is it safe to save my card on your site?', 'answer' => 'Yes. We use Stripe as our payment processor — your full card details never touch our servers. Stripe is PCI DSS Level 1 compliant, the highest security standard in the payments industry.'],
                            ['question' => 'Do you charge sales tax?', 'answer' => 'Sales tax is calculated and applied at checkout based on your shipping address. We are required to collect tax in all US states where we have economic nexus.'],
                            ['question' => 'How does the price match guarantee work?', 'answer' => 'If you find an identical product at a lower price from a major US retailer, contact us within 7 days of purchase. We will refund the difference. The item must be in stock and available for immediate purchase at the competitor.'],
                        ],
                    ],
                    'ar' => [
                        'items' => [
                            ['question' => 'ما طرق الدفع التي تقبلونها؟', 'answer' => 'نقبل Visa وMastercard وAmerican Express وDiscover وPayPal وApple Pay وGoogle Pay. جميع المدفوعات تتم معالجتها بشكل آمن عبر Stripe.'],
                            ['question' => 'هل من الآمن حفظ بطاقتي على موقعكم؟', 'answer' => 'نعم. نستخدم Stripe كمعالج دفع — تفاصيل بطاقتك الكاملة لا تصل إلى خوادمنا أبدًا. Stripe متوافق مع PCI DSS المستوى 1.'],
                            ['question' => 'هل تفرضون ضريبة مبيعات؟', 'answer' => 'يتم حساب ضريبة المبيعات وتطبيقها عند الدفع بناءً على عنوان الشحن الخاص بك.'],
                            ['question' => 'كيف يعمل ضمان مطابقة السعر؟', 'answer' => 'إذا وجدت منتجًا مماثلاً بسعر أقل من تاجر تجزئة أمريكي كبير، اتصل بنا خلال 7 أيام من الشراء. سنرد الفرق.'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'accordion'],
                'is_active' => true,
            ],
        );

        // ── Account Section ───────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'account_faq'],
            [
                'section_type' => 'faq',
                'sort_order' => 4,
                'title' => ['en' => 'Account & Security', 'ar' => 'الحساب والأمان'],
                'content' => [
                    'en' => [
                        'items' => [
                            ['question' => 'How do I create an account?', 'answer' => 'Click "Sign Up" at the top right of any page. You can register with your email address or sign in with Google. Creating an account takes under 30 seconds.'],
                            ['question' => 'I forgot my password — what do I do?', 'answer' => 'Click "Forgot Password" on the login page. We will send a password reset link to your email. The link expires in 60 minutes.'],
                            ['question' => 'How do I delete my account?', 'answer' => 'Go to Account Settings → Privacy → Delete Account. Please note that this action is irreversible and will delete your order history, saved addresses, and preferences.'],
                            ['question' => 'Do you share my personal data?', 'answer' => 'We never sell your personal data. We only share necessary information with our shipping carriers and payment processor to fulfill your orders. See our Privacy Policy for full details.'],
                        ],
                    ],
                    'ar' => [
                        'items' => [
                            ['question' => 'كيف أُنشئ حسابًا؟', 'answer' => 'انقر على "تسجيل" في الزاوية اليمنى العليا من أي صفحة. يمكنك التسجيل ببريدك الإلكتروني أو تسجيل الدخول عبر Google. إنشاء حساب يستغرق أقل من 30 ثانية.'],
                            ['question' => 'نسيت كلمة المرور — ماذا أفعل؟', 'answer' => 'انقر على "نسيت كلمة المرور" في صفحة تسجيل الدخول. سنرسل رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني. الرابط صالح لمدة 60 دقيقة.'],
                            ['question' => 'كيف أحذف حسابي؟', 'answer' => 'انتقل إلى إعدادات الحساب → الخصوصية → حذف الحساب. يرجى ملاحظة أن هذا الإجراء لا يمكن التراجع عنه.'],
                            ['question' => 'هل تشاركون بياناتي الشخصية؟', 'answer' => 'لا نبيع بياناتك الشخصية أبدًا. نشارك المعلومات الضرورية فقط مع شركات الشحن ومعالج الدفع لتنفيذ طلباتك.'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'accordion'],
                'is_active' => true,
            ],
        );

        // ── CTA Section ───────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'cta_faq'],
            [
                'section_type' => 'cta',
                'sort_order' => 5,
                'title' => ['en' => 'Did not find what you are looking for?', 'ar' => 'لم تجد ما تبحث عنه؟'],
                'subtitle' => [
                    'en' => 'Our support team is just a message away. We typically reply within 2 minutes on chat.',
                    'ar' => 'فريق الدعم لدينا على بعد رسالة واحدة. نرد عادة خلال دقيقتين في الدردشة.',
                ],
                'content' => [
                    'en' => [
                        'ctas' => [
                            ['label' => 'Chat with us', 'url' => '#', 'style' => 'primary'],
                            ['label' => 'Email support', 'url' => 'mailto:support@justshop.test', 'style' => 'secondary'],
                        ],
                    ],
                    'ar' => [
                        'ctas' => [
                            ['label' => 'تحدث معنا', 'url' => '#', 'style' => 'primary'],
                            ['label' => 'راسل الدعم', 'url' => 'mailto:support@justshop.test', 'style' => 'secondary'],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'centered'],
                'is_active' => true,
            ],
        );
    }

    private function seedSummerSalePage(Store $store, User $creator): void
    {
        $page = StoreMarketingPage::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'slug->en' => 'summer-sale',
            ],
            [
                'title' => ['en' => 'Summer Sale — Up to 40% Off', 'ar' => 'تخفيضات الصيف — خصم يصل إلى 40%'],
                'slug' => ['en' => 'summer-sale', 'ar' => 'summer-sale-ar'],
                'excerpt' => [
                    'en' => 'Our biggest sale of the year is here! Up to 40% off across thousands of items. Limited time only.',
                    'ar' => 'أكبر تخفيضات العام هنا! خصم يصل إلى 40% على آلاف المنتجات. لفترة محدودة.',
                ],
                'content' => [],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now()->subDays(1),
                'seo' => [
                    'meta_title' => ['en' => 'Summer Sale — Up to 40% Off | JustShop Demo', 'ar' => 'تخفيضات الصيف — خصم يصل إلى 40% | JustShop'],
                    'meta_description' => [
                        'en' => 'Shop our biggest sale of the year. Up to 40% off electronics, fashion, home, and more. Free shipping over $49. Ends soon!',
                        'ar' => 'تسوق أكبر تخفيضات العام. خصم يصل إلى 40% على الإلكترونيات والأزياء والمنزل والمزيد. شحن مجاني للطلبات فوق $49.',
                    ],
                    'canonical_url' => config('app.url') . '/summer-sale',
                    'robots' => 'index,follow',
                    'twitter_card' => 'summary_large_image',
                ],
                'template' => MarketingPageTemplateEnum::PROMOTION,
                'sort_order' => 4,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ],
        );

        // ── Hero Section ──────────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'hero_summer'],
            [
                'section_type' => 'hero',
                'sort_order' => 0,
                'title' => ['en' => 'Summer Sale — Up to 40% Off', 'ar' => 'تخفيضات الصيف — خصم يصل إلى 40%'],
                'subtitle' => [
                    'en' => 'Thousands of items marked down. Free shipping over $49. This is our biggest sale of the year — do not miss it.',
                    'ar' => 'آلاف المنتجات بأسعار مخفضة. شحن مجاني للطلبات فوق $49. هذه أكبر تخفيضات العام — لا تفوّتها.',
                ],
                'content' => [
                    'en' => [
                        'headline' => 'Summer Sale — Up to 40% Off',
                        'subheadline' => 'Thousands of items marked down. Free shipping over $49. This is our biggest sale of the year — do not miss it.',
                        'badge' => 'Limited Time Offer',
                        'cta_primary' => ['label' => 'Shop the Sale', 'url' => '/products?sale=1'],
                        'cta_secondary' => ['label' => 'View Categories', 'url' => '/categories'],
                        'background_style' => 'gradient',
                    ],
                    'ar' => [
                        'headline' => 'تخفيضات الصيف — خصم يصل إلى 40%',
                        'subheadline' => 'آلاف المنتجات بأسعار مخفضة. شحن مجاني للطلبات فوق $49. هذه أكبر تخفيضات العام — لا تفوّتها.',
                        'badge' => 'عرض لفترة محدودة',
                        'cta_primary' => ['label' => 'تسوق التخفيضات', 'url' => '/products?sale=1'],
                        'cta_secondary' => ['label' => 'عرض التصنيفات', 'url' => '/categories'],
                        'background_style' => 'gradient',
                    ],
                ],
                'settings' => ['layout' => 'full', 'height' => 'large', 'overlay' => true],
                'is_active' => true,
            ],
        );

        // ── Sale Categories Section ───────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'categories_summer'],
            [
                'section_type' => 'features',
                'sort_order' => 1,
                'title' => ['en' => 'Shop by department — all on sale', 'ar' => 'تسوق حسب القسم — الكل مخفّض'],
                'subtitle' => [
                    'en' => 'Every category has something for you. Discounts range from 15% to 40% off.',
                    'ar' => 'كل قسم لديه شيء لك. الخصومات تتراوح من 15% إلى 40%.',
                ],
                'content' => [
                    'en' => [
                        ['icon' => 'smartphone', 'title' => 'Electronics — Up to 30% Off', 'body' => 'Wireless earbuds, smartwatches, bluetooth speakers, portable chargers, and more. Top brands at unbeatable prices.'],
                        ['icon' => 'shirt', 'title' => 'Fashion — Up to 40% Off', 'body' => 'Summer collection: t-shirts, shorts, dresses, sandals, and sunglasses. Refresh your wardrobe without breaking the bank.'],
                        ['icon' => 'home', 'title' => 'Home & Living — Up to 35% Off', 'body' => 'Kitchen gadgets, decorative pieces, smart home devices, and organizational tools. Make your home summer-ready.'],
                        ['icon' => 'heart', 'title' => 'Beauty & Health — Up to 25% Off', 'body' => 'Skincare bundles, wellness kits, essential oils, and personal care essentials. Treat yourself this summer.'],
                        ['icon' => 'bike', 'title' => 'Sports & Outdoors — Up to 30% Off', 'body' => 'Camping gear, fitness equipment, hydration packs, and outdoor accessories. Get outside and save.'],
                        ['icon' => 'gift', 'title' => 'Flash Deals — Up to 50% Off', 'body' => 'Limited-quantity flash deals that change daily. Check back every morning for new surprises. Quantities are strictly limited.'],
                    ],
                    'ar' => [
                        ['icon' => 'smartphone', 'title' => 'الإلكترونيات — خصم يصل إلى 30%', 'body' => 'سماعات لاسلكية، ساعات ذكية، مكبرات بلوتوث، شواحن محمولة، والمزيد. أفضل العلامات التجارية بأسعار لا تُقبل المنافسة.'],
                        ['icon' => 'shirt', 'title' => 'الأزياء — خصم يصل إلى 40%', 'body' => 'مجموعة الصيف: تيشيرتات، شورتات، فساتين، صنادل، ونظارات شمسية. جدّد خزانة ملابسك دون إرهاق الميزانية.'],
                        ['icon' => 'home', 'title' => 'المنزل والمعيشة — خصم يصل إلى 35%', 'body' => 'أدوات المطبخ، قطع ديكور، أجهزة منزلية ذكية، وأدوات تنظيم. اجعل منزلك جاهزًا للصيف.'],
                        ['icon' => 'heart', 'title' => 'الجمال والصحة — خصم يصل إلى 25%', 'body' => 'مجموعات العناية بالبشرة، أطقم العافية، زيوت أساسية، وأساسيات العناية الشخصية.'],
                        ['icon' => 'bike', 'title' => 'الرياضة والهواء الطلق — خصم يصل إلى 30%', 'body' => 'معدات التخييم، أدوات اللياقة، حقائب الترطيب، وإكسسوارات الأنشطة الخارجية.'],
                        ['icon' => 'gift', 'title' => 'عروض خاطفة — خصم يصل إلى 50%', 'body' => 'عروض خاطفة بكميات محدودة تتغير يوميًا. تفقد الصفحة كل صباح لمفاجآت جديدة.'],
                    ],
                ],
                'settings' => ['layout' => 'grid', 'columns' => 3],
                'is_active' => true,
            ],
        );

        // ── Countdown / Urgency Section ───────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'urgency_summer'],
            [
                'section_type' => 'cta',
                'sort_order' => 2,
                'title' => ['en' => 'The clock is ticking', 'ar' => 'الوقت يداهمك'],
                'subtitle' => [
                    'en' => 'This sale ends in 7 days. Once it is gone, these prices are gone too.',
                    'ar' => 'تنتهي هذه التخفيضات بعد 7 أيام. بمجرد انتهائها، لن تعود هذه الأسعار.',
                ],
                'content' => [
                    'en' => [
                        'headline' => 'The clock is ticking',
                        'description' => 'This sale ends in 7 days. Once it is gone, these prices are gone too.',
                        'ctas' => [
                            ['label' => 'Shop Now — Save Up to 40%', 'url' => '/products?sale=1', 'style' => 'primary'],
                        ],
                        'urgency_badges' => [
                            '12,000+ items already claimed',
                            'Average discount: 27% off',
                            'Free shipping over $49',
                            '60-day returns on all sale items',
                        ],
                    ],
                    'ar' => [
                        'headline' => 'الوقت يداهمك',
                        'description' => 'تنتهي هذه التخفيضات بعد 7 أيام. بمجرد انتهائها، لن تعود هذه الأسعار.',
                        'ctas' => [
                            ['label' => 'تسوق الآن — وفر حتى 40%', 'url' => '/products?sale=1', 'style' => 'primary'],
                        ],
                        'urgency_badges' => [
                            'أكثر من 12,000 منتج تم شراؤها',
                            'متوسط الخصم: 27%',
                            'شحن مجاني فوق $49',
                            'إرجاع 60 يومًا على جميع منتجات التخفيضات',
                        ],
                    ],
                ],
                'settings' => ['layout' => 'centered', 'background' => 'brand'],
                'is_active' => true,
            ],
        );

        // ── Testimonials Section ──────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'testimonials_summer'],
            [
                'section_type' => 'testimonials',
                'sort_order' => 3,
                'title' => ['en' => 'What customers are saying about our sale', 'ar' => 'ماذا يقول العملاء عن تخفيضاتنا'],
                'subtitle' => [
                    'en' => 'Real reviews from real shoppers who scored big during our last sale.',
                    'ar' => 'تقييمات حقيقية من متسوقين حقيقيين استفادوا كثيرًا من تخفيضاتنا السابقة.',
                ],
                'content' => [
                    'en' => [
                        'testimonials' => [
                            [
                                'quote' => 'Got a $200 smartwatch for $120 during last year summer sale. Still wearing it daily. The deals are legit.',
                                'author' => 'Alex P.',
                                'role' => 'Verified Buyer',
                                'rating' => 5,
                            ],
                            [
                                'quote' => 'Stocked up on summer clothes for the whole family. Saved $180 total. The quality is way better than what you would get at the mall for the same price.',
                                'author' => 'Tanya W.',
                                'role' => 'Verified Buyer',
                                'rating' => 5,
                            ],
                            [
                                'quote' => 'The flash deals sell out fast — you have to be quick. Managed to grab a portable speaker at 50% off. Best impulse purchase ever.',
                                'author' => 'Carlos M.',
                                'role' => 'Verified Buyer',
                                'rating' => 5,
                            ],
                        ],
                    ],
                    'ar' => [
                        'testimonials' => [
                            [
                                'quote' => 'حصلت على ساعة ذكية بقيمة $200 مقابل $120 خلال تخفيضات الصيف الماضي. ما زلت أرتديها يوميًا. العروض حقيقية.',
                                'author' => 'أليكس ب.',
                                'role' => 'مشترٍ موثّق',
                                'rating' => 5,
                            ],
                            [
                                'quote' => 'تسوقت ملابس صيفية للعائلة كلها. وفرت $180 إجمالاً. الجودة أفضل بكثير مما ستجده في المركز التجاري بنفس السعر.',
                                'author' => 'تانيا و.',
                                'role' => 'مشترٍ موثّق',
                                'rating' => 5,
                            ],
                            [
                                'quote' => 'العروض الخاطفة تنفد بسرعة — يجب أن تكون سريعًا. تمكنت من الحصول على مكبر صوت محمول بخصم 50%. أفضل شراء مندفع على الإطلاق.',
                                'author' => 'كارلوس م.',
                                'role' => 'مشترٍ موثّق',
                                'rating' => 5,
                            ],
                        ],
                    ],
                ],
                'settings' => ['layout' => 'carousel', 'autoplay' => true],
                'is_active' => true,
            ],
        );

        // ── Final CTA Section ─────────────────────────────────────
        $page->sections()->updateOrCreate(
            ['store_id' => $store->id, 'identifier' => 'final_cta_summer'],
            [
                'section_type' => 'cta',
                'sort_order' => 4,
                'title' => ['en' => 'Do not wait — these deals will not last', 'ar' => 'لا تنتظر — هذه العروض لن تدوم'],
                'subtitle' => [
                    'en' => 'Thousands of shoppers are browsing the sale right now. Items are selling out fast.',
                    'ar' => 'آلاف المتسوقين يتصفحون التخفيضات الآن. المنتجات تنفد بسرعة.',
                ],
                'content' => [
                    'en' => [
                        'ctas' => [
                            ['label' => 'Shop the Summer Sale', 'url' => '/products?sale=1', 'style' => 'primary'],
                            ['label' => 'Browse New Arrivals', 'url' => '/products?sort=newest', 'style' => 'secondary'],
                        ],
                        'trust_badges' => [
                            'Free shipping over $49',
                            '60-day returns on sale items',
                            'Price match guarantee',
                            'Secure checkout',
                        ],
                    ],
                    'ar' => [
                        'ctas' => [
                            ['label' => 'تسوق تخفيضات الصيف', 'url' => '/products?sale=1', 'style' => 'primary'],
                            ['label' => 'تصفح الوافدين الجدد', 'url' => '/products?sort=newest', 'style' => 'secondary'],
                        ],
                        'trust_badges' => [
                            'شحن مجاني فوق $49',
                            'إرجاع 60 يومًا على منتجات التخفيضات',
                            'ضمان مطابقة السعر',
                            'دفع آمن',
                        ],
                    ],
                ],
                'settings' => ['layout' => 'centered', 'background' => 'dark'],
                'is_active' => true,
            ],
        );
    }
}
