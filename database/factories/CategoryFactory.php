<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => Str::title($name),
            'name_ar' => $name,
            'slug' => Str::slug($name),
            'icon' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
