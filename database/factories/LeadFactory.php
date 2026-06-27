<?php

namespace Database\Factories;

use App\Models\MerchantProfile;
use App\Models\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'merchant_profile_id' => MerchantProfile::factory(),
            'quality_score' => fake()->numberBetween(40, 100),
            'distance_km' => fake()->optional()->randomFloat(2, 0, 150),
            'status' => 'notified',
        ];
    }
}
