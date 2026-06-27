<?php

return [

    // Lead-credit packages and subscription plans are now DB-managed (admin → Plans).
    // See the `credit_packages` and `plans` tables + Database\Seeders\PlanSeeder.

    'currency' => 'EGP',

    /*
    | Gamification: merchant levels by completed deals. Highest threshold met wins.
    */
    'levels' => [
        ['key' => 'bronze', 'name' => 'Bronze', 'name_ar' => 'برونزي', 'min_deals' => 0],
        ['key' => 'silver', 'name' => 'Silver', 'name_ar' => 'فضي', 'min_deals' => 5],
        ['key' => 'gold', 'name' => 'Gold', 'name_ar' => 'ذهبي', 'min_deals' => 20],
        ['key' => 'diamond', 'name' => 'Diamond', 'name_ar' => 'ماسي', 'min_deals' => 50],
        ['key' => 'elite', 'name' => 'Elite', 'name_ar' => 'النخبة', 'min_deals' => 100],
    ],

    /*
    | AI enrichment (Anthropic). Disabled automatically when no API key is set.
    */
    /*
    | Web Push (PWA background notifications). Disabled automatically when no
    | VAPID keypair is configured.
    */
    'push' => [
        'enabled' => (bool) env('VAPID_PUBLIC_KEY') && (bool) env('VAPID_PRIVATE_KEY'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:hello@tanafos.shop'),
    ],

    'ai' => [
        'enabled' => (bool) env('ANTHROPIC_API_KEY'),
        'key' => env('ANTHROPIC_API_KEY'),
        // Default to the most capable model; override with ANTHROPIC_MODEL
        // (e.g. claude-haiku-4-5) if you want to trade quality for cost.
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-8'),
    ],

];
