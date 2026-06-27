<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PageTemplate;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DefaultTemplateSeeder extends Seeder
{
    /**
     * Create default templates for all stores
     */
    public function run(): void
    {
        $stores = Store::all();
        
        foreach ($stores as $store) {
            $this->createDefaultTemplates($store);
        }

        $this->command->info("Default templates created for {$stores->count()} stores!");
    }

    private function createDefaultTemplates(Store $store): void
    {
        // 1. Default Page Template (full header + footer)
        PageTemplate::updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'page.default',
            ],
            [
                'name' => 'Default Page',
                'type' => 'page',
                'description' => 'Standard page layout with full header and footer',
                'sections' => [
                    'header' => [
                        'type' => 'header',
                        'settings' => [
                            'menu' => 'main-menu',
                            'logo_position' => 'left',
                            'show_search' => true,
                        ],
                    ],
                    'main' => [
                        'type' => 'page_content',
                        'settings' => [],
                    ],
                    'footer' => [
                        'type' => 'footer',
                        'settings' => [
                            'menu' => 'footer-menu',
                            'show_social' => true,
                            'color_scheme' => 'default',
                        ],
                    ],
                ],
                'section_order' => ['header', 'main', 'footer'],
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // 2. Auth Page Template (minimal header + minimal footer)
        PageTemplate::updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'page.auth',
            ],
            [
                'name' => 'Auth Page',
                'type' => 'page',
                'description' => 'Clean layout for login, register, and password reset pages',
                'sections' => [
                    'header' => [
                        'type' => 'header',
                        'settings' => [
                            'menu' => 'main-menu',
                            'logo_position' => 'center',
                            'show_search' => false,
                        ],
                    ],
                    'main' => [
                        'type' => 'page_content',
                        'settings' => [],
                    ],
                    'footer' => [
                        'type' => 'footer-minimal',
                        'settings' => [
                            'menu' => 'footer-menu',
                        ],
                    ],
                ],
                'section_order' => ['header', 'main', 'footer'],
                'is_default' => false,
                'is_active' => true,
            ]
        );

        // 3. Legal Page Template (minimal header + legal footer)
        PageTemplate::updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'page.legal',
            ],
            [
                'name' => 'Legal Page',
                'type' => 'page',
                'description' => 'Simple layout for privacy policy, terms of service, etc.',
                'sections' => [
                    'header' => [
                        'type' => 'header',
                        'settings' => [
                            'menu' => 'main-menu',
                            'logo_position' => 'left',
                            'show_search' => false,
                        ],
                    ],
                    'main' => [
                        'type' => 'page_content',
                        'settings' => [],
                    ],
                    'footer' => [
                        'type' => 'footer-legal',
                        'settings' => [
                            'menu' => 'footer-menu',
                        ],
                    ],
                ],
                'section_order' => ['header', 'main', 'footer'],
                'is_default' => false,
                'is_active' => true,
            ]
        );

        // 4. Landing Page Template (hero + content + footer)
        PageTemplate::updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'page.landing',
            ],
            [
                'name' => 'Landing Page',
                'type' => 'page',
                'description' => 'Marketing landing page with hero banner',
                'sections' => [
                    'hero' => [
                        'type' => 'hero',
                        'settings' => [
                            'heading' => 'Welcome',
                            'text' => '',
                            'height' => 'large',
                        ],
                    ],
                    'main' => [
                        'type' => 'page_content',
                        'settings' => [],
                    ],
                    'footer' => [
                        'type' => 'footer',
                        'settings' => [
                            'menu' => 'footer-menu',
                            'show_social' => true,
                            'color_scheme' => 'default',
                        ],
                    ],
                ],
                'section_order' => ['hero', 'main', 'footer'],
                'is_default' => false,
                'is_active' => true,
            ]
        );
    }
}
