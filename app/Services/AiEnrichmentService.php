<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiEnrichmentService
{
    /** Whether AI enrichment is configured (an Anthropic key is present). */
    public function enabled(): bool
    {
        return (bool) config('banha.ai.enabled');
    }

    /**
     * Enrich a request with structured specifications and a suggested budget,
     * derived from the buyer's free-text title/description via Claude.
     * No-op (returns false) when AI is disabled or the call fails — enrichment
     * must never block the marketplace loop.
     */
    public function enrich(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $data = $this->callClaude($request);
        } catch (Throwable $e) {
            Log::warning('AI enrichment failed', ['request_id' => $request->id, 'error' => $e->getMessage()]);

            return false;
        }

        if (! is_array($data)) {
            return false;
        }

        return $this->apply($request, $data);
    }

    /**
     * Apply parsed enrichment to a request: store specs, and fill only the
     * budget hints the buyer left blank — never overwrite their own input.
     *
     * @param  array<string, mixed>  $data
     */
    public function apply(Request $request, array $data): bool
    {
        // Don't overwrite specs the buyer entered in the wizard.
        if (empty($request->specifications) && ! empty($data['specifications'])) {
            $request->forceFill(['specifications' => $data['specifications']]);
        }

        if (empty($request->budget_min) && ! empty($data['suggested_budget_min'])) {
            $request->budget_min = (int) $data['suggested_budget_min'];
        }
        if (empty($request->budget_max) && ! empty($data['suggested_budget_max'])) {
            $request->budget_max = (int) $data['suggested_budget_max'];
        }

        $request->saveQuietly(); // don't re-trigger matching on enrichment save

        return true;
    }

    /** @return array<string, mixed>|null */
    private function callClaude(Request $request): ?array
    {
        $client = new Client(apiKey: config('banha.ai.key'));

        $category = $request->category?->name ?? 'general';
        $prompt = <<<PROMPT
        You enrich buyer requests for a reverse marketplace in Egypt (prices in EGP).
        Given a buyer's request, extract structured specifications and suggest a realistic budget range.

        Category: {$category}
        Title: {$request->title}
        Description: {$request->description}

        Respond with ONLY a JSON object, no prose, in this exact shape:
        {"specifications": {"<key>": "<value>", ...}, "suggested_budget_min": <int|null>, "suggested_budget_max": <int|null>}
        Use concise spec keys relevant to the category. Use null for budgets you cannot estimate.
        PROMPT;

        $message = $client->messages->create(
            maxTokens: 1024,
            messages: [['role' => 'user', 'content' => $prompt]],
            model: config('banha.ai.model'),
        );

        return $this->parseJson($this->extractText($message));
    }

    /** Concatenate text blocks from the SDK response. */
    private function extractText(object $message): string
    {
        $text = '';
        foreach ($message->content ?? [] as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text ?? '';
            }
        }

        return $text;
    }

    /** @return array<string, mixed>|null */
    private function parseJson(string $text): ?array
    {
        // Tolerate models that wrap JSON in prose or fences.
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
