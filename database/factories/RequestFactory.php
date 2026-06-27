<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Request>
 */
class RequestFactory extends Factory
{
    public function definition(): array
    {
        $min = fake()->numberBetween(500, 5000);

        return [
            'buyer_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'budget_min' => $min,
            'budget_max' => $min + fake()->numberBetween(500, 5000),
            'currency' => 'EGP',
            'city' => fake()->randomElement(['القاهرة', 'بنها', 'الجيزة']),
            'lat' => fake()->randomFloat(7, 30.0, 30.6),
            'lng' => fake()->randomFloat(7, 31.0, 31.4),
            'condition' => fake()->randomElement(Request::CONDITIONS),
            'urgency' => fake()->randomElement(Request::URGENCIES),
            'payment_method' => fake()->randomElement(Request::PAYMENT_METHODS),
            'warranty_required' => fake()->boolean(),
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'open', 'published_at' => now()]);
    }
}
