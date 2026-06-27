<?php

namespace Database\Factories;

use App\Models\MerchantProfile;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'buyer_id' => User::factory(),
            'merchant_profile_id' => MerchantProfile::factory(),
        ];
    }
}
