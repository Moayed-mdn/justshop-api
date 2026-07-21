<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlatformMarketingPagesSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'super@test.com')->first();
        $creatorId = $superAdmin?->id;

        $pages = [
            [
                'title' => json_encode(['en' => 'Pricing', 'ar' => 'التسعير']),
                'slug' => json_encode(['en' => 'pricing', 'ar' => 'pricing']),
                'excerpt' => json_encode(['en' => 'Simple, transparent pricing for businesses of all sizes', 'ar' => 'تسعير بسيط وشفاف للشركات من جميع الأحجام']),
                'content' => json_encode(['en' => $this->getPricingContent(), 'ar' => '']),
                'status' => 'published',
                'published_at' => now(),
                'seo' => json_encode([
                    'en' => [
                        'meta_title' => 'Pricing - Commerce Platform',
                        'meta_description' => 'Choose the perfect plan for your business. Start free, scale as you grow.',
                        'og_image' => null,
                        'robots' => 'index,follow',
                    ],
                    'ar' => [],
                ]),
                'template' => 'pricing',
                'sort_order' => 1,
            ],
            [
                'title' => json_encode(['en' => 'Features', 'ar' => 'المميزات']),
                'slug' => json_encode(['en' => 'features', 'ar' => 'features']),
                'excerpt' => json_encode(['en' => 'Everything you need to run a successful online store', 'ar' => 'كل ما تحتاجه لإدارة متجر إلكتروني ناجح']),
                'content' => json_encode(['en' => $this->getFeaturesContent(), 'ar' => '']),
                'status' => 'published',
                'published_at' => now(),
                'seo' => json_encode([
                    'en' => [
                        'meta_title' => 'Features - Commerce Platform',
                        'meta_description' => 'Powerful features for modern commerce. Inventory management, multi-currency, localization, and more.',
                        'og_image' => null,
                        'robots' => 'index,follow',
                    ],
                    'ar' => [],
                ]),
                'template' => 'features',
                'sort_order' => 2,
            ],
            [
                'title' => json_encode(['en' => 'About Us', 'ar' => 'من نحن']),
                'slug' => json_encode(['en' => 'about', 'ar' => 'about']),
                'excerpt' => json_encode(['en' => 'Building the future of global commerce', 'ar' => 'بناء مستقبل التجارة العالمية']),
                'content' => json_encode(['en' => $this->getAboutContent(), 'ar' => '']),
                'status' => 'published',
                'published_at' => now(),
                'seo' => json_encode([
                    'en' => [
                        'meta_title' => 'About Us - Commerce Platform',
                        'meta_description' => 'Learn about our mission to empower brands worldwide with best-in-class commerce technology.',
                        'og_image' => null,
                        'robots' => 'index,follow',
                    ],
                    'ar' => [],
                ]),
                'template' => 'about',
                'sort_order' => 3,
            ],
        ];

        foreach ($pages as $pageData) {
            PlatformMarketingPage::create([
                ...$pageData,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
            ]);
        }

        $this->command->info('✓ Created ' . count($pages) . ' platform marketing pages');
    }

    private function getPricingContent(): string
    {
        return <<<'HTML'
<h2>Choose Your Plan</h2>
<p>Start free, scale as you grow. No credit card required.</p>

<h3>Starter - $0/month</h3>
<ul>
  <li>Up to 100 products</li>
  <li>Single store</li>
  <li>Basic analytics</li>
  <li>Community support</li>
</ul>

<h3>Growth - $99/month</h3>
<ul>
  <li>Unlimited products</li>
  <li>Up to 5 stores</li>
  <li>Advanced analytics</li>
  <li>Email support</li>
  <li>Multi-currency</li>
</ul>

<h3>Enterprise - Custom</h3>
<ul>
  <li>Everything in Growth</li>
  <li>Unlimited stores</li>
  <li>Dedicated account manager</li>
  <li>24/7 priority support</li>
  <li>Custom integrations</li>
  <li>SLA guarantees</li>
</ul>
HTML;
    }

    private function getFeaturesContent(): string
    {
        return <<<'HTML'
<h2>Powerful Features for Modern Commerce</h2>

<h3>🌍 Global Inventory Management</h3>
<p>Sync inventory across multiple warehouses and regions automatically. Real-time stock tracking and automated reordering.</p>

<h3>🚀 Headless API</h3>
<p>Connect any frontend with our robust GraphQL and REST APIs. Build custom experiences for web, mobile, and IoT.</p>

<h3>📊 Real-time Analytics</h3>
<p>Gain deep insights into customer behavior and sales performance. Custom dashboards and exportable reports.</p>

<h3>🌐 Localization Engine</h3>
<p>Support for 50+ languages and localized checkout experiences. Automatic currency conversion and tax calculation.</p>

<h3>🛒 Multi-Store Management</h3>
<p>Manage multiple brands and stores from a single dashboard. Shared inventory or independent operations.</p>

<h3>🔒 Enterprise Security</h3>
<p>SOC 2 Type II certified. PCI DSS compliant. 99.99% uptime SLA.</p>
HTML;
    }

    private function getAboutContent(): string
    {
        return <<<'HTML'
<h2>About Commerce Platform</h2>

<p>We're building the future of global commerce. Our mission is to empower brands worldwide with best-in-class commerce technology.</p>

<h3>Our Story</h3>
<p>Founded in 2024, Commerce Platform was born from the frustration of managing complex, multi-region ecommerce operations. We knew there had to be a better way.</p>

<p>Today, we power over 15,000 merchants in 140 countries, processing $2.4B in annual GMV. Our platform handles everything from inventory management to checkout, localization to analytics.</p>

<h3>Our Values</h3>
<ul>
  <li><strong>Merchant First:</strong> Every decision starts with our merchants' success.</li>
  <li><strong>Global by Design:</strong> Built for international commerce from day one.</li>
  <li><strong>Open & Extensible:</strong> APIs and integrations are first-class citizens.</li>
  <li><strong>Reliability Matters:</strong> 99.99% uptime isn't a goal, it's a baseline.</li>
</ul>

<h3>Join Us</h3>
<p>We're always looking for talented people to join our team. Check out our careers page.</p>
HTML;
    }
}
