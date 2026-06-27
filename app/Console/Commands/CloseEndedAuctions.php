<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Services\AuctionService;
use Illuminate\Console\Command;

class CloseEndedAuctions extends Command
{
    protected $signature = 'auctions:close-ended';

    protected $description = 'Close auctions whose end time has passed and notify winners';

    public function handle(AuctionService $auctions): int
    {
        $due = Auction::where('status', 'live')->where('ends_at', '<=', now())->get();

        foreach ($due as $auction) {
            $auctions->close($auction);
        }

        $this->info("Closed {$due->count()} auction(s).");

        return self::SUCCESS;
    }
}
