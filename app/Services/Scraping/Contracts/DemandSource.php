<?php

namespace App\Services\Scraping\Contracts;

use App\Services\Scraping\DemandItem;

interface DemandSource
{
    /** Short identifier (matches the config key). */
    public function key(): string;

    /**
     * Fetch up to $limit raw demand items from the source.
     *
     * @return iterable<DemandItem>
     */
    public function fetch(int $limit): iterable;
}
