<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;

class RequestPolicy
{
    public function view(User $user, Request $request): bool
    {
        return $user->id === $request->buyer_id;
    }

    public function update(User $user, Request $request): bool
    {
        // Only the owning buyer, and only while not yet completed/closed.
        return $user->id === $request->buyer_id
            && in_array($request->status, ['draft', 'open', 'matched'], true);
    }

    public function delete(User $user, Request $request): bool
    {
        return $user->id === $request->buyer_id;
    }
}
