<?php

namespace App\Jobs;

use App\Models\Request;
use App\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchRequestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Request $request)
    {
    }

    public function handle(MatchingService $matching): void
    {
        $matching->match($this->request);
    }
}
