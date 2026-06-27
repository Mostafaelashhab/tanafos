<?php

namespace Database\Factories;

use App\Models\MerchantProfile;
use App\Models\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'merchant_profile_id' => MerchantProfile::factory(),
            'price' => fake()->numberBetween(1000, 80000),
            'currency' => 'EGP',
            'warranty' => fake()->optional()->randomElement(['6 شهور', 'سنة', 'سنتان']),
            'delivery_days' => fake()->optional()->numberBetween(1, 30),
            'description' => fake()->optional()->sentence(),
            'negotiation_enabled' => fake()->boolean(80),
            'status' => 'submitted',
        ];
    }
}
