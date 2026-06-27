<?php

namespace Tests\Feature;

use App\Models\CreditPackage;
use App\Models\MerchantProfile;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminPlansTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['type' => 'admin']);
    }

    public function test_admin_can_create_a_credit_package(): void
    {
        Volt::actingAs($this->admin())->test('admin.plans')
            ->call('start', 'package')
            ->set('key', 'mega')
            ->set('name', 'Mega')
            ->set('name_ar', 'ميجا')
            ->set('credits', 1000)
            ->set('price', 1500)
            ->call('save');

        $this->assertDatabaseHas('credit_packages', ['key' => 'mega', 'credits' => 1000, 'price' => 1500]);
    }

    public function test_admin_can_create_a_plan(): void
    {
        Volt::actingAs($this->admin())->test('admin.plans')
            ->call('start', 'plan')
            ->set('key', 'platinum')
            ->set('name', 'Platinum')
            ->set('name_ar', 'بلاتيني')
            ->set('tier', 'premium')
            ->set('price', 3500)
            ->call('save');

        $this->assertDatabaseHas('plans', ['key' => 'platinum', 'tier' => 'premium', 'price' => 3500]);
    }

    public function test_admin_can_toggle_and_delete_a_package(): void
    {
        $pkg = CreditPackage::create(['key' => 'tmp', 'name' => 'Tmp', 'name_ar' => 'مؤقت', 'credits' => 10, 'price' => 50]);

        $component = Volt::actingAs($this->admin())->test('admin.plans');
        $component->call('toggle', 'package', $pkg->id);
        $this->assertFalse($pkg->fresh()->is_active);

        $component->call('remove', 'package', $pkg->id);
        $this->assertModelMissing($pkg);
    }

    public function test_admin_can_grant_points_to_a_merchant(): void
    {
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 10]);

        Volt::actingAs($this->admin())->test('admin.merchants')
            ->call('openPoints', $merchant->id)
            ->set('pointsAmount', 50)
            ->call('grantPoints');

        $this->assertSame(60, $merchant->fresh()->credits_balance);
        $this->assertDatabaseHas('credit_transactions', [
            'merchant_profile_id' => $merchant->id,
            'type' => 'bonus',
            'amount' => 50,
        ]);
    }

    public function test_points_adjustment_cannot_drive_balance_negative(): void
    {
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 10]);

        Volt::actingAs($this->admin())->test('admin.merchants')
            ->call('openPoints', $merchant->id)
            ->set('pointsAmount', -50)
            ->call('grantPoints');

        $this->assertSame(0, $merchant->fresh()->credits_balance);
    }

    public function test_non_admin_cannot_access_plans(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $this->actingAs($buyer)->get(route('admin.plans'))->assertForbidden();
    }
}
