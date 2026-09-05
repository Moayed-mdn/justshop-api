<?php

declare(strict_types=1);

namespace Database\Factories\Navigation;

use App\Models\Navigation\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Navigation\NavigationMenu>
 */
class NavigationMenuFactory extends Factory
{
    protected $model = NavigationMenu::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'name' => $name,
            'handle' => Str::slug($name),
            'description' => null,
            'settings' => [],
            'is_active' => true,
        ];
    }
}
