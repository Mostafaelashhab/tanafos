<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MerchantProfile>
 */
class MerchantProfileFactory extends Factory
{
    public function definition(): array
    {
        // Roughly the greater Cairo / Banha region for realistic geo matching.
        return [
            'user_id' => User::factory()->merchant(),
            'business_name' => fake()->company(),
            'description' => fake()->optional()->sentence(),
            'city' => fake()->randomElement(['القاهرة', 'بنها', 'الجيزة', 'طنطا', 'الإسكندرية']),
            'lat' => fake()->randomFloat(7, 30.0, 30.6),
            'lng' => fake()->randomFloat(7, 31.0, 31.4),
            'credits_balance' => fake()->numberBetween(0, 200),
            'subscription_tier' => fake()->randomElement(['none', 'basic', 'gold', 'premium']),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => now(),
        ]);
    }
}
