<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SectionSchema;
use Illuminate\Database\Seeder;

class SectionSchemaSeeder extends Seeder
{
    /**
     * Seed default section schemas for Shopify-style template system
     */
    public function run(): void
    {
        $schemas = [
            // Layout Sections
            [
                'type' => 'header',
                'name' => 'Header',
                'description' => 'Site header with logo, navigation, and search',
                'category' => 'layout',
                'settings' => [
                    [
                        'type' => 'link_list',
                        'id' => 'menu',
                        'label' => 'Menu',
                        'default' => 'main-menu',
                        'info' => 'Select which navigation menu to display',
                    ],
                    [
                        'type' => 'select',
                        'id' => 'logo_position',
                        'label' => 'Logo position',
                        'options' => [
                            ['value' => 'left', 'label' => 'Left'],
                            ['value' => 'center', 'label' => 'Center'],
                        ],
                        'default' => 'left',
                    ],
                    [
                        'type' => 'checkbox',
                        'id' => 'show_search',
                        'label' => 'Show search bar',
                        'default' => true,
                    ],
                ],
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'type' => 'footer',
                'name' => 'Footer',
                'description' => 'Site footer with navigation, social links, and copyright',
                'category' => 'layout',
                'settings' => [
                    [
                        'type' => 'link_list',
                        'id' => 'menu',
                        'label' => 'Footer menu',
                        'default' => 'footer-menu',
                        'info' => 'Select which navigation menu to display in footer',
                    ],
                    [
                        'type' => 'checkbox',
                        'id' => 'show_social',
                        'label' => 'Show social media links',
                        'default' => true,
                    ],
                    [
                        'type' => 'select',
                        'id' => 'color_scheme',
                        'label' => 'Color scheme',
                        'options' => [
                            ['value' => 'default', 'label' => 'Default'],
                            ['value' => 'dark', 'label' => 'Dark'],
                            ['value' => 'light', 'label' => 'Light'],
                        ],
                        'default' => 'default',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'type' => 'footer-minimal',
                'name' => 'Footer - Minimal',
                'description' => 'Minimal footer with limited links',
                'category' => 'layout',
                'settings' => [
                    [
                        'type' => 'link_list',
                        'id' => 'menu',
                        'label' => 'Footer menu',
                        'default' => 'footer-menu',
                        'info' => 'Select which navigation menu to display',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 21,
            ],
            [
                'type' => 'footer-legal',
                'name' => 'Footer - Legal',
                'description' => 'Footer with legal links (Privacy, Terms, etc.)',
                'category' => 'layout',
                'settings' => [
                    [
                        'type' => 'link_list',
                        'id' => 'menu',
                        'label' => 'Legal menu',
                        'default' => 'legal-footer',
                        'info' => 'Menu with legal links',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 22,
            ],

            // Content Sections
            [
                'type' => 'page_content',
                'name' => 'Page Content',
                'description' => 'Main page content area',
                'category' => 'content',
                'settings' => [],
                'is_active' => true,
                'sort_order' => 100,
            ],
            [
                'type' => 'hero',
                'name' => 'Hero Banner',
                'description' => 'Large hero banner with image and CTA',
                'category' => 'content',
                'settings' => [
                    [
                        'type' => 'text',
                        'id' => 'heading',
                        'label' => 'Heading',
                        'default' => 'Welcome',
                    ],
                    [
                        'type' => 'textarea',
                        'id' => 'text',
                        'label' => 'Text',
                        'default' => '',
                    ],
                    [
                        'type' => 'select',
                        'id' => 'height',
                        'label' => 'Section height',
                        'options' => [
                            ['value' => 'small', 'label' => 'Small'],
                            ['value' => 'medium', 'label' => 'Medium'],
                            ['value' => 'large', 'label' => 'Large'],
                        ],
                        'default' => 'medium',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 110,
            ],

            // Commerce Sections
            [
                'type' => 'product-grid',
                'name' => 'Product Grid',
                'description' => 'Grid of products',
                'category' => 'commerce',
                'settings' => [
                    [
                        'type' => 'text',
                        'id' => 'heading',
                        'label' => 'Heading',
                        'default' => 'Featured Products',
                    ],
                    [
                        'type' => 'number',
                        'id' => 'products_to_show',
                        'label' => 'Products to show',
                        'default' => 8,
                        'min' => 2,
                        'max' => 50,
                    ],
                    [
                        'type' => 'select',
                        'id' => 'columns_desktop',
                        'label' => 'Columns (desktop)',
                        'options' => [
                            ['value' => '2', 'label' => '2'],
                            ['value' => '3', 'label' => '3'],
                            ['value' => '4', 'label' => '4'],
                            ['value' => '5', 'label' => '5'],
                        ],
                        'default' => '4',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 200,
            ],
            [
                'type' => 'category-grid',
                'name' => 'Category Grid',
                'description' => 'Grid of product categories',
                'category' => 'commerce',
                'settings' => [
                    [
                        'type' => 'text',
                        'id' => 'heading',
                        'label' => 'Heading',
                        'default' => 'Shop by Category',
                    ],
                    [
                        'type' => 'select',
                        'id' => 'columns_desktop',
                        'label' => 'Columns (desktop)',
                        'options' => [
                            ['value' => '2', 'label' => '2'],
                            ['value' => '3', 'label' => '3'],
                            ['value' => '4', 'label' => '4'],
                        ],
                        'default' => '3',
                    ],
                ],
                'is_active' => true,
                'sort_order' => 210,
            ],
        ];

        foreach ($schemas as $schema) {
            SectionSchema::updateOrCreate(
                ['type' => $schema['type']],
                $schema
            );
        }

        $this->command->info('Section schemas seeded successfully!');
    }
}
