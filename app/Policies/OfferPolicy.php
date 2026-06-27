<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    /** The owning merchant, or the buyer who owns the request, may view an offer. */
    public function view(User $user, Offer $offer): bool
    {
        return ($user->isMerchant() && $user->merchantProfile?->id === $offer->merchant_profile_id)
            || ($user->isBuyer() && $user->id === $offer->request->buyer_id);
    }
}
