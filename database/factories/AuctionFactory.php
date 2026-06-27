<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Auction>
 */
class AuctionFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->numberBetween(500, 20000);

        return [
            'seller_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'condition' => fake()->randomElement(['new', 'used', 'any']),
            'city' => fake()->optional()->randomElement(['القاهرة', 'الجيزة', 'الإسكندرية']),
            'currency' => 'EGP',
            'starting_price' => $start,
            'bid_increment' => 50,
            'reserve_price' => null,
            'current_price' => $start,
            'bids_count' => 0,
            'highest_bid_id' => null,
            'winner_id' => null,
            'status' => 'live',
            'ends_at' => now()->addDays(3),
        ];
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => 'ended',
            'ends_at' => now()->subHour(),
            'closed_at' => now(),
        ]);
    }

    public function endingSoon(): static
    {
        return $this->state(fn () => ['ends_at' => now()->addMinutes(10)]);
    }
}
