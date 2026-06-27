<?php

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['type' => 'admin']);
    }

    public function test_admin_can_view_the_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('Admin'));
    }

    public function test_non_admin_is_forbidden_from_admin_area(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $merchant = User::factory()->merchant()->create();

        foreach (['admin.dashboard', 'admin.merchants', 'admin.users', 'admin.requests'] as $route) {
            $this->actingAs($buyer)->get(route($route))->assertForbidden();
            $this->actingAs($merchant)->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_can_verify_and_unverify_a_merchant(): void
    {
        $profile = MerchantProfile::factory()->create(['verified_at' => null]);

        $component = Volt::actingAs($this->admin())->test('admin.merchants');

        $component->call('toggleVerified', $profile->id);
        $this->assertNotNull($profile->fresh()->verified_at);

        $component->call('toggleVerified', $profile->id);
        $this->assertNull($profile->fresh()->verified_at);
    }

    public function test_admin_can_filter_users_by_type(): void
    {
        User::factory()->create(['type' => 'buyer', 'name' => 'Buyer Person']);
        User::factory()->merchant()->create(['name' => 'Merchant Person']);

        Volt::actingAs($this->admin())->test('admin.users')
            ->set('type', 'merchant')
            ->assertSee('Merchant Person')
            ->assertDontSee('Buyer Person');
    }
}
