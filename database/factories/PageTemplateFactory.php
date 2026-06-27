<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PageTemplate>
 */
class PageTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        
        return [
            'store_id' => Store::factory(),
            'name' => ucfirst($name),
            'handle' => 'page.' . Str::slug($name),
            'type' => 'page',
            'sections' => [
                'header' => [
                    'type' => 'header',
                    'settings' => [
                        'menu' => 'main-menu',
                        'show_search' => true,
                        'logo_position' => 'left',
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
                    ],
                ],
            ],
            'section_order' => ['header', 'main', 'footer'],
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the template is the default for its type.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Create a minimal template (auth pages).
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Minimal Template',
            'handle' => 'page.minimal',
            'sections' => [
                'header' => [
                    'type' => 'header',
                    'settings' => [
                        'menu' => 'main-menu',
                        'show_search' => false,
                        'logo_position' => 'center',
                    ],
                ],
                'main' => [
                    'type' => 'page_content',
                    'settings' => [],
                ],
                'footer' => [
                    'type' => 'footer-minimal',
                    'settings' => [
                        'menu' => 'minimal-footer',
                    ],
                ],
            ],
            'section_order' => ['header', 'main', 'footer'],
        ]);
    }

    /**
     * Create a landing page template with hero.
     */
    public function landing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Landing Page',
            'handle' => 'page.landing',
            'sections' => [
                'header' => [
                    'type' => 'header',
                    'settings' => [
                        'menu' => 'main-menu',
                        'show_search' => true,
                    ],
                ],
                'hero' => [
                    'type' => 'hero',
                    'settings' => [
                        'heading' => 'Welcome',
                        'subheading' => 'To our store',
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
                    ],
                ],
            ],
            'section_order' => ['header', 'hero', 'main', 'footer'],
        ]);
    }
}
