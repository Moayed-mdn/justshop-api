<?php

declare(strict_types=1);

namespace Database\Factories\Cms\Marketing\Store;

use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Store;
use App\Models\User;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreMarketingPageFactory extends Factory
{
    protected $model = StoreMarketingPage::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'title' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'excerpt' => $this->faker->paragraph,
            'content' => [],
            'status' => MarketingPageStatusEnum::DRAFT->value,
            'published_at' => null,
            'seo' => [
                'title' => $this->faker->sentence,
                'description' => $this->faker->paragraph,
            ],
            'template' => MarketingPageTemplateEnum::GENERIC->value,
            'page_template_id' => null,
            'sort_order' => 0,
            'is_homepage' => false,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
