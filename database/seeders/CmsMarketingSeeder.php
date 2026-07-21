<?php

namespace Database\Seeders;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\MarketingPage\MarketingPageTypeEnum;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Cms\CmsDocument;
use App\Models\Cms\CmsDocumentSection;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Models\User;
use App\Support\System\FrontendUrlBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsMarketingSeeder extends Seeder
{
    private User $admin;

    public function run(): void
    {
        $this->admin = User::where('email', 'admin@example.com')->first() ?? User::first();

        // PART 1 & 2: Marketing Pages
        $this->seedHomePage();
        $this->seedAboutPage();
        $this->seedContactPage();
        $this->seedFeaturesPage();
        $this->seedEnterprisePage();
        $this->seedPricingPage();
        $this->seedDemoPage();
        $this->seedTemplatesPage();

        // PART 1 & 8: Blog System
        $this->seedBlogSystem();

        // PART 1 & 9: Documentation System
        $this->seedDocumentationSystem();
    }

    /**
     * HOME PAGE
     */
    private function seedHomePage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::HOME],
            [
                'title' => ['en' => 'The Commerce Platform for Next-Gen Brands', 'ar' => 'منصة التجارة لجيل القادم من العلامات التجارية'],
                'slug' => ['en' => 'home', 'ar' => 'الرئيسية'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'Build Your Global Commerce Empire', 'ar' => 'ابنِ إمبراطوريتك التجارية العالمية'],
                        subtitle: ['en' => 'A production-grade commerce engine for brands that scale. Multi-store, multi-currency, and fully CMS-driven.', 'ar' => 'محرك تجارة من الدرجة الإنتاجية للعلامات التجارية التي تتوسع. متاجر متعددة، عملات متعددة، ومدفوع بالكامل بنظام إدارة المحتوى.'],
                        badge: ['en' => 'v2.0 Now Live', 'ar' => 'الإصدار 2.0 متاح الآن'],
                        primaryCta: ['label' => ['en' => 'Start Free Trial', 'ar' => 'ابدأ الفترة التجريبية'], 'url' => '/register'],
                        secondaryCta: ['label' => ['en' => 'Watch Demo', 'ar' => 'شاهد العرض التجريبي'], 'url' => '/demo']
                    ),
                    'stats' => [
                        ['label' => ['en' => 'Active Merchants', 'ar' => 'تاجر نشط'], 'value' => '15,000+'],
                        ['label' => ['en' => 'Annual GMV', 'ar' => 'إجمالي حجم البضائع السنوي'], 'value' => '$2.4B'],
                        ['label' => ['en' => 'Uptime SLA', 'ar' => 'اتفاقية مستوى الخدمة'], 'value' => '99.99%'],
                        ['label' => ['en' => 'Countries', 'ar' => 'دولة'], 'value' => '140+'],
                    ],
                    'features' => [
                        [
                            'title' => ['en' => 'Global Inventory', 'ar' => 'المخزون العالمي'],
                            'description' => ['en' => 'Sync inventory across multiple warehouses and regions automatically.', 'ar' => 'مزامنة المخزون عبر مستودعات ومناطق متعددة تلقائيًا.'],
                            'icon' => 'inventory'
                        ],
                        [
                            'title' => ['en' => 'Headless API', 'ar' => 'واجهة برمجة تطبيقات Headless'],
                            'description' => ['en' => 'Connect any frontend with our robust GraphQL and REST APIs.', 'ar' => 'قم بتوصيل أي واجهة أمامية بواجهات برمجة تطبيقات GraphQL و REST القوية الخاصة بنا.'],
                            'icon' => 'api'
                        ],
                        [
                            'title' => ['en' => 'Real-time Analytics', 'ar' => 'تحليلات في الوقت الفعلي'],
                            'description' => ['en' => 'Gain deep insights into customer behavior and sales performance.', 'ar' => 'احصل على رؤى عميقة حول سلوك العملاء وأداء المبيعات.'],
                            'icon' => 'analytics'
                        ],
                        [
                            'title' => ['en' => 'Localization Engine', 'ar' => 'محرك التوطين'],
                            'description' => ['en' => 'Support for 50+ languages and localized checkout experiences.', 'ar' => 'دعم لأكثر من 50 لغة وتجارب دفع محلية.'],
                            'icon' => 'language'
                        ],
                    ],
                    'testimonials' => [
                        [
                            'quote' => ['en' => 'The most stable commerce backend we have ever used. Our migration was seamless.', 'ar' => 'أكثر خلفية تجارية استقرارًا استخدمناها على الإطلاق. كانت هجرتنا سلسة.'],
                            'author' => 'Sarah Chen',
                            'role' => 'CTO at EcoStyle',
                            'avatar' => 'https://i.pravatar.cc/150?u=sarah'
                        ],
                        [
                            'quote' => ['en' => 'Scaling to the Middle East was easy with their native Arabic support.', 'ar' => 'كان التوسع في الشرق الأوسط سهلاً بفضل دعمهم الأصلي للغة العربية.'],
                            'author' => 'Ahmed Al-Mansour',
                            'role' => 'Founder of Desert Bloom',
                            'avatar' => 'https://i.pravatar.cc/150?u=ahmed'
                        ]
                    ],
                    'faq' => $this->getSaaSFaqs(),
                    'cta' => $this->buildCtaSection(
                        title: ['en' => 'Ready to Scale Your Brand?', 'ar' => 'هل أنت جاهز لتوسيع علامتك التجارية؟'],
                        subtitle: ['en' => 'Join 15,000+ merchants building the future of commerce.', 'ar' => 'انضم إلى أكثر من 15,000 تاجر يبنون مستقبل التجارة.'],
                    ),
                    'company_info' => $this->getCompanyInfo(),
                    'footer' => $this->getFooterData(),
                    'social_links' => $this->getSocialLinks(),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Home', 'ar' => 'الرئيسية'],
                    description: ['en' => 'The leading enterprise commerce platform for global brands.', 'ar' => 'منصة التجارة الرائدة للمؤسسات والعلامات التجارية العالمية.'],
                    slug: 'home'
                )
            ]
        );
    }

    /**
     * ABOUT PAGE
     */
    private function seedAboutPage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::ABOUT],
            [
                'title' => ['en' => 'Our Mission & Story', 'ar' => 'مهمتنا وقصتنا'],
                'slug' => ['en' => 'about', 'ar' => 'من-نحن'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'Empowering Commerce Everywhere', 'ar' => 'تمكين التجارة في كل مكان'],
                        subtitle: ['en' => 'We started with a simple goal: to make enterprise-grade commerce accessible to every ambitious brand.', 'ar' => 'بدأنا بهدف بسيط: جعل التجارة على مستوى المؤسسات متاحة لكل علامة تجارية طموحة.'],
                    ),
                    'mission' => [
                        'title' => ['en' => 'Our Mission', 'ar' => 'مهمتنا'],
                        'content' => ['en' => 'To provide the most flexible and scalable commerce infrastructure on the planet.', 'ar' => 'توفير البنية التحتية التجارية الأكثر مرونة وقابلية للتوسع على هذا الكوكب.'],
                    ],
                    'story' => [
                        'title' => ['en' => 'How We Started', 'ar' => 'كيف بدأنا'],
                        'content' => ['en' => 'Founded in 2020, we realized that existing platforms were either too simple or too complex. We built the middle ground.', 'ar' => 'تأسست في عام 2020، أدركنا أن المنصات الحالية كانت إما بسيطة للغاية أو معقدة للغاية. لقد بنينا الحل الوسط.'],
                    ],
                    'values' => [
                        ['title' => ['en' => 'Innovation', 'ar' => 'الابتكار'], 'desc' => ['en' => 'We push the boundaries of what is possible in commerce.', 'ar' => 'نحن ندفع حدود ما هو ممكن في التجارة.']],
                        ['title' => ['en' => 'Reliability', 'ar' => 'الموثوقية'], 'desc' => ['en' => 'Our platform is built for 100% uptime.', 'ar' => 'تم بناء منصتنا لوقت تشغيل بنسبة 100%.']],
                        ['title' => ['en' => 'Customer First', 'ar' => 'العميل أولاً'], 'desc' => ['en' => 'Success is measured by our merchants growth.', 'ar' => 'يُقاس النجاح بنمو تجارنا.']],
                    ],
                    'team' => [
                        ['name' => 'John Doe', 'role' => 'CEO', 'avatar' => 'https://i.pravatar.cc/150?u=john'],
                        ['name' => 'Jane Smith', 'role' => 'CTO', 'avatar' => 'https://i.pravatar.cc/150?u=jane'],
                    ],
                    'milestones' => [
                        ['year' => '2020', 'event' => ['en' => 'Company Founded', 'ar' => 'تأسيس الشركة']],
                        ['year' => '2022', 'event' => ['en' => 'Series A Funding', 'ar' => 'تمويل الفئة أ']],
                        ['year' => '2024', 'event' => ['en' => '10k Merchants Reached', 'ar' => 'الوصول إلى 10 آلاف تاجر']],
                    ],
                    'cta' => $this->buildCtaSection(),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'About Us', 'ar' => 'من نحن'],
                    description: ['en' => 'Learn about our journey and the team behind the platform.', 'ar' => 'تعرف على رحلتنا والفريق الذي يقف وراء المنصة.'],
                    slug: 'about'
                )
            ]
        );
    }

    /**
     * CONTACT PAGE
     */
    private function seedContactPage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::CONTACT],
            [
                'title' => ['en' => 'Get in Touch', 'ar' => 'اتصل بنا'],
                'slug' => ['en' => 'contact', 'ar' => 'اتصل-بنا'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'We are Here to Help', 'ar' => 'نحن هنا للمساعدة'],
                        subtitle: ['en' => 'Have questions? Our team of experts is ready to assist you with your commerce journey.', 'ar' => 'لديك أسئلة؟ فريق الخبراء لدينا جاهز لمساعدتك في رحلتك التجارية.'],
                    ),
                    'contact_methods' => [
                        ['type' => 'email', 'label' => ['en' => 'Sales', 'ar' => 'المبيعات'], 'value' => 'sales@example.com'],
                        ['type' => 'email', 'label' => ['en' => 'Support', 'ar' => 'الدعم'], 'value' => 'support@example.com'],
                        ['type' => 'phone', 'label' => ['en' => 'Global', 'ar' => 'عالمي'], 'value' => '+1 (800) 123-4567'],
                    ],
                    'office_locations' => [
                        ['city' => ['en' => 'Dubai', 'ar' => 'دبي'], 'address' => 'Business Bay, Dubai, UAE'],
                        ['city' => ['en' => 'London', 'ar' => 'لندن'], 'address' => 'Shoreditch High St, London, UK'],
                        ['city' => ['en' => 'San Francisco', 'ar' => 'سان فرانسيسكو'], 'address' => 'Market St, SF, USA'],
                    ],
                    'support_hours' => [
                        ['days' => ['en' => 'Monday - Friday', 'ar' => 'الاثنين - الجمعة'], 'hours' => '24/7'],
                        ['days' => ['en' => 'Saturday - Sunday', 'ar' => 'السبت - الأحد'], 'hours' => 'Email only'],
                    ],
                    'faq' => $this->getSaaSFaqs(),
                    'cta' => $this->buildCtaSection(),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
                    description: ['en' => 'Reach out to our sales and support teams.', 'ar' => 'تواصل مع فرق المبيعات والدعم لدينا.'],
                    slug: 'contact'
                )
            ]
        );
    }

    /**
     * FEATURES PAGE
     */
    private function seedFeaturesPage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::FEATURES],
            [
                'title' => ['en' => 'Platform Features', 'ar' => 'مميزات المنصة'],
                'slug' => ['en' => 'features', 'ar' => 'المميزات'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'Everything You Need to Sell Online', 'ar' => 'كل ما تحتاجه للبيع عبر الإنترنت'],
                        subtitle: ['en' => 'Powerful tools designed to help you manage products, orders, and customers at scale.', 'ar' => 'أدوات قوية مصممة لمساعدتك في إدارة المنتجات والطلبات والعملاء على نطاق واسع.'],
                    ),
                    'feature_groups' => [
                        [
                            'title' => ['en' => 'Store Management', 'ar' => 'إدارة المتجر'],
                            'features' => [
                                ['title' => ['en' => 'Multi-store Support', 'ar' => 'دعم المتاجر المتعددة'], 'desc' => ['en' => 'Manage multiple storefronts from a single dashboard.', 'ar' => 'إدارة واجهات متاجر متعددة من لوحة تحكم واحدة.']],
                                ['title' => ['en' => 'Advanced Permissions', 'ar' => 'أذونات متقدمة'], 'desc' => ['en' => 'Granular role-based access control for your team.', 'ar' => 'التحكم في الوصول القائم على الأدوار لفريقك.']],
                            ]
                        ],
                        [
                            'title' => ['en' => 'Checkout & Payments', 'ar' => 'الدفع والمدفوعات'],
                            'features' => [
                                ['title' => ['en' => 'Global Payments', 'ar' => 'مدفوعات عالمية'], 'desc' => ['en' => 'Support for 100+ payment gateways worldwide.', 'ar' => 'دعم لأكثر من 100 بوابة دفع حول العالم.']],
                                ['title' => ['en' => 'Tax Automation', 'ar' => 'أتمتة الضرائب'], 'desc' => ['en' => 'Automatic tax calculations for every jurisdiction.', 'ar' => 'حسابات ضريبية تلقائية لكل ولاية قضائية.']],
                            ]
                        ]
                    ],
                    'integrations' => [
                        ['name' => 'Stripe', 'logo' => 'stripe'],
                        ['name' => 'PayPal', 'logo' => 'paypal'],
                        ['name' => 'ShipStation', 'logo' => 'shipstation'],
                        ['name' => 'Mailchimp', 'logo' => 'mailchimp'],
                    ],
                    'automation' => [
                        'title' => ['en' => 'Workflow Automation', 'ar' => 'أتمتة سير العمل'],
                        'desc' => ['en' => 'Automate repetitive tasks like order tagging and notification emails.', 'ar' => 'أتمتة المهام المتكررة مثل وسم الطلبات ورسائل البريد الإلكتروني للإشعارات.'],
                    ],
                    'analytics' => [
                        'title' => ['en' => 'Advanced Reporting', 'ar' => 'تقارير متقدمة'],
                        'desc' => ['en' => 'Real-time sales, inventory, and customer reports.', 'ar' => 'تقارير مبيعات ومخزون وعملاء في الوقت الفعلي.'],
                    ],
                    'mobile' => [
                        'title' => ['en' => 'Mobile Commerce', 'ar' => 'التجارة عبر الهاتف المحمول'],
                        'desc' => ['en' => 'PWA support and native mobile SDKs for iOS and Android.', 'ar' => 'دعم PWA وحزم أدوات تطوير البرمجيات للهاتف المحمول لنظامي iOS و Android.'],
                    ],
                    'security' => [
                        'title' => ['en' => 'Enterprise Security', 'ar' => 'أمن المؤسسات'],
                        'desc' => ['en' => 'SOC2, PCI-DSS Level 1, and GDPR compliant.', 'ar' => 'متوافق مع SOC2 و PCI-DSS المستوى 1 و GDPR.'],
                    ],
                    'cta' => $this->buildCtaSection(),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Features', 'ar' => 'المميزات'],
                    description: ['en' => 'Discover the powerful commerce features of our platform.', 'ar' => 'اكتشف ميزات التجارة القوية لمنصتنا.'],
                    slug: 'features'
                )
            ]
        );
    }

    /**
     * ENTERPRISE PAGE
     */
    private function seedEnterprisePage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::ENTERPRISE],
            [
                'title' => ['en' => 'Enterprise Solutions', 'ar' => 'حلول المؤسسات'],
                'slug' => ['en' => 'enterprise', 'ar' => 'المؤسسات'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'Scale Without Limits', 'ar' => 'توسع بدون حدود'],
                        subtitle: ['en' => 'The ultimate commerce infrastructure for global enterprises requiring high performance and dedicated support.', 'ar' => 'البنية التحتية التجارية النهائية للمؤسسات العالمية التي تتطلب أداءً عاليًا ودعمًا مخصصًا.'],
                    ),
                    'enterprise_features' => [
                        ['title' => ['en' => 'Dedicated Infrastructure', 'ar' => 'بنية تحتية مخصصة'], 'desc' => ['en' => 'Isolated instances for maximum performance and security.', 'ar' => 'مثيلات معزولة لأقصى قدر من الأداء والأمن.']],
                        ['title' => ['en' => 'Priority Support', 'ar' => 'دعم ذو أولوية'], 'desc' => ['en' => '24/7 dedicated account management and technical support.', 'ar' => 'إدارة حسابات ودعم فني مخصص على مدار الساعة طوال أيام الأسبوع.']],
                        ['title' => ['en' => 'SLA Guarantee', 'ar' => 'ضمان اتفاقية مستوى الخدمة'], 'desc' => ['en' => '99.99% uptime guarantee with financial backing.', 'ar' => 'ضمان وقت تشغيل بنسبة 99.99% مع دعم مالي.']],
                    ],
                    'compliance' => [
                        'title' => ['en' => 'Global Compliance', 'ar' => 'الامتثال العالمي'],
                        'badges' => ['SOC2', 'PCI-DSS', 'GDPR', 'HIPAA'],
                    ],
                    'scalability' => [
                        'title' => ['en' => 'Built for Scale', 'ar' => 'بنيت للتوسع'],
                        'content' => ['en' => 'Handle 100k+ orders per minute during peak sales events.', 'ar' => 'التعامل مع أكثر من 100 ألف طلب في الدقيقة خلال أحداث ذروة المبيعات.'],
                    ],
                    'infrastructure' => [
                        'title' => ['en' => 'Cloud Infrastructure', 'ar' => 'البنية التحتية السحابية'],
                        'content' => ['en' => 'Multi-region deployment across AWS and Google Cloud.', 'ar' => 'نشر متعدد المناطق عبر AWS و Google Cloud.'],
                    ],
                    'support' => [
                        'title' => ['en' => 'White-glove Onboarding', 'ar' => 'بدء استخدام متميز'],
                        'content' => ['en' => 'Dedicated migration team to move you from legacy systems.', 'ar' => 'فريق هجرة مخصص لنقلك من الأنظمة القديمة.'],
                    ],
                    'case_studies' => [
                        ['client' => 'Global Corp', 'result' => ['en' => '300% growth in 1 year', 'ar' => 'نمو بنسبة 300% في عام واحد']],
                        ['client' => 'Tech Giant', 'result' => ['en' => 'Reduced latency by 50%', 'ar' => 'تقليل زمن الوصول بنسبة 50%']],
                    ],
                    'cta' => $this->buildCtaSection(
                        title: ['en' => 'Talk to Our Enterprise Team', 'ar' => 'تحدث إلى فريق المؤسسات لدينا'],
                        subtitle: ['en' => 'Get a custom solution tailored to your business needs.', 'ar' => 'احصل على حل مخصص مصمم وفقًا لاحتياجات عملك.'],
                    ),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Enterprise Commerce', 'ar' => 'تجارة المؤسسات'],
                    description: ['en' => 'High-performance commerce for global enterprise brands.', 'ar' => 'تجارة عالية الأداء للعلامات التجارية للمؤسسات العالمية.'],
                    slug: 'enterprise'
                )
            ]
        );
    }

    /**
     * PRICING PAGE
     */
    private function seedPricingPage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::PRICING],
            [
                'title' => ['en' => 'Simple, Transparent Pricing', 'ar' => 'تسعير بسيط وشفاف'],
                'slug' => ['en' => 'pricing', 'ar' => 'الأسعار'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'Plans for Every Stage of Growth', 'ar' => 'خطط لكل مرحلة من مراحل النمو'],
                        subtitle: ['en' => 'From startups to global enterprises, we have a plan that fits your needs.', 'ar' => 'من الشركات الناشئة إلى المؤسسات العالمية، لدينا خطة تناسب احتياجاتك.'],
                    ),
                    'plans' => [
                        [
                            'name' => ['en' => 'Starter', 'ar' => 'البداية'],
                            'price_monthly' => '$29',
                            'price_yearly' => '$290',
                            'features' => [
                                ['en' => 'Up to 1,000 products', 'ar' => 'حتى 1,000 منتج'],
                                ['en' => 'Standard checkout', 'ar' => 'دفع قياسي'],
                                ['en' => 'Basic analytics', 'ar' => 'تحليلات أساسية'],
                            ],
                            'limits' => ['en' => '1 Store', 'ar' => 'متجر واحد'],
                            'cta' => ['en' => 'Start Trial', 'ar' => 'ابدأ الفترة التجريبية'],
                        ],
                        [
                            'name' => ['en' => 'Growth', 'ar' => 'النمو'],
                            'price_monthly' => '$79',
                            'price_yearly' => '$790',
                            'features' => [
                                ['en' => 'Unlimited products', 'ar' => 'منتجات غير محدودة'],
                                ['en' => 'Advanced CMS', 'ar' => 'نظام إدارة محتوى متقدم'],
                                ['en' => 'Priority support', 'ar' => 'دعم ذو أولوية'],
                            ],
                            'limits' => ['en' => '3 Stores', 'ar' => '3 متاجر'],
                            'cta' => ['en' => 'Get Started', 'ar' => 'ابدأ الآن'],
                            'featured' => true,
                        ],
                        [
                            'name' => ['en' => 'Enterprise', 'ar' => 'المؤسسات'],
                            'price_monthly' => 'Custom',
                            'price_yearly' => 'Custom',
                            'features' => [
                                ['en' => 'Dedicated infrastructure', 'ar' => 'بنية تحتية مخصصة'],
                                ['en' => 'White-glove onboarding', 'ar' => 'بدء استخدام متميز'],
                                ['en' => 'Custom integrations', 'ar' => 'تكاملات مخصصة'],
                            ],
                            'limits' => ['en' => 'Unlimited Stores', 'ar' => 'متاجر غير محدودة'],
                            'cta' => ['en' => 'Contact Sales', 'ar' => 'اتصل بالمبيعات'],
                        ]
                    ],
                    'faq' => $this->getSaaSFaqs(),
                    'comparison_table' => [
                        'headers' => [['en' => 'Feature', 'ar' => 'الميزة'], 'Starter', 'Growth', 'Enterprise'],
                        'rows' => [
                            [['en' => 'Products', 'ar' => 'المنتجات'], '1,000', 'Unlimited', 'Unlimited'],
                            [['en' => 'Transaction Fee', 'ar' => 'رسوم المعاملات'], '2.0%', '1.0%', '0.5%'],
                            [['en' => 'API Access', 'ar' => 'الوصول إلى API'], 'Limited', 'Full', 'Full + Priority'],
                        ]
                    ],
                    'cta' => $this->buildCtaSection(),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Pricing Plans', 'ar' => 'خطط التسعير'],
                    description: ['en' => 'Choose the right plan for your business.', 'ar' => 'اختر الخطة المناسبة لعملك.'],
                    slug: 'pricing'
                )
            ]
        );
    }

    /**
     * DEMO PAGE
     */
    private function seedDemoPage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::DEMO],
            [
                'title' => ['en' => 'Interactive Product Demo', 'ar' => 'عرض تجريبي تفاعلي للمنتج'],
                'slug' => ['en' => 'demo', 'ar' => 'العرض-التجريبي'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'See the Future of Commerce in Action', 'ar' => 'شاهد مستقبل التجارة في العمل'],
                        subtitle: ['en' => 'Take a guided tour of the most powerful commerce engine ever built. No credit card required.', 'ar' => 'قم بجولة إرشادية في أقوى محرك تجارة تم بناؤه على الإطلاق. لا يلزم وجود بطاقة ائتمان.'],
                        primaryCta: ['label' => ['en' => 'Start Interactive Demo', 'ar' => 'ابدأ العرض التفاعلي'], 'url' => '#walkthrough'],
                    ),
                    'product_walkthrough' => [
                        'title' => ['en' => 'How it Works', 'ar' => 'كيف يعمل'],
                        'steps' => [
                            [
                                'title' => ['en' => 'Launch Your Store', 'ar' => 'أطلق متجرك'],
                                'desc' => ['en' => 'Initialize your multi-tenant environment in seconds.', 'ar' => 'قم بتهيئة بيئة تعدد المستأجرين في ثوانٍ.'],
                                'image' => 'https://placehold.co/600x400/000/fff?text=Launch+Step'
                            ],
                            [
                                'title' => ['en' => 'Configure CMS', 'ar' => 'تكوين نظام إدارة المحتوى'],
                                'desc' => ['en' => 'Manage pages, blogs, and documentation with ease.', 'ar' => 'إدارة الصفحات والمدونات والوثائق بسهولة.'],
                                'image' => 'https://placehold.co/600x400/000/fff?text=CMS+Step'
                            ],
                            [
                                'title' => ['en' => 'Scale Globally', 'ar' => 'التوسع عالمياً'],
                                'desc' => ['en' => 'Enable multi-currency and localized storefronts.', 'ar' => 'تمكين واجهات المتاجر متعددة العملات والمحلية.'],
                                'image' => 'https://placehold.co/600x400/000/fff?text=Scale+Step'
                            ]
                        ]
                    ],
                    'dashboard_preview' => [
                        'title' => ['en' => 'Enterprise Dashboard', 'ar' => 'لوحة تحكم المؤسسات'],
                        'subtitle' => ['en' => 'Real-time control over your entire commerce empire.', 'ar' => 'تحكم في الوقت الفعلي في إمبراطوريتك التجارية بالكامل.'],
                        'features' => [
                            ['en' => 'Live Sales Stream', 'ar' => 'بث مباشر للمبيعات'],
                            ['en' => 'Inventory Alerts', 'ar' => 'تنبيهات المخزون'],
                            ['en' => 'Customer Segmentation', 'ar' => 'تقسيم العملاء'],
                            ['en' => 'Multi-store Switching', 'ar' => 'التبديل بين المتاجر'],
                        ],
                        'image' => 'https://placehold.co/1000x600/000/fff?text=Dashboard+Preview'
                    ],
                    'automation_features' => [
                        'title' => ['en' => 'Powerful Automation', 'ar' => 'أتمتة قوية'],
                        'features' => [
                            [
                                'title' => ['en' => 'Order Tagging', 'ar' => 'وسم الطلبات'],
                                'desc' => ['en' => 'Automatically tag orders based on fraud risk or region.', 'ar' => 'وسم الطلبات تلقائيًا بناءً على مخاطر الاحتيال أو المنطقة.'],
                            ],
                            [
                                'title' => ['en' => 'Inventory Sync', 'ar' => 'مزامنة المخزون'],
                                'desc' => ['en' => 'Keep stock levels accurate across all sales channels.', 'ar' => 'حافظ على دقة مستويات المخزون عبر جميع قنوات البيع.'],
                            ]
                        ]
                    ],
                    'analytics_preview' => [
                        'title' => ['en' => 'Deep Analytics', 'ar' => 'تحليلات عميقة'],
                        'desc' => ['en' => 'Gain insights into conversion rates and customer LTV.', 'ar' => 'احصل على رؤى حول معدلات التحويل وقيمة دورة حياة العميل.'],
                        'metrics' => [
                            ['label' => ['en' => 'Conversion Rate', 'ar' => 'معدل التحويل'], 'value' => '+24%'],
                            ['label' => ['en' => 'Avg. Order Value', 'ar' => 'متوسط قيمة الطلب'], 'value' => '$142'],
                        ]
                    ],
                    'integrations' => [
                        'title' => ['en' => 'Connect Everything', 'ar' => 'ربط كل شيء'],
                        'logos' => ['Stripe', 'PayPal', 'FedEx', 'Mailchimp', 'Slack', 'Zapier']
                    ],
                    'testimonials' => [
                        [
                            'quote' => ['en' => 'The demo convinced us in minutes. It is the fastest commerce engine we have tested.', 'ar' => 'أقنعنا العرض التجريبي في دقائق. إنه أسرع محرك تجارة اختبرناه.'],
                            'author' => 'Michael Ross',
                            'role' => 'Director of Operations at Trendify',
                        ]
                    ],
                    'faq' => [
                        [
                            'question' => ['en' => 'How long does the demo take?', 'ar' => 'كم من الوقت يستغرق العرض التجريبي؟'],
                            'answer' => ['en' => 'The guided walkthrough takes about 5 minutes, but you can explore for as long as you like.', 'ar' => 'يستغرق العرض التوضيحي الموجه حوالي 5 دقائق، ولكن يمكنك الاستكشاف للمدة التي تريدها.']
                        ],
                        [
                            'question' => ['en' => 'Do I need a credit card?', 'ar' => 'هل أحتاج إلى بطاقة ائتمان؟'],
                            'answer' => ['en' => 'No credit card is required to access the interactive demo.', 'ar' => 'لا يلزم وجود بطاقة ائتمان للوصول إلى العرض التجريبي التفاعلي.']
                        ],
                        [
                            'question' => ['en' => 'Is there technical support?', 'ar' => 'هل يوجد دعم فني؟'],
                            'answer' => ['en' => 'Yes, our team is available via live chat during the demo.', 'ar' => 'نعم، فريقنا متاح عبر الدردشة المباشرة أثناء العرض التجريبي.']
                        ]
                    ],
                    'cta' => $this->buildCtaSection(
                        title: ['en' => 'Experience it Today', 'ar' => 'جربه اليوم'],
                        subtitle: ['en' => 'Join 15,000+ merchants building the future.', 'ar' => 'انضم إلى أكثر من 15,000 تاجر يبنون المستقبل.'],
                    ),
                    'company_info' => $this->getCompanyInfo(),
                    'footer' => $this->getFooterData(),
                    'social_links' => $this->getSocialLinks(),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Product Demo', 'ar' => 'العرض التجريبي للمنتج'],
                    description: ['en' => 'Interactive product walkthrough of our commerce platform.', 'ar' => 'جولة تفاعلية في منتجات منصة التجارة الخاصة بنا.'],
                    slug: 'demo'
                )
            ]
        );
    }

    /**
     * TEMPLATES PAGE
     */
    private function seedTemplatesPage(): void
    {
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::TEMPLATES],
            [
                'title' => ['en' => 'Storefront Templates', 'ar' => 'قوالب واجهة المتجر'],
                'slug' => ['en' => 'templates', 'ar' => 'القوالب'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'Beautiful, High-Performance Templates', 'ar' => 'قوالب جميلة وعالية الأداء'],
                        subtitle: ['en' => 'Launch your store with world-class designs optimized for speed and conversion.', 'ar' => 'أطلق متجرك بتصاميم عالمية المستوى محسنة للسرعة والتحويل.'],
                    ),
                    'template_categories' => [
                        ['en' => 'Fashion', 'ar' => 'الموضة'],
                        ['en' => 'Electronics', 'ar' => 'الإلكترونيات'],
                        ['en' => 'Beauty', 'ar' => 'الجمال'],
                        ['en' => 'Furniture', 'ar' => 'الأثاث'],
                        ['en' => 'Luxury', 'ar' => 'الفخامة'],
                    ],
                    'featured_templates' => [
                        [
                            'title' => ['en' => 'Modern Fashion', 'ar' => 'الموضة الحديثة'],
                            'subtitle' => ['en' => 'Clean and elegant for apparel brands.', 'ar' => 'نظيف وأنيق لعلامات الملابس التجارية.'],
                            'desc' => ['en' => 'Optimized for high-resolution imagery and smooth transitions.', 'ar' => 'محسن للصور عالية الدقة والانتقالات السلسة.'],
                            'features' => ['RTL Support', 'Video Hero', 'Quick View'],
                            'image' => 'https://placehold.co/800x600/000/fff?text=Fashion+Template'
                        ],
                        [
                            'title' => ['en' => 'Minimal Electronics', 'ar' => 'الإلكترونيات البسيطة'],
                            'subtitle' => ['en' => 'Focused on tech specs and conversions.', 'ar' => 'يركز على المواصفات التقنية والتحويلات.'],
                            'desc' => ['en' => 'Engineered for large catalogs and complex product variations.', 'ar' => 'مصمم للكتالوجات الكبيرة وتنوع المنتجات المعقدة.'],
                            'features' => ['Spec Tables', 'Comparison Tool', 'Fast Search'],
                            'image' => 'https://placehold.co/800x600/000/fff?text=Electronics+Template'
                        ]
                    ],
                    'industry_use_cases' => [
                        [
                            'industry' => ['en' => 'Grocery', 'ar' => 'البقالة'],
                            'benefit' => ['en' => 'Fast re-ordering and local delivery integration.', 'ar' => 'إعادة طلب سريعة وتكامل التسليم المحلي.'],
                        ],
                        [
                            'industry' => ['en' => 'Digital Products', 'ar' => 'المنتجات الرقمية'],
                            'benefit' => ['en' => 'Instant downloads and secure license management.', 'ar' => 'تنزيلات فورية وإدارة آمنة للتراخيص.'],
                        ]
                    ],
                    'customization_features' => [
                        'title' => ['en' => 'Fully Customizable', 'ar' => 'قابل للتخصيص بالكامل'],
                        'features' => [
                            ['en' => 'Visual Editor', 'ar' => 'المحرر المرئي'],
                            ['en' => 'Custom CSS/JS', 'ar' => 'CSS/JS مخصص'],
                            ['en' => 'Brand Presets', 'ar' => 'قوالب العلامة التجارية'],
                        ]
                    ],
                    'storefront_capabilities' => [
                        'title' => ['en' => 'Native Capabilities', 'ar' => 'قدرات أصلية'],
                        'features' => [
                            ['en' => 'Multi-currency', 'ar' => 'عملات متعددة'],
                            ['en' => 'RTL/LTR Support', 'ar' => 'دعم RTL/LTR'],
                            ['en' => 'Advanced Filtering', 'ar' => 'تصفية متقدمة'],
                        ]
                    ],
                    'mobile_experience' => [
                        'title' => ['en' => 'Mobile-First Design', 'ar' => 'تصميم للهاتف المحمول أولاً'],
                        'desc' => ['en' => 'Touch-optimized interfaces that feel like native apps.', 'ar' => 'واجهات محسنة للمس تشبه التطبيقات الأصلية.'],
                        'image' => 'https://placehold.co/400x800/000/fff?text=Mobile+View'
                    ],
                    'performance_features' => [
                        'title' => ['en' => 'Lightning Fast', 'ar' => 'سريع كالبرق'],
                        'metrics' => [
                            ['label' => 'PageSpeed Score', 'value' => '100/100'],
                            ['label' => 'Time to Interactive', 'value' => '0.8s'],
                        ]
                    ],
                    'faq' => [
                        [
                            'question' => ['en' => 'Are templates responsive?', 'ar' => 'هل القوالب متجاوبة؟'],
                            'answer' => ['en' => 'Yes, every template is fully responsive and optimized for mobile, tablet, and desktop.', 'ar' => 'نعم، كل قالب متجاوب بالكامل ومحسن للهواتف المحمولة والأجهزة اللوحية وأجهزة الكمبيوتر المكتبية.']
                        ],
                        [
                            'question' => ['en' => 'Can I create my own theme?', 'ar' => 'هل يمكنني إنشاء سمة خاصة بي؟'],
                            'answer' => ['en' => 'While we provide templates, you can use our Headless API to build any frontend experience.', 'ar' => 'بينما نوفر القوالب، يمكنك استخدام واجهة برمجة تطبيقات Headless الخاصة بنا لبناء أي تجربة واجهة أمامية.']
                        ]
                    ],
                    'cta' => $this->buildCtaSection(
                        title: ['en' => 'Choose Your Template', 'ar' => 'اختر قالبك'],
                        subtitle: ['en' => 'Start building your dream store today.', 'ar' => 'ابدأ في بناء متجر أحلامك اليوم.'],
                    ),
                    'company_info' => $this->getCompanyInfo(),
                    'footer' => $this->getFooterData(),
                    'social_links' => $this->getSocialLinks(),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Storefront Templates', 'ar' => 'قوالب واجهة المتجر'],
                    description: ['en' => 'Browse our collection of high-performance ecommerce templates.', 'ar' => 'تصفح مجموعتنا من قوالب التجارة الإلكترونية عالية الأداء.'],
                    slug: 'templates'
                )
            ]
        );
    }


    /**
     * BLOG SYSTEM
     */
    private function seedBlogSystem(): void
    {
        // 1. Blog Landing Page
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::BLOG],
            [
                'title' => ['en' => 'Commerce Insights & Blog', 'ar' => 'رؤى التجارة والمدونة'],
                'slug' => ['en' => 'blog', 'ar' => 'المدونة'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'The Future of Commerce, Delivered', 'ar' => 'مستقبل التجارة، بين يديك'],
                        subtitle: ['en' => 'Insights, trends, and tutorials from the experts building the worlds best commerce engine.', 'ar' => 'رؤى واتجاهات ودروس من الخبراء الذين يبنون أفضل محرك تجارة في العالم.'],
                    ),
                    'categories' => [
                        ['en' => 'Product Updates', 'ar' => 'تحديثات المنتج'],
                        ['en' => 'Engineering', 'ar' => 'الهندسة'],
                        ['en' => 'Case Studies', 'ar' => 'دراسات الحالة'],
                        ['en' => 'Ecommerce Tips', 'ar' => 'نصائح التجارة الإلكترونية'],
                    ],
                    'cta' => $this->buildCtaSection(
                        title: ['en' => 'Subscribe to Our Newsletter', 'ar' => 'اشترك في نشرتنا الإخبارية'],
                        subtitle: ['en' => 'Get the latest commerce insights delivered to your inbox.', 'ar' => 'احصل على أحدث رؤى التجارة في بريدك الوارد.'],
                    ),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Blog', 'ar' => 'المدونة'],
                    description: ['en' => 'Latest news and insights from the commerce world.', 'ar' => 'أحدث الأخبار والرؤى من عالم التجارة.'],
                    slug: 'blog'
                )
            ]
        );

        // 2. Categories
        $categories = [
            ['en' => 'Growth', 'ar' => 'النمو'],
            ['en' => 'Technology', 'ar' => 'التكنولوجيا'],
            ['en' => 'Optimization', 'ar' => 'التحسين'],
            ['en' => 'Success Stories', 'ar' => 'قصص النجاح'],
        ];

        $categoryModels = [];
        foreach ($categories as $names) {
            $categoryModels[] = BlogCategory::updateOrCreate(
                ['slug->en' => Str::slug($names['en'])],
                [
                    'name' => $names,
                    'slug' => [
                        'en' => Str::slug($names['en']),
                        'ar' => Str::slug($names['ar']),
                    ],
                    'description' => [
                        'en' => "Expert insights about {$names['en']}.",
                        'ar' => "رؤى الخبراء حول {$names['ar']}.",
                    ],
                ]
            );
        }

        // 3. Tags
        $tags = [
            ['en' => 'Ecommerce', 'ar' => 'تجارة إلكترونية'],
            ['en' => 'SEO', 'ar' => 'تحسين محركات البحث'],
            ['en' => 'Performance', 'ar' => 'الأداء'],
            ['en' => 'Scaling', 'ar' => 'التوسع'],
        ];

        $tagModels = [];
        foreach ($tags as $names) {
            $tagModels[] = BlogTag::updateOrCreate(
                ['slug->en' => Str::slug($names['en'])],
                [
                    'name' => $names,
                    'slug' => [
                        'en' => Str::slug($names['en']),
                        'ar' => Str::slug($names['ar']),
                    ],
                ]
            );
        }

        // 4. Posts
        $posts = [
            [
                'title' => ['en' => '10 Ways to Scale Your Store to $10M GMV', 'ar' => '10 طرق لتوسيع متجرك إلى 10 ملايين دولار حجم بضائع'],
                'slug' => ['en' => 'scale-store-to-10m-gmv', 'ar' => 'توسيع-المتجر-إلى-10-ملايين'],
                'excerpt' => ['en' => 'Learn the proven strategies used by top merchants to scale their operations globally.', 'ar' => 'تعرف على الاستراتيجيات المثبتة التي يستخدمها كبار التجار لتوسيع عملياتهم عالميًا.'],
                'content' => ['en' => 'Full content for scaling store...', 'ar' => 'المحتوى الكامل لتوسيع المتجر...'],
                'category_idx' => 0,
            ],
            [
                'title' => ['en' => 'Optimizing Storefront Performance for SEO', 'ar' => 'تحسين أداء واجهة المتجر لمحركات البحث'],
                'slug' => ['en' => 'optimizing-storefront-performance-seo', 'ar' => 'تحسين-أداء-المتجر-للسيو'],
                'excerpt' => ['en' => 'Speed is a ranking factor. Discover how to make your store lightning fast.', 'ar' => 'السرعة هي عامل تصنيف. اكتشف كيفية جعل متجرك سريعًا كالبرق.'],
                'content' => ['en' => 'Full content for SEO optimization...', 'ar' => 'المحتوى الكامل لتحسين السيو...'],
                'category_idx' => 2,
            ],
            [
                'title' => ['en' => 'The Rise of Headless Commerce in 2026', 'ar' => 'صعود التجارة بلا رأس (Headless) في عام 2026'],
                'slug' => ['en' => 'rise-of-headless-commerce-2026', 'ar' => 'صعود-التجارة-بلا-رأس-2026'],
                'excerpt' => ['en' => 'Why brands are moving away from monolithic platforms to API-first architectures.', 'ar' => 'لماذا تنتقل العلامات التجارية من المنصات المتجانسة إلى معماريات تعتمد على واجهة برمجة التطبيقات أولاً.'],
                'content' => ['en' => 'Full content for headless commerce...', 'ar' => 'المحتوى الكامل للتجارة بلا رأس...'],
                'category_idx' => 1,
            ],
        ];

        foreach ($posts as $i => $p) {
            $post = BlogPost::updateOrCreate(
                ['slug->en' => $p['slug']['en']],
                [
                    'author_id' => $this->admin->id,
                    'blog_category_id' => $categoryModels[$p['category_idx']]->id,
                    'title' => $p['title'],
                    'slug' => $p['slug'],
                    'excerpt' => $p['excerpt'],
                    'content' => $p['content'],
                    'seo' => $this->buildSeo(
                        title: $p['title'],
                        description: $p['excerpt'],
                        slug: $p['slug']['en'],
                        type: 'article'
                    ),
                    'is_published' => true,
                    'published_at' => now()->subDays($i * 2),
                    'featured' => $i === 0,
                    'reading_time' => 5 + $i,
                    'created_by' => $this->admin->id,
                ]
            );
            $post->tags()->sync([$tagModels[0]->id, $tagModels[1]->id]);
        }
    }

    /**
     * DOCUMENTATION SYSTEM
     */
    private function seedDocumentationSystem(): void
    {
        // 1. Landing Page
        PlatformMarketingPage::updateOrCreate(
            ['type' => MarketingPageTypeEnum::DOCUMENTATION],
            [
                'title' => ['en' => 'Platform Documentation', 'ar' => 'وثائق المنصة'],
                'slug' => ['en' => 'docs', 'ar' => 'الوثائق'],
                'status' => MarketingPageStatusEnum::PUBLISHED,
                'published_at' => now(),
                'created_by' => $this->admin->id,
                'content' => [
                    'hero' => $this->buildHeroSection(
                        title: ['en' => 'Developer Hub & Docs', 'ar' => 'مركز المطورين والوثائق'],
                        subtitle: ['en' => 'Everything you need to build, integrate, and scale with our commerce APIs.', 'ar' => 'كل ما تحتاجه للبناء والتكامل والتوسع باستخدام واجهات برمجة تطبيقات التجارة الخاصة بنا.'],
                    ),
                    'categories' => [
                        ['title' => ['en' => 'Getting Started', 'ar' => 'البدء'], 'desc' => ['en' => 'Learn the basics and set up your first store.', 'ar' => 'تعرف على الأساسيات وقم بإعداد متجرك الأول.']],
                        ['title' => ['en' => 'API Reference', 'ar' => 'مرجع API'], 'desc' => ['en' => 'Detailed documentation for our REST and GraphQL APIs.', 'ar' => 'وثائق مفصلة لواجهات برمجة تطبيقات REST و GraphQL الخاصة بنا.']],
                    ],
                    'cta' => $this->buildCtaSection(
                        title: ['en' => 'Need Help?', 'ar' => 'هل تحتاج لمساعدة؟'],
                        subtitle: ['en' => 'Join our developer community on Discord.', 'ar' => 'انضم إلى مجتمع المطورين لدينا على ديسكورد.'],
                    ),
                ],
                'seo' => $this->buildSeo(
                    title: ['en' => 'Documentation', 'ar' => 'الوثائق'],
                    description: ['en' => 'Comprehensive documentation for developers and merchants.', 'ar' => 'وثائق شاملة للمطورين والتجار.'],
                    slug: 'docs'
                )
            ]
        );

        // 2. Sections
        $sections = [
            ['en' => 'Basics', 'ar' => 'الأساسيات'],
            ['en' => 'Store Management', 'ar' => 'إدارة المتجر'],
            ['en' => 'Advanced API', 'ar' => 'واجهة برمجة تطبيقات متقدمة'],
        ];

        $sectionModels = [];
        foreach ($sections as $i => $names) {
            $sectionModels[] = CmsDocumentSection::updateOrCreate(
                ['slug->en' => Str::slug($names['en'])],
                [
                    'title' => $names,
                    'slug' => [
                        'en' => Str::slug($names['en']),
                        'ar' => Str::slug($names['ar']),
                    ],
                    'sort_order' => $i,
                    'is_published' => true,
                ]
            );
        }

        // 3. Documents
        $docs = [
            [
                'title' => ['en' => 'Introduction to the Platform', 'ar' => 'مقدمة عن المنصة'],
                'slug' => ['en' => 'introduction', 'ar' => 'مقدمة'],
                'section_idx' => 0,
                'content' => [
                    'en' => "## Overview\nWelcome to the platform. This guide will help you understand our core architecture.\n\n### Core Concepts\n- Multi-tenancy\n- API-first\n- Localization",
                    'ar' => "## نظرة عامة\nمرحباً بك في المنصة. سيساعدك هذا الدليل على فهم معماريتنا الأساسية.\n\n### المفاهيم الأساسية\n- تعدد المستأجرين\n- واجهة برمجة التطبيقات أولاً\n- التوطين"
                ],
            ],
            [
                'title' => ['en' => 'Quick Start Guide', 'ar' => 'دليل البدء السريع'],
                'slug' => ['en' => 'quick-start', 'ar' => 'بدء-سريع'],
                'section_idx' => 0,
                'content' => [
                    'en' => "## Getting Started\nFollow these steps to launch your store.\n\n1. Create an account\n2. Configure settings\n3. Add products",
                    'ar' => "## البدء\nاتبع هذه الخطوات لإطلاق متجرك.\n\n1. إنشاء حساب\n2. تكوين الإعدادات\n3. إضافة المنتجات"
                ],
            ],
            [
                'title' => ['en' => 'API Authentication', 'ar' => 'مصادقة API'],
                'slug' => ['en' => 'api-auth', 'ar' => 'مصادقة-API'],
                'section_idx' => 2,
                'content' => [
                    'en' => "## Authentication\nUse Bearer tokens to authenticate your requests.\n\n```bash\ncurl -H 'Authorization: Bearer YOUR_TOKEN' ...\n```",
                    'ar' => "## المصادقة\nاستخدم رموز Bearer لمصادقة طلباتك.\n\n```bash\ncurl -H 'Authorization: Bearer YOUR_TOKEN' ...\n```"
                ],
            ],
        ];

        foreach ($docs as $i => $d) {
            CmsDocument::updateOrCreate(
                ['slug->en' => $d['slug']['en']],
                [
                    'section_id' => $sectionModels[$d['section_idx']]->id,
                    'title' => $d['title'],
                    'slug' => $d['slug'],
                    'content' => $d['content'],
                    'seo' => $this->buildSeo(
                        title: $d['title'],
                        description: ['en' => "Documentation for {$d['title']['en']}", 'ar' => "وثائق لـ {$d['title']['ar']}"],
                        slug: "docs/{$d['slug']['en']}"
                    ),
                    'sort_order' => $i,
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }

    /**
     * HELPERS
     */
    private function buildHeroSection(array $title, array $subtitle, ?array $badge = null, ?array $primaryCta = null, ?array $secondaryCta = null): array
    {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'badge' => $badge,
            'cta_primary' => $primaryCta,
            'cta_secondary' => $secondaryCta,
            'image' => 'https://placehold.co/1200x600/000/fff?text=Hero+Image',
        ];
    }

    private function buildCtaSection(?array $title = null, ?array $subtitle = null): array
    {
        return [
            'title' => $title ?? ['en' => 'Ready to grow?', 'ar' => 'جاهز للنمو؟'],
            'subtitle' => $subtitle ?? ['en' => 'Join thousands of satisfied customers.', 'ar' => 'انضم إلى آلاف العملاء الراضين.'],
            'primary_label' => ['en' => 'Get Started', 'ar' => 'ابدأ الآن'],
            'secondary_label' => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
            'primary_url' => FrontendUrlBuilder::build('/register'),
            'secondary_url' => FrontendUrlBuilder::build('/contact'),
        ];
    }

    private function buildSeo(array $title, array $description, string $slug, string $type = 'website'): array
    {
        return [
            'meta_title' => [
                'en' => "{$title['en']} | Commerce Platform",
                'ar' => "{$title['ar']} | منصة التجارة",
            ],
            'meta_description' => $description,
            'canonical_url' => FrontendUrlBuilder::build("/{$slug}"),
            'robots' => 'index, follow',
            'og' => [
                'title' => $title,
                'description' => $description,
                'image' => FrontendUrlBuilder::build('/og-image.jpg'),
                'type' => $type,
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'site' => '@commerce_platform',
            ],
            'alternates' => [
                'en' => FrontendUrlBuilder::build("/en/{$slug}"),
                'ar' => FrontendUrlBuilder::build("/ar/{$slug}"),
            ],
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => $type === 'article' ? 'BlogPosting' : 'WebPage',
                'name' => $title,
                'description' => $description,
            ],
        ];
    }

    private function getSaaSFaqs(): array
    {
        return [
            [
                'question' => ['en' => 'Is there a free trial?', 'ar' => 'هل هناك فترة تجريبية مجانية؟'],
                'answer' => ['en' => 'Yes, we offer a 14-day free trial for all new accounts.', 'ar' => 'نعم، نحن نقدم فترة تجريبية مجانية لمدة 14 يومًا لجميع الحسابات الجديدة.']
            ],
            [
                'question' => ['en' => 'Can I migrate from Shopify?', 'ar' => 'هل يمكنني الهجرة من شوبيفاي؟'],
                'answer' => ['en' => 'Absolutely. We have dedicated tools to import your products and orders.', 'ar' => 'بالتأكيد. لدينا أدوات مخصصة لاستيراد منتجاتك وطلباتك.']
            ],
            [
                'question' => ['en' => 'Do you support multi-currency?', 'ar' => 'هل تدعمون العملات المتعددة؟'],
                'answer' => ['en' => 'Yes, you can sell in 100+ currencies with automatic conversion.', 'ar' => 'نعم، يمكنك البيع بأكثر من 100 عملة مع تحويل تلقائي.']
            ],
        ];
    }

    private function getCompanyInfo(): array
    {
        return [
            'email' => 'contact@platform.com',
            'phone' => '+1 (800) 123-4567',
            'address' => '123 Commerce St, Tech City, 94103',
            'hours' => '9 AM - 6 PM PST',
            'map_url' => 'https://maps.google.com/...',
        ];
    }

    private function getFooterData(): array
    {
        return [
            'copyright' => ['en' => '© 2026 Commerce Platform Inc. All rights reserved.', 'ar' => '© 2026 شركة منصة التجارة. جميع الحقوق محفوظة.'],
            'tagline' => ['en' => 'The world\'s most flexible commerce engine.', 'ar' => 'محرك التجارة الأكثر مرونة في العالم.'],
            'links' => [
                ['label' => ['en' => 'Terms', 'ar' => 'الشروط'], 'url' => '/terms'],
                ['label' => ['en' => 'Privacy', 'ar' => 'الخصوصية'], 'url' => '/privacy'],
            ],
        ];
    }

    private function getSocialLinks(): array
    {
        return [
            ['platform' => 'twitter', 'url' => 'https://twitter.com/platform'],
            ['platform' => 'linkedin', 'url' => 'https://linkedin.com/company/platform'],
            ['platform' => 'github', 'url' => 'https://github.com/platform'],
        ];
    }
}
