<?php

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_merchant_submits_a_pending_payment_without_getting_credits(): void
    {
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 0]);

        Volt::actingAs($merchant->user)->test('merchant.checkout', ['kind' => 'package', 'key' => 'starter'])
            ->call('chooseMethod', 'instapay')
            ->set('step', 3)
            ->set('sender_number', '01099999999')
            ->set('reference', 'TX123')
            ->call('submit')
            ->assertRedirect(route('merchant.billing'));

        $this->assertDatabaseHas('payments', [
            'merchant_profile_id' => $merchant->id,
            'kind' => 'package',
            'item_key' => 'starter',
            'method' => 'instapay',
            'amount' => 299,
            'status' => 'pending',
        ]);
        $this->assertSame(0, $merchant->fresh()->credits_balance); // no credits until approved
    }

    public function test_checkout_requires_a_sender_number(): void
    {
        $merchant = MerchantProfile::factory()->create();

        Volt::actingAs($merchant->user)->test('merchant.checkout', ['kind' => 'package', 'key' => 'starter'])
            ->set('method', 'instapay')
            ->set('step', 3)
            ->call('submit')
            ->assertHasErrors('sender_number');
    }

    public function test_admin_approval_applies_the_package_credits_and_notifies(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $admin = User::factory()->create(['type' => 'admin']);
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 0]);
        $payment = Payment::factory()->create([
            'merchant_profile_id' => $merchant->id,
            'kind' => 'package', 'item_key' => 'growth', 'amount' => 999, 'status' => 'pending',
        ]);

        Volt::actingAs($admin)->test('admin.payments')->call('approve', $payment->id);

        $this->assertSame('approved', $payment->fresh()->status);
        $this->assertSame(500, $merchant->fresh()->credits_balance);
        \Illuminate\Support\Facades\Notification::assertSentTo($merchant->user, \App\Notifications\PaymentApproved::class);
    }

    public function test_admin_rejection_grants_nothing(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 0]);
        $payment = Payment::factory()->create(['merchant_profile_id' => $merchant->id, 'status' => 'pending']);

        Volt::actingAs($admin)->test('admin.payments')->call('reject', $payment->id);

        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame(0, $merchant->fresh()->credits_balance);
    }

    public function test_plan_approval_sets_subscription_tier(): void
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $merchant = MerchantProfile::factory()->create(['subscription_tier' => 'none']);
        $payment = Payment::factory()->create([
            'merchant_profile_id' => $merchant->id,
            'kind' => 'plan', 'item_key' => 'gold', 'amount' => 799, 'status' => 'pending',
        ]);

        Volt::actingAs($admin)->test('admin.payments')->call('approve', $payment->id);

        $this->assertSame('gold', $merchant->fresh()->subscription_tier);
    }

    public function test_checkout_is_merchant_only(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $this->actingAs($buyer)->get(route('merchant.checkout', ['kind' => 'package', 'key' => 'starter']))->assertForbidden();
    }
}
