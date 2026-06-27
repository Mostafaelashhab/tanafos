<?php

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_sees_the_buyer_dashboard(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);

        $this->actingAs($buyer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Buyer dashboard'))
            ->assertSee(route('requests.create'));
    }

    public function test_merchant_sees_the_merchant_dashboard(): void
    {
        $merchant = User::factory()->merchant()->create();
        MerchantProfile::factory()->create(['user_id' => $merchant->id]);

        $this->actingAs($merchant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Merchant dashboard'));
    }
}
