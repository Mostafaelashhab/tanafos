<?php

namespace Database\Factories;

use App\Models\MerchantProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_profile_id' => MerchantProfile::factory(),
            'kind' => 'package',
            'item_key' => 'starter',
            'method' => 'instapay',
            'amount' => 299,
            'sender_number' => '01000000000',
            'reference' => null,
            'proof_path' => null,
            'status' => 'pending',
        ];
    }
}
