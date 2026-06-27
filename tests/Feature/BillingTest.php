<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Services\CreditService;
use App\Services\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PlanSeeder::class); // plans/packages are DB-managed
    }

    public function test_buying_a_package_adds_credits_and_logs_a_transaction(): void
    {
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 0]);

        app(CreditService::class)->purchasePackage($merchant, 'starter');

        $this->assertSame(100, $merchant->fresh()->credits_balance);
        $this->assertDatabaseHas('credit_transactions', [
            'merchant_profile_id' => $merchant->id,
            'type' => 'purchase',
            'amount' => 100,
            'balance_after' => 100,
            'price' => 299,
        ]);
    }

    public function test_pro_package_grants_unlimited_tier_without_a_balance(): void
    {
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 0, 'subscription_tier' => 'none']);

        app(CreditService::class)->purchasePackage($merchant, 'pro');

        $merchant->refresh();
        $this->assertSame('premium', $merchant->subscription_tier);
        $this->assertSame(0, $merchant->credits_balance);
    }

    public function test_subscribing_sets_the_tier(): void
    {
        $merchant = MerchantProfile::factory()->create(['subscription_tier' => 'none']);

        app(CreditService::class)->subscribe($merchant, 'gold');

        $this->assertSame('gold', $merchant->fresh()->subscription_tier);
    }

    public function test_submitting_an_offer_writes_a_consume_transaction(): void
    {
        $merchant = MerchantProfile::factory()->verified()->create(['credits_balance' => 5, 'subscription_tier' => 'none']);
        $request = Request::factory()->published()->create();
        $lead = Lead::factory()->create(['request_id' => $request->id, 'merchant_profile_id' => $merchant->id]);

        $offer = app(OfferService::class)->submit($merchant, $lead, ['price' => 9000]);

        $this->assertDatabaseHas('credit_transactions', [
            'merchant_profile_id' => $merchant->id,
            'type' => 'consume',
            'amount' => -1,
            'balance_after' => 4,
            'reference_type' => $offer->getMorphClass(),
            'reference_id' => $offer->id,
        ]);
    }

    public function test_billing_page_lists_packages(): void
    {
        $merchant = MerchantProfile::factory()->create(['credits_balance' => 0]);

        Volt::actingAs($merchant->user)->test('merchant.billing')
            ->assertOk()
            ->assertSee(route('merchant.checkout', ['kind' => 'package', 'key' => 'growth']));
    }

    public function test_billing_page_is_merchant_only(): void
    {
        $buyer = \App\Models\User::factory()->create(['type' => 'buyer']);

        $this->actingAs($buyer)->get(route('merchant.billing'))->assertForbidden();
    }
}
