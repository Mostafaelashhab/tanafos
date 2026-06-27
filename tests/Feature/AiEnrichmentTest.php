<?php

namespace Tests\Feature;

use App\Jobs\EnrichRequestJob;
use App\Models\Request;
use App\Models\User;
use App\Services\AiEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class AiEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrichment_is_skipped_when_no_api_key(): void
    {
        config(['banha.ai.enabled' => false]);
        $request = Request::factory()->create(['specifications' => null]);

        $result = app(AiEnrichmentService::class)->enrich($request);

        $this->assertFalse($result);
        $this->assertNull($request->fresh()->specifications);
    }

    public function test_apply_stores_specs_and_fills_only_blank_budgets(): void
    {
        $request = Request::factory()->create([
            'specifications' => null,
            'budget_min' => 5000,   // buyer-provided — must be preserved
            'budget_max' => null,   // blank — may be filled
        ]);

        app(AiEnrichmentService::class)->apply($request, [
            'specifications' => ['brand' => 'Apple', 'storage' => '256GB'],
            'suggested_budget_min' => 9999,
            'suggested_budget_max' => 80000,
        ]);

        $request->refresh();
        $this->assertSame(['brand' => 'Apple', 'storage' => '256GB'], $request->specifications);
        $this->assertSame(5000, $request->budget_min);     // not overwritten
        $this->assertSame(80000, $request->budget_max);    // filled
    }

    public function test_publishing_dispatches_enrichment_job(): void
    {
        Queue::fake();
        $buyer = User::factory()->create(['type' => 'buyer']);
        $request = Request::factory()->for($buyer, 'buyer')->create(['status' => 'draft']);

        $request->publish();

        Queue::assertPushed(EnrichRequestJob::class, fn ($job) => $job->request->is($request));
    }

    public function test_enrich_job_delegates_to_the_service(): void
    {
        $request = Request::factory()->create();

        $mock = Mockery::mock(AiEnrichmentService::class);
        $mock->shouldReceive('enrich')->once()->with(Mockery::on(fn ($r) => $r->is($request)));
        $this->app->instance(AiEnrichmentService::class, $mock);

        (new EnrichRequestJob($request))->handle($mock);
    }
}
