<?php

namespace App\Services;

use App\Exceptions\BidException;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use App\Notifications\AuctionWon;
use App\Notifications\Outbid;
use Illuminate\Support\Facades\DB;

class AuctionService
{
    /**
     * Place a bid on an auction. Atomic: locks the auction row so two bids can't
     * tie, marks the previous leader as outbid, and bumps the auction price.
     *
     * @throws BidException
     */
    public function placeBid(User $bidder, Auction $auction, int $amount): Bid
    {
        if ($auction->seller_id === $bidder->id) {
            throw new BidException(__('You cannot bid on your own auction.'));
        }

        [$bid, $previousLeaderId] = DB::transaction(function () use ($bidder, $auction, $amount) {
            /** @var Auction $auction */
            $auction = Auction::whereKey($auction->id)->lockForUpdate()->firstOrFail();

            if (! $auction->isLive()) {
                throw new BidException(__('This auction is closed.'));
            }

            if ($amount < $auction->minNextBid()) {
                throw new BidException(__('Your bid must be at least :n :c.', [
                    'n' => $auction->minNextBid(), 'c' => $auction->currency,
                ]));
            }

            // Demote the current leader.
            $previousLeaderId = null;
            if ($auction->highest_bid_id) {
                $previous = Bid::find($auction->highest_bid_id);
                if ($previous) {
                    $previousLeaderId = $previous->bidder_id;
                    $previous->update(['status' => 'outbid']);
                }
            }

            $bid = Bid::create([
                'auction_id' => $auction->id,
                'bidder_id' => $bidder->id,
                'amount' => $amount,
                'status' => 'leading',
            ]);

            $auction->forceFill([
                'current_price' => $amount,
                'highest_bid_id' => $bid->id,
                'bids_count' => $auction->bids_count + 1,
            ])->save();

            return [$bid, $previousLeaderId];
        });

        // Tell the person who just lost the lead (if any, and not the same bidder).
        if ($previousLeaderId && $previousLeaderId !== $bidder->id) {
            User::find($previousLeaderId)?->notify(new Outbid($bid->auction->refresh()));
        }

        return $bid;
    }

    /**
     * Close an auction: pick the winner from the highest bid. Safe to call on an
     * already-closed auction (no-op). Used both by the timer and manual close.
     */
    public function close(Auction $auction): void
    {
        $winnerId = DB::transaction(function () use ($auction) {
            $auction = Auction::whereKey($auction->id)->lockForUpdate()->firstOrFail();

            if ($auction->status !== 'live') {
                return null;
            }

            $winningBid = $auction->highest_bid_id ? Bid::find($auction->highest_bid_id) : null;
            if ($winningBid) {
                $winningBid->update(['status' => 'won']);
            }

            $auction->forceFill([
                'status' => 'ended',
                'closed_at' => now(),
                'winner_id' => $winningBid?->bidder_id,
            ])->save();

            return $winningBid?->bidder_id;
        });

        if ($winnerId) {
            User::find($winnerId)?->notify(new AuctionWon($auction->refresh()));
        }
    }

    /** Seller cancels a live auction (no winner). */
    public function cancel(Auction $auction): void
    {
        if ($auction->status !== 'live') {
            return;
        }

        $auction->forceFill(['status' => 'cancelled', 'closed_at' => now()])->save();
    }
}
