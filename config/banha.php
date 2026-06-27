<?php

return [

    /*
    | Lead-credit packages. Price in EGP. `credits = null` means unlimited
    | (Pro) which is granted as a subscription tier rather than a balance.
    */
    'credit_packages' => [
        'starter' => ['name' => 'Starter', 'name_ar' => 'المبتدئ', 'credits' => 100, 'price' => 299],
        'growth' => ['name' => 'Growth', 'name_ar' => 'النمو', 'credits' => 500, 'price' => 999],
        'pro' => ['name' => 'Pro', 'name_ar' => 'المحترف', 'credits' => null, 'price' => 2999, 'grants_tier' => 'premium'],
    ],

    /*
    | Subscription plans. `tier` matches merchant_profiles.subscription_tier.
    | Subscription merchants submit offers without consuming credits.
    */
    'plans' => [
        'basic' => ['name' => 'Basic', 'name_ar' => 'أساسي', 'tier' => 'basic', 'price' => 299],
        'gold' => ['name' => 'Gold', 'name_ar' => 'ذهبي', 'tier' => 'gold', 'price' => 799],
        'premium' => ['name' => 'Premium', 'name_ar' => 'بريميوم', 'tier' => 'premium', 'price' => 1999],
    ],

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
