<?php

namespace App\Jobs;

use App\Models\Request;
use App\Services\AiEnrichmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnrichRequestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Request $request)
    {
    }

    public function handle(AiEnrichmentService $ai): void
    {
        $ai->enrich($this->request);
    }
}
