<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /** A merchant may only see leads addressed to their own profile. */
    public function view(User $user, Lead $lead): bool
    {
        return $user->isMerchant()
            && $user->merchantProfile?->id === $lead->merchant_profile_id;
    }

    /** Submit a (single) offer for this lead, while its request is still open. */
    public function submitOffer(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead)
            && $lead->offer()->doesntExist()
            && in_array($lead->request->status, ['open', 'matched'], true);
    }
}
