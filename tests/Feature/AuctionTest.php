<?php

namespace Tests\Feature;

use App\Exceptions\BidException;
use App\Models\Auction;
use App\Models\User;
use App\Notifications\AuctionWon;
use App\Notifications\Outbid;
use App\Services\AuctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuctionTest extends TestCase
{
    use RefreshDatabase;

    private function liveAuction(array $attrs = []): Auction
    {
        return Auction::factory()->create(array_merge([
            'starting_price' => 1000, 'bid_increment' => 100, 'current_price' => 1000,
            'status' => 'live', 'ends_at' => now()->addDay(),
        ], $attrs));
    }

    public function test_first_bid_must_meet_starting_price_then_increments(): void
    {
        $auction = $this->liveAuction();
        $bidder = User::factory()->create();

        app(AuctionService::class)->placeBid($bidder, $auction, 1000);
        $auction->refresh();
        $this->assertSame(1000, $auction->current_price);
        $this->assertSame(1, $auction->bids_count);
        $this->assertSame(1100, $auction->minNextBid());
    }

    public function test_bid_below_minimum_is_rejected(): void
    {
        $auction = $this->liveAuction();
        $bidder = User::factory()->create();

        $this->expectException(BidException::class);
        app(AuctionService::class)->placeBid($bidder, $auction, 900);
    }

    public function test_seller_cannot_bid_on_own_auction(): void
    {
        $auction = $this->liveAuction();
        $this->expectException(BidException::class);
        app(AuctionService::class)->placeBid($auction->seller, $auction, 1000);
    }

    public function test_outbidding_demotes_previous_leader_and_notifies(): void
    {
        Notification::fake();
        $auction = $this->liveAuction();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $b1 = app(AuctionService::class)->placeBid($first, $auction, 1000);
        app(AuctionService::class)->placeBid($second, $auction->refresh(), 1500);

        $this->assertSame('outbid', $b1->fresh()->status);
        $this->assertSame(1500, $auction->fresh()->current_price);
        Notification::assertSentTo($first, Outbid::class);
    }

    public function test_closing_picks_highest_bidder_as_winner_and_notifies(): void
    {
        Notification::fake();
        $auction = $this->liveAuction();
        $winner = User::factory()->create();

        app(AuctionService::class)->placeBid($winner, $auction, 1200);
        app(AuctionService::class)->close($auction->refresh());

        $auction->refresh();
        $this->assertSame('ended', $auction->status);
        $this->assertSame($winner->id, $auction->winner_id);
        Notification::assertSentTo($winner, AuctionWon::class);
    }

    public function test_close_ended_command_closes_due_auctions(): void
    {
        $due = $this->liveAuction(['ends_at' => now()->subMinute()]);
        $future = $this->liveAuction(['ends_at' => now()->addDay()]);

        $this->artisan('auctions:close-ended')->assertSuccessful();

        $this->assertSame('ended', $due->fresh()->status);
        $this->assertSame('live', $future->fresh()->status);
    }

    public function test_user_can_create_and_bid_through_the_pages(): void
    {
        $seller = User::factory()->create();
        Volt::actingAs($seller)->test('auctions.create')
            ->set('title', 'موبايل سامسونج')
            ->set('starting_price', 2000)
            ->set('bid_increment', 100)
            ->set('duration_days', 3)
            ->call('save')
            ->assertHasNoErrors();

        $auction = Auction::first();
        $this->assertSame('live', $auction->status);

        $bidder = User::factory()->create();
        Volt::actingAs($bidder)->test('auctions.show', ['auction' => $auction])
            ->set('amount', 2000)
            ->call('placeBid')
            ->assertHasNoErrors();

        $this->assertSame(2000, $auction->fresh()->current_price);
    }

    public function test_too_low_bid_shows_error_on_page(): void
    {
        $auction = $this->liveAuction();
        $bidder = User::factory()->create();

        Volt::actingAs($bidder)->test('auctions.show', ['auction' => $auction])
            ->set('amount', 500)
            ->call('placeBid')
            ->assertHasErrors('amount');
    }
}
