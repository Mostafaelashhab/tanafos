<?php

namespace App\Services;

use App\Models\MerchantProfile;
use App\Models\Request;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Record the buyer's review of the winning merchant and refresh cached rating.
     *
     * @param  array{rating:int, quality_score?:?int, delivery_score?:?int, response_score?:?int, comment?:?string}  $data
     */
    public function submit(Request $request, array $data): Review
    {
        $merchantProfileId = $request->selectedOffer->merchant_profile_id;

        return DB::transaction(function () use ($request, $data, $merchantProfileId) {
            $review = Review::create([
                'request_id' => $request->id,
                'reviewer_id' => $request->buyer_id,
                'merchant_profile_id' => $merchantProfileId,
                'rating' => $data['rating'],
                'quality_score' => $data['quality_score'] ?? null,
                'delivery_score' => $data['delivery_score'] ?? null,
                'response_score' => $data['response_score'] ?? null,
                'comment' => $data['comment'] ?? null,
            ]);

            $this->recomputeRating(MerchantProfile::find($merchantProfileId));

            return $review;
        });
    }

    public function recomputeRating(MerchantProfile $profile): void
    {
        $profile->forceFill([
            'rating_avg' => round((float) $profile->reviews()->avg('rating'), 2),
        ])->save();
    }
}
