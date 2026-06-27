<?php

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_scales_with_completed_deals(): void
    {
        $this->assertSame('bronze', MerchantProfile::factory()->make(['completed_deals' => 0])->level()['key']);
        $this->assertSame('silver', MerchantProfile::factory()->make(['completed_deals' => 5])->level()['key']);
        $this->assertSame('gold', MerchantProfile::factory()->make(['completed_deals' => 25])->level()['key']);
        $this->assertSame('elite', MerchantProfile::factory()->make(['completed_deals' => 150])->level()['key']);
    }

    public function test_badges_reflect_performance(): void
    {
        $star = MerchantProfile::factory()->make([
            'verified_at' => now(),
            'rating_avg' => 4.8,
            'completed_deals' => 12,
            'response_minutes_avg' => 30,
        ]);

        $badges = $star->badges();
        $this->assertContains('verified', $badges);
        $this->assertContains('top_merchant', $badges);
        $this->assertContains('fast_responder', $badges);
        $this->assertContains('rising_star', $badges);

        $fresh = MerchantProfile::factory()->make([
            'verified_at' => null,
            'rating_avg' => 0,
            'completed_deals' => 0,
            'response_minutes_avg' => null,
        ]);
        $this->assertSame([], $fresh->badges());
    }

    public function test_leaderboard_ranks_verified_merchants_by_deals(): void
    {
        $top = MerchantProfile::factory()->verified()->create(['completed_deals' => 40, 'business_name' => 'Top Shop']);
        MerchantProfile::factory()->verified()->create(['completed_deals' => 3, 'business_name' => 'Small Shop']);
        MerchantProfile::factory()->create(['completed_deals' => 99, 'verified_at' => null, 'business_name' => 'Unverified']);

        $user = User::factory()->create(['type' => 'buyer']);

        $this->actingAs($user)->get(route('leaderboard'))
            ->assertOk()
            ->assertSeeInOrder(['Top Shop', 'Small Shop'])
            ->assertDontSee('Unverified');
    }
}
