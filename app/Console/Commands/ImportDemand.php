<?php

namespace App\Console\Commands;

use App\Services\Scraping\DemandImporter;
use Illuminate\Console\Command;

class ImportDemand extends Command
{
    protected $signature = 'demand:import
                            {source? : Import only this configured source key}
                            {--limit= : Max items per source this run}';

    protected $description = 'Import buyer-demand posts from external sources into requests (commission-exempt)';

    public function handle(DemandImporter $importer): int
    {
        if (! $importer->enabled()) {
            $this->warn('Demand scraping is disabled (SCRAPE_ENABLED=false).');

            return self::SUCCESS;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $summary = $importer->run($this->argument('source'), $limit);

        if (empty($summary)) {
            $this->warn('No enabled sources matched. Configure banha.scrape.sources.');

            return self::SUCCESS;
        }

        $total = 0;
        foreach ($summary as $key => $row) {
            $this->line("• <info>{$key}</info>: imported {$row['imported']}, skipped {$row['skipped']}");
            $total += $row['imported'];
        }

        $this->info("Done. {$total} new request(s) imported.");

        return self::SUCCESS;
    }
}
