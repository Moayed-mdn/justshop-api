<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition()
    {
        $slug = fake()->unique()->slug(2);

        return [
            'slug' => $slug,
            'parent_id' => null,
        ];
    }
}