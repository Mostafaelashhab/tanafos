<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\MerchantProfile;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;

class SelectionService
{
    /**
     * Accept an offer as the winner: reject the rest, complete the request,
     * refresh merchant win-rates, and open a conversation with the winner.
     */
    public function selectWinner(Offer $offer): Offer
    {
        return DB::transaction(function () use ($offer) {
            $request = $offer->request()->lockForUpdate()->firstOrFail();

            // Merchants with a live offer here — their win-rate changes either way.
            $affected = $request->offers()->pluck('merchant_profile_id')->unique();

            $request->offers()
                ->whereKeyNot($offer->id)
                ->whereIn('status', ['submitted', 'shortlisted'])
                ->update(['status' => 'rejected']);

            $offer->update(['status' => 'accepted']);

            $request->forceFill([
                'selected_offer_id' => $offer->id,
                'status' => 'completed',
            ])->save();

            $winner = $offer->merchantProfile()->first();
            $winner->increment('completed_deals');

            foreach ($affected as $profileId) {
                $this->recomputeWinRate(MerchantProfile::find($profileId));
            }

            // Open (or reuse) the buyer↔winner thread so they can finalise details.
            Conversation::firstOrCreate(
                ['request_id' => $request->id, 'merchant_profile_id' => $winner->id],
                ['buyer_id' => $request->buyer_id],
            );

            return $offer->fresh();
        });
    }

    /** win_rate = accepted offers / total offers, as a percentage. */
    public function recomputeWinRate(MerchantProfile $profile): void
    {
        $total = $profile->offers()->count();
        $won = $profile->offers()->where('status', 'accepted')->count();

        $profile->forceFill([
            'win_rate' => $total > 0 ? round($won / $total * 100, 2) : 0,
        ])->save();
    }
}
