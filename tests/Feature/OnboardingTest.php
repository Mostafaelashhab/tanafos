<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unonboarded_user_is_redirected_from_dashboard(): void
    {
        $user = User::factory()->create(['type' => 'buyer', 'onboarded_at' => null]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding'));
    }

    public function test_completing_onboarding_sets_the_timestamp_and_returns_to_dashboard(): void
    {
        $user = User::factory()->create(['type' => 'buyer', 'onboarded_at' => null]);

        $this->actingAs($user)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->onboarded_at);
    }

    public function test_onboarded_user_sees_the_dashboard(): void
    {
        $user = User::factory()->create(['type' => 'buyer', 'onboarded_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
