<?php

namespace App\Services\Scraping;

use Anthropic\Client;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns a raw scraped demand post into structured request fields. Uses Claude
 * to read messy real-world text (Arabic/English) and classify it; falls back to
 * a heuristic parser when AI is disabled or the call fails, so imports never
 * block on the model being available.
 */
class DemandParser
{
    public function enabled(): bool
    {
        return (bool) config('banha.ai.enabled');
    }

    /**
     * @return array{
     *   title:string, category_id:?int, description:?string,
     *   budget_min:?int, budget_max:?int, city:?string,
     *   condition:string, urgency:string, contact_phone:?string,
     *   specifications:array<string,mixed>
     * }
     */
    public function parse(DemandItem $item): array
    {
        $base = $this->heuristic($item);

        if (! $this->enabled()) {
            return $base;
        }

        try {
            $ai = $this->callClaude($item);
        } catch (Throwable $e) {
            Log::warning('Demand parse failed', ['platform' => $item->platform, 'error' => $e->getMessage()]);

            return $base;
        }

        if (! is_array($ai)) {
            return $base;
        }

        // AI wins where present; heuristic fills the gaps.
        return [
            'title' => Str::limit(trim($ai['title'] ?? '') ?: $base['title'], 120, ''),
            'category_id' => $this->resolveCategoryId($ai['category'] ?? null) ?? $base['category_id'],
            'description' => $ai['description'] ?? $base['description'],
            'budget_min' => $this->int($ai['budget_min'] ?? null),
            'budget_max' => $this->int($ai['budget_max'] ?? null),
            'city' => $ai['city'] ?? $base['city'],
            'condition' => in_array($ai['condition'] ?? null, ['new', 'used', 'any'], true) ? $ai['condition'] : 'any',
            'urgency' => in_array($ai['urgency'] ?? null, ['low', 'normal', 'high'], true) ? $ai['urgency'] : 'normal',
            'contact_phone' => $ai['contact_phone'] ?? $base['contact_phone'],
            'specifications' => is_array($ai['specifications'] ?? null) ? $ai['specifications'] : $base['specifications'],
        ];
    }

    /** Best-effort parse without AI. */
    private function heuristic(DemandItem $item): array
    {
        $text = Str::squish($item->text);
        $title = Str::limit($text, 80, '');

        return [
            'title' => $title !== '' ? $title : __('Imported request'),
            'category_id' => $this->defaultCategoryId(),
            'description' => $item->text,
            'budget_min' => null,
            'budget_max' => null,
            'city' => $item->city,
            'condition' => 'any',
            'urgency' => 'normal',
            'contact_phone' => $item->contactPhone ?? $this->extractPhone($text),
            'specifications' => [],
        ];
    }

    /** @return array<string, mixed>|null */
    private function callClaude(DemandItem $item): ?array
    {
        $client = new Client(apiKey: config('banha.ai.key'));

        $categories = Category::query()->where('is_active', true)
            ->get(['id', 'name', 'name_ar'])
            ->map(fn ($c) => "{$c->id}: {$c->name} / {$c->name_ar}")
            ->implode("\n");

        $prompt = <<<PROMPT
        You normalize scraped "wanted / looking for" posts into buyer requests for an
        Egyptian reverse marketplace (prices in EGP). The text may be Arabic or English
        and messy. Extract a clean request.

        Available categories (id: en / ar):
        {$categories}

        Post:
        """
        {$item->text}
        """

        Respond with ONLY a JSON object in this exact shape, no prose:
        {"title": "<short Arabic title>", "category": <category id int or null>,
         "description": "<cleaned summary or null>", "budget_min": <int|null>, "budget_max": <int|null>,
         "city": "<city or null>", "condition": "new|used|any", "urgency": "low|normal|high",
         "contact_phone": "<phone or null>", "specifications": {"<key>": "<value>"}}
        Pick the single best category id from the list, or null if none fits.
        PROMPT;

        $message = $client->messages->create(
            maxTokens: 1024,
            messages: [['role' => 'user', 'content' => $prompt]],
            model: config('banha.ai.model'),
        );

        $text = '';
        foreach ($message->content ?? [] as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text ?? '';
            }
        }

        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function resolveCategoryId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return Category::whereKey($id)->exists() ? $id : null;
    }

    private function defaultCategoryId(): ?int
    {
        $configured = config('banha.scrape.default_category_id');
        if ($configured && Category::whereKey($configured)->exists()) {
            return (int) $configured;
        }

        return Category::query()->where('is_active', true)->orderBy('sort_order')->value('id');
    }

    private function extractPhone(string $text): ?string
    {
        // Egyptian mobile numbers (optionally +20 / 0020 prefixed).
        if (preg_match('/(?:\+?20|0)?1[0125]\d{8}/', str_replace([' ', '-'], '', $text), $m)) {
            return $m[0];
        }

        return null;
    }

    private function int(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
