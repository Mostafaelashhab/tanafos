<?php

namespace App\Services\Scraping;

use App\Models\Request;
use App\Services\Scraping\Contracts\DemandSource;
use App\Services\Scraping\Sources\ApiFeedSource;
use App\Services\Scraping\Sources\HtmlScraperSource;
use App\Services\Scraping\Sources\JsonFileSource;
use Illuminate\Support\Str;

/**
 * Orchestrates demand import: pulls raw items from configured sources, parses
 * each into a structured request, dedupes, and persists. Imported requests are
 * commission-exempt (free for merchants to offer on) and, unless auto-publish
 * is on, land as drafts in the admin review queue.
 */
class DemandImporter
{
    public function __construct(private DemandParser $parser)
    {
    }

    public function enabled(): bool
    {
        return (bool) config('banha.scrape.enabled');
    }

    /**
     * Run every enabled source (or a single named one).
     *
     * @return array<string, array{imported:int, skipped:int}>
     */
    public function run(?string $only = null, ?int $limit = null): array
    {
        $limit ??= (int) config('banha.scrape.per_source_limit', 25);
        $summary = [];

        foreach ($this->sources() as $source) {
            if ($only !== null && $source->key() !== $only) {
                continue;
            }

            $summary[$source->key()] = $this->ingest($source, $limit);
        }

        return $summary;
    }

    /** @return array{imported:int, skipped:int} */
    public function ingest(DemandSource $source, int $limit): array
    {
        $imported = 0;
        $skipped = 0;
        $platform = null;

        foreach ($source->fetch($limit) as $item) {
            $platform = $item->platform;

            if (! $item->isUsable() || $this->exists($item)) {
                $skipped++;

                continue;
            }

            $this->persist($item);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function persist(DemandItem $item): Request
    {
        $parsed = $this->parser->parse($item);
        $autoPublish = (bool) config('banha.scrape.auto_publish');

        return Request::create([
            'buyer_id' => null,
            'category_id' => $parsed['category_id'],
            'title' => $parsed['title'],
            'description' => $parsed['description'],
            'budget_min' => $parsed['budget_min'],
            'budget_max' => $parsed['budget_max'],
            'city' => $parsed['city'],
            'condition' => $parsed['condition'],
            'urgency' => $parsed['urgency'],
            'specifications' => $parsed['specifications'] ?: null,
            'contact_name' => $item->contactName,
            'contact_phone' => $parsed['contact_phone'],
            'source' => 'scraped',
            'source_platform' => $item->platform,
            'source_url' => $item->url ? Str::limit($item->url, 1024, '') : null,
            'external_id' => Str::limit($item->externalId, 255, ''),
            'commission_exempt' => true,
            'imported_at' => now(),
            // Auto-publish goes live (fires matching); otherwise held for review.
            'status' => $autoPublish ? 'open' : 'draft',
            'published_at' => $autoPublish ? now() : null,
        ]);
    }

    private function exists(DemandItem $item): bool
    {
        return Request::query()
            ->where('source_platform', $item->platform)
            ->where('external_id', Str::limit($item->externalId, 255, ''))
            ->exists();
    }

    /** @return array<int, DemandSource> */
    public function sources(): array
    {
        $sources = [];

        foreach ((array) config('banha.scrape.sources', []) as $key => $config) {
            if (! ($config['enabled'] ?? false)) {
                continue;
            }

            $sources[] = match ($config['driver'] ?? null) {
                'api' => new ApiFeedSource($key, $config),
                'html' => new HtmlScraperSource($key, $config),
                'json' => new JsonFileSource($key, $config),
                default => null,
            };
        }

        return array_values(array_filter($sources));
    }
}
