<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Offer;
use App\Notifications\NewOffer;
use Illuminate\Support\Facades\DB;

class OfferService
{
    /**
     * Submit an offer for a lead, consuming one credit atomically.
     * Subscription merchants are not charged. Idempotent guard: one offer per (request, merchant).
     *
     * @param  array{price:int, warranty?:?string, delivery_days?:?int, description?:?string, negotiation_enabled?:bool}  $data
     *
     * @throws InsufficientCreditsException
     */
    public function submit(MerchantProfile $merchant, Lead $lead, array $data): Offer
    {
        $offer = DB::transaction(function () use ($merchant, $lead, $data) {
            // Lock the profile row so concurrent submissions can't double-spend a credit.
            $merchant = MerchantProfile::whereKey($merchant->id)->lockForUpdate()->firstOrFail();

            if (! $merchant->canSubmitOffer()) {
                throw new InsufficientCreditsException;
            }

            $offer = Offer::create([
                'request_id' => $lead->request_id,
                'merchant_profile_id' => $merchant->id,
                'lead_id' => $lead->id,
                'price' => $data['price'],
                'warranty' => $data['warranty'] ?? null,
                'delivery_days' => $data['delivery_days'] ?? null,
                'description' => $data['description'] ?? null,
                'negotiation_enabled' => $data['negotiation_enabled'] ?? true,
                'status' => 'submitted',
            ]);

            $charged = false;
            if (! $merchant->onSubscription()) {
                $merchant->decrement('credits_balance');
                app(CreditService::class)->recordConsumption($merchant, $merchant->credits_balance, $offer);
                $charged = true;
            }

            $lead->forceFill([
                'status' => 'offered',
                'charged_at' => $charged ? now() : $lead->charged_at,
            ])->save();

            return $offer;
        });

        // Notify the buyer (DB + live broadcast) once the offer is committed.
        $offer->request->buyer->notify(new NewOffer($offer));

        return $offer;
    }
}
