<?php

namespace Database\Factories;

use App\Models\MerchantProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_profile_id' => MerchantProfile::factory(),
            'type' => 'purchase',
            'amount' => 100,
            'balance_after' => 100,
            'price' => 299,
            'description' => 'Purchased Starter (100 credits)',
        ];
    }
}
