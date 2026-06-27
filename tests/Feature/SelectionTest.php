<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\MerchantProfile;
use App\Models\Offer;
use App\Models\Request;
use App\Models\User;
use App\Services\SelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_a_winner_accepts_it_rejects_others_and_completes_request(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();
        $winner = Offer::factory()->create(['request_id' => $request->id, 'price' => 10000]);
        $loser = Offer::factory()->create(['request_id' => $request->id, 'price' => 20000]);

        app(SelectionService::class)->selectWinner($winner);

        $this->assertSame('accepted', $winner->fresh()->status);
        $this->assertSame('rejected', $loser->fresh()->status);

        $request->refresh();
        $this->assertSame('completed', $request->status);
        $this->assertSame($winner->id, $request->selected_offer_id);
    }

    public function test_selection_updates_win_rate_and_completed_deals(): void
    {
        $request = Request::factory()->published()->create();
        $winnerProfile = MerchantProfile::factory()->create(['win_rate' => 0, 'completed_deals' => 0]);
        $offer = Offer::factory()->create([
            'request_id' => $request->id,
            'merchant_profile_id' => $winnerProfile->id,
        ]);

        app(SelectionService::class)->selectWinner($offer);

        $winnerProfile->refresh();
        $this->assertSame('100.00', (string) $winnerProfile->win_rate);
        $this->assertSame(1, $winnerProfile->completed_deals);
    }

    public function test_selection_opens_a_conversation_with_the_winner(): void
    {
        $request = Request::factory()->published()->create();
        $offer = Offer::factory()->create(['request_id' => $request->id]);

        app(SelectionService::class)->selectWinner($offer);

        $this->assertDatabaseHas('conversations', [
            'request_id' => $request->id,
            'merchant_profile_id' => $offer->merchant_profile_id,
            'buyer_id' => $request->buyer_id,
        ]);
    }

    public function test_buyer_can_select_a_winner_from_the_request_page(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();
        $offer = Offer::factory()->create(['request_id' => $request->id]);

        Volt::actingAs($buyer)->test('requests.show', ['request' => $request])
            ->call('selectWinner', $offer->id);

        $this->assertSame('completed', $request->fresh()->status);
    }

    public function test_non_owner_cannot_select_a_winner(): void
    {
        $owner = User::factory()->create(['type' => 'buyer']);
        $stranger = User::factory()->create(['type' => 'buyer']);
        $request = Request::factory()->published()->for($owner, 'buyer')->create();
        $offer = Offer::factory()->create(['request_id' => $request->id]);

        $this->actingAs($stranger)
            ->get(route('requests.show', $request))
            ->assertForbidden();
    }

    public function test_buyer_can_review_after_completion_and_rating_recomputes(): void
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();
        $profile = MerchantProfile::factory()->create(['rating_avg' => 0]);
        $offer = Offer::factory()->create(['request_id' => $request->id, 'merchant_profile_id' => $profile->id]);

        app(SelectionService::class)->selectWinner($offer);
        $request->refresh();

        Volt::actingAs($buyer)->test('requests.show', ['request' => $request])
            ->set('rating', 4)
            ->set('comment', 'خدمة ممتازة')
            ->call('submitReview');

        $this->assertDatabaseHas('reviews', [
            'request_id' => $request->id,
            'merchant_profile_id' => $profile->id,
            'rating' => 4,
        ]);
        $this->assertSame('4.00', (string) $profile->fresh()->rating_avg);
    }
}
