<?php

namespace App\Services\Scraping\Sources;

use App\Services\Scraping\Contracts\DemandSource;
use App\Services\Scraping\DemandItem;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Generic best-effort HTML scraper. Fetches a page and extracts demand
 * items by XPath selectors. Intended for sites you're permitted to scrape —
 * raw scraping of Google/Facebook violates their Terms of Service, so prefer
 * ApiFeedSource for those. Selectors come from config; missing ones degrade
 * gracefully to whole-element text.
 */
class HtmlScraperSource implements DemandSource
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
        $url = $this->config['url'] ?? null;
        if (! $url) {
            return [];
        }

        $response = Http::timeout(20)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; TanafosBot/1.0)'])
            ->get($url);

        if (! $response->successful() || trim($response->body()) === '') {
            return [];
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$response->body());
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $itemQuery = $this->normalize($this->config['item_selector'] ?? 'article', absolute: true);
        $nodes = $xpath->query($itemQuery);
        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $platform = $this->config['platform'] ?? 'web';
        $textSel = $this->config['text_selector'] ?? null;
        $linkSel = $this->config['link_selector'] ?? './/a/@href';
        $items = [];

        foreach ($nodes as $i => $node) {
            if (count($items) >= $limit) {
                break;
            }

            $text = $textSel
                ? trim((string) optional($xpath->query($textSel, $node)?->item(0))->textContent)
                : trim($node->textContent);
            $text = Str::squish($text);

            if (mb_strlen($text) < 8) {
                continue;
            }

            $href = (string) optional($xpath->query($linkSel, $node)?->item(0))->nodeValue;

            $items[] = new DemandItem(
                platform: $platform,
                externalId: $href !== '' ? $href : md5($platform.$text),
                text: mb_substr($text, 0, 2000),
                url: $this->absoluteUrl($href, $url),
            );
        }

        return $items;
    }

    private function normalize(string $selector, bool $absolute = false): string
    {
        if (str_starts_with($selector, '/') || str_starts_with($selector, './')) {
            return $selector;
        }

        // Treat a bare token as an element name.
        return ($absolute ? '//' : './/').$selector;
    }

    private function absoluteUrl(string $href, string $base): ?string
    {
        if ($href === '') {
            return null;
        }
        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }

        $parts = parse_url($base);
        if (! isset($parts['scheme'], $parts['host'])) {
            return $href;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];

        return $origin.'/'.ltrim($href, '/');
    }
}
