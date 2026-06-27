<?php

namespace App\Services\Scraping\Sources;

use App\Services\Scraping\Contracts\DemandSource;
use App\Services\Scraping\DemandItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Official-provider source: pulls demand from a JSON HTTP endpoint
 * (e.g. an Apify dataset, a partner API, or any data feed). This is the
 * recommended, ToS-safe path — point it at a provider you're allowed to use.
 */
class ApiFeedSource implements DemandSource
{
    /** @param array<string, mixed> $config */
    public function __construct(private string $key, private array $config)
    {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function fetch(int $limit): iterable
    {
        $endpoint = $this->config['endpoint'] ?? null;
        if (! $endpoint) {
            return [];
        }

        $request = Http::acceptJson()->timeout(20);
        if (! empty($this->config['token'])) {
            $request = $request->withToken($this->config['token']);
        }

        $response = $request->get($endpoint, ['limit' => $limit]);
        if (! $response->successful()) {
            return [];
        }

        // Accept either a bare array or a {data: [...]} / {results: [...]} envelope.
        $rows = $response->json('data', $response->json('results', $response->json()));
        if (! is_array($rows)) {
            return [];
        }

        $platform = $this->config['platform'] ?? $this->key;
        $map = $this->config['map'] ?? [];
        $items = [];

        foreach (array_slice($rows, 0, $limit) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $items[] = DemandItem::fromArray($platform, $this->remap($row, $map));
        }

        return $items;
    }

    /**
     * Translate provider field names into our normalized keys using dot-paths.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $map
     * @return array<string, mixed>
     */
    private function remap(array $row, array $map): array
    {
        if (empty($map)) {
            return $row;
        }

        $out = [];
        foreach ($map as $field => $path) {
            $out[$field] = Arr::get($row, $path);
        }

        return $out;
    }
}
