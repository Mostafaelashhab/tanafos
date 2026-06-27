<?php

return [

    // Lead-credit packages and subscription plans are now DB-managed (admin → Plans).
    // See the `credit_packages` and `plans` tables + Database\Seeders\PlanSeeder.

    'currency' => 'EGP',

    /*
    | Manual payment — merchants transfer to this number via InstaPay or
    | Vodafone Cash, submit proof, and an admin approves to apply the purchase.
    */
    'payment' => [
        'number' => env('PAYMENT_NUMBER', '01022345504'),
        'methods' => [
            'instapay' => ['name' => 'InstaPay', 'name_ar' => 'إنستاباي'],
            'vodafone_cash' => ['name' => 'Vodafone Cash', 'name_ar' => 'فودافون كاش'],
        ],
    ],

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

    /*
    | Demand scraping — import "people looking for X" posts from external
    | sources and turn them into requests. Imported demand is commission-exempt:
    | merchants can submit offers on it WITHOUT spending a credit.
    |
    | Two source kinds are supported per entry:
    |   - driver: 'api'  → an official/provider JSON endpoint (recommended; ToS-safe)
    |   - driver: 'html' → a generic best-effort HTML scraper (config selectors)
    |   - driver: 'json' → a local JSON file (manual import / seeding / testing)
    */
    'scrape' => [
        'enabled' => (bool) env('SCRAPE_ENABLED', true),

        // Fallback category for imported demand the parser can't classify.
        'default_category_id' => env('SCRAPE_DEFAULT_CATEGORY_ID'),

        // When true, imported demand goes live immediately. When false (default)
        // it lands as a draft in the admin "Imported demand" review queue.
        'auto_publish' => (bool) env('SCRAPE_AUTO_PUBLISH', false),

        // Safety cap per source per run.
        'per_source_limit' => (int) env('SCRAPE_LIMIT', 25),

        'sources' => [
            // --- Official provider feed (e.g. an Apify dataset or partner API) ---
            'provider' => [
                'driver' => 'api',
                'platform' => env('SCRAPE_PROVIDER_PLATFORM', 'facebook'),
                'enabled' => (bool) env('SCRAPE_PROVIDER_ENDPOINT'),
                'endpoint' => env('SCRAPE_PROVIDER_ENDPOINT'),
                'token' => env('SCRAPE_PROVIDER_TOKEN'),
                // Dot-paths into each JSON item → our normalized fields.
                'map' => [
                    'external_id' => 'id',
                    'text' => 'text',
                    'url' => 'url',
                    'contact_name' => 'author',
                    'contact_phone' => 'phone',
                    'city' => 'city',
                    'posted_at' => 'created_at',
                ],
            ],

            // --- Generic HTML scraper (best-effort; may break / violates some ToS) ---
            'classifieds' => [
                'driver' => 'html',
                'platform' => 'web',
                'enabled' => (bool) env('SCRAPE_HTML_URL'),
                'url' => env('SCRAPE_HTML_URL'),
                // CSS-ish selectors resolved via DOMXPath.
                'item_selector' => env('SCRAPE_HTML_ITEM', 'article'),
                'text_selector' => env('SCRAPE_HTML_TEXT', './/*'),
                'link_selector' => env('SCRAPE_HTML_LINK', './/a/@href'),
            ],

            // --- Local JSON file (manual import / seeding) ---
            'file' => [
                'driver' => 'json',
                'platform' => env('SCRAPE_FILE_PLATFORM', 'import'),
                'enabled' => (bool) env('SCRAPE_FILE_PATH'),
                'path' => env('SCRAPE_FILE_PATH'),
            ],
        ],
    ],

];
