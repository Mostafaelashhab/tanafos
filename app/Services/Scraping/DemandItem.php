<?php

namespace App\Services\Scraping;

/**
 * A single raw piece of buyer demand pulled from an external source,
 * before it has been parsed into a structured Request.
 */
class DemandItem
{
    public function __construct(
        public string $platform,
        public string $externalId,
        public string $text,
        public ?string $url = null,
        public ?string $contactName = null,
        public ?string $contactPhone = null,
        public ?string $city = null,
        public ?string $postedAt = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(string $platform, array $row): self
    {
        $text = trim((string) ($row['text'] ?? $row['message'] ?? $row['body'] ?? ''));
        $externalId = (string) ($row['external_id'] ?? $row['id'] ?? md5($platform.$text));

        return new self(
            platform: $platform,
            externalId: $externalId,
            text: $text,
            url: $row['url'] ?? null,
            contactName: $row['contact_name'] ?? $row['author'] ?? null,
            contactPhone: $row['contact_phone'] ?? $row['phone'] ?? null,
            city: $row['city'] ?? null,
            postedAt: $row['posted_at'] ?? $row['created_at'] ?? null,
        );
    }

    public function isUsable(): bool
    {
        return mb_strlen($this->text) >= 8;
    }
}
