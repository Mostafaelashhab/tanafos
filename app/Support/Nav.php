<?php

namespace App\Support;

use App\Models\User;

class Nav
{
    /**
     * Role-aware primary navigation items.
     *
     * @return array<int, array{route:string, active:string, icon:string, label:string}>
     */
    public static function primary(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $items = [
            ['route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
        ];

        if ($user->isBuyer()) {
            $items[] = ['route' => 'requests.index', 'active' => 'requests.*', 'icon' => 'document', 'label' => 'My requests'];
        }

        if ($user->isMerchant()) {
            $items[] = ['route' => 'merchant.leads.index', 'active' => 'merchant.leads.*', 'icon' => 'inbox', 'label' => 'Leads'];
            $items[] = ['route' => 'merchant.billing', 'active' => 'merchant.billing', 'icon' => 'credit-card', 'label' => 'Billing'];
        }

        if ($user->isAdmin()) {
            $items[] = ['route' => 'admin.dashboard', 'active' => 'admin.*', 'icon' => 'shield-check', 'label' => 'Admin'];
        }

        $items[] = ['route' => 'leaderboard', 'active' => 'leaderboard', 'icon' => 'trophy', 'label' => 'Leaderboard'];

        return $items;
    }
}
