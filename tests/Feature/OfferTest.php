<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientCreditsException;
use App\Models\Category;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Offer;
use App\Models\Request;
use App\Models\User;
use App\Services\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{MerchantProfile, Lead} a merchant with credits and a notified lead on an open request. */
    private function leadFor(array $merchantAttrs = []): array
    {
        $merchant = MerchantProfile::factory()->verified()->create(array_merge(['credits_balance' => 5, 'subscription_tier' => 'none'], $merchantAttrs));
        $request = Request::factory()->published()->create();
        $lead = Lead::factory()->create([
            'request_id' => $request->id,
            'merchant_profile_id' => $merchant->id,
            'status' => 'notified',
        ]);

        return [$merchant, $lead];
    }

    public function test_submitting_an_offer_consumes_one_credit_and_marks_lead_offered(): void
    {
        [$merchant, $lead] = $this->leadFor(['credits_balance' => 5]);

        $offer = app(OfferService::class)->submit($merchant, $lead, ['price' => 12000]);

        $this->assertSame('submitted', $offer->status);
        $this->assertSame(4, $merchant->fresh()->credits_balance);
        $lead = $lead->fresh();
        $this->assertSame('offered', $lead->status);
        $this->assertNotNull($lead->charged_at);
    }

    public function test_subscription_merchant_is_not_charged(): void
    {
        [$merchant, $lead] = $this->leadFor(['credits_balance' => 0, 'subscription_tier' => 'gold']);

        app(OfferService::class)->submit($merchant, $lead, ['price' => 9000]);

        $this->assertSame(0, $merchant->fresh()->credits_balance);
        $this->assertSame('offered', $lead->fresh()->status);
    }

    public function test_submission_blocked_without_credits(): void
    {
        [$merchant, $lead] = $this->leadFor(['credits_balance' => 0, 'subscription_tier' => 'none']);

        $this->expectException(InsufficientCreditsException::class);

        try {
            app(OfferService::class)->submit($merchant, $lead, ['price' => 9000]);
        } finally {
            $this->assertDatabaseCount('offers', 0);
            $this->assertSame('notified', $lead->fresh()->status);
        }
    }

    public function test_merchant_can_submit_an_offer_through_the_lead_page(): void
    {
        [$merchant, $lead] = $this->leadFor(['credits_balance' => 3]);

        Volt::actingAs($merchant->user)->test('merchant.leads.show', ['lead' => $lead])
            ->set('form.price', 15000)
            ->set('form.delivery_days', 5)
            ->set('form.warranty', 'سنة')
            ->call('submit')
            ->assertRedirect(route('merchant.leads.index'));

        $this->assertDatabaseHas('offers', [
            'request_id' => $lead->request_id,
            'merchant_profile_id' => $merchant->id,
            'price' => 15000,
        ]);
        $this->assertSame(2, $merchant->fresh()->credits_balance);
    }

    public function test_lead_page_shows_credit_error_when_broke(): void
    {
        [$merchant, $lead] = $this->leadFor(['credits_balance' => 0, 'subscription_tier' => 'none']);

        Volt::actingAs($merchant->user)->test('merchant.leads.show', ['lead' => $lead])
            ->set('form.price', 15000)
            ->call('submit')
            ->assertHasErrors('form.price');

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_offer_requires_a_positive_price(): void
    {
        [$merchant, $lead] = $this->leadFor();

        Volt::actingAs($merchant->user)->test('merchant.leads.show', ['lead' => $lead])
            ->set('form.price', 0)
            ->call('submit')
            ->assertHasErrors('form.price');
    }

    public function test_buyer_sees_offers_on_their_request_sorted_by_price(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();

        Offer::factory()->create(['request_id' => $request->id, 'price' => 30000]);
        Offer::factory()->create(['request_id' => $request->id, 'price' => 10000]);

        $component = Volt::actingAs($buyer)->test('requests.show', ['request' => $request])
            ->assertOk()
            ->assertSee('10000')
            ->assertSee('30000');

        // Lowest price first by default.
        $offers = $component->instance()->offers();
        $this->assertSame(10000, $offers->first()->price);
    }

    public function test_merchant_cannot_submit_twice_for_the_same_request(): void
    {
        [$merchant, $lead] = $this->leadFor(['credits_balance' => 5]);
        app(OfferService::class)->submit($merchant, $lead, ['price' => 11000]);

        // Policy now blocks a second submission via the page.
        $this->assertFalse($merchant->user->can('submitOffer', $lead->fresh()));
    }
}
