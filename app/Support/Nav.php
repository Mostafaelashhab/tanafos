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

    /** Localized title for the current screen (shown in the mobile app bar). */
    public static function title(): string
    {
        $map = [
            'dashboard' => 'Home',
            'requests.index' => 'My requests',
            'requests.create' => 'New request',
            'requests.show' => 'Request',
            'requests.edit' => 'Edit request',
            'merchant.leads.index' => 'Leads',
            'merchant.leads.show' => 'Lead',
            'merchant.billing' => 'Billing',
            'leaderboard' => 'Leaderboard',
            'notifications.index' => 'Notifications',
            'profile' => 'Profile',
            'admin.dashboard' => 'Admin',
            'admin.merchants' => 'Merchants',
            'admin.users' => 'Users',
            'admin.requests' => 'Requests',
            'admin.plans' => 'Plans',
            'conversations.show' => 'Chat',
        ];

        foreach ($map as $route => $title) {
            if (request()->routeIs($route)) {
                return __($title);
            }
        }

        return config('app.name');
    }
}

