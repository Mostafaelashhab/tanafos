<?php

namespace App\Services\Scraping\Sources;

use App\Services\Scraping\Contracts\DemandSource;
use App\Services\Scraping\DemandItem;

/**
 * Local JSON file source — for manual imports, seeding, or feeding a
 * scrape you ran elsewhere. The file is a JSON array of objects with
 * at least a "text" field (id/url/phone/city optional).
 */
class JsonFileSource implements DemandSource
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
        $path = $this->config['path'] ?? null;
        if (! $path || ! is_file($path)) {
            return [];
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            return [];
        }

        $platform = $this->config['platform'] ?? 'import';
        $items = [];

        foreach (array_slice($rows, 0, $limit) as $row) {
            if (is_array($row)) {
                $items[] = DemandItem::fromArray($platform, $row);
            }
        }

        return $items;
    }
}
