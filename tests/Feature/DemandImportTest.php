<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Models\User;
use App\Services\OfferService;
use App\Services\Scraping\DemandImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DemandImportTest extends TestCase
{
    use RefreshDatabase;

    private function configureFileSource(Category $category): void
    {
        config([
            'banha.ai.enabled' => false, // force the heuristic parser (deterministic)
            'banha.scrape.enabled' => true,
            'banha.scrape.auto_publish' => false,
            'banha.scrape.default_category_id' => $category->id,
            'banha.scrape.sources' => [
                'file' => [
                    'driver' => 'json',
                    'platform' => 'facebook',
                    'enabled' => true,
                    'path' => base_path('tests/Fixtures/demand.json'),
                ],
            ],
        ]);
    }

    public function test_importer_creates_commission_exempt_draft_requests(): void
    {
        $category = Category::factory()->create();
        $this->configureFileSource($category);

        $summary = app(DemandImporter::class)->run();

        $this->assertSame(2, $summary['file']['imported']);
        $this->assertDatabaseCount('requests', 2);

        $request = Request::scraped()->first();
        $this->assertSame('scraped', $request->source);
        $this->assertSame('facebook', $request->source_platform);
        $this->assertTrue((bool) $request->commission_exempt);
        $this->assertSame('draft', $request->status); // held for review
        $this->assertNull($request->buyer_id);
        $this->assertSame($category->id, $request->category_id);
    }

    public function test_importer_extracts_phone_and_dedupes_on_rerun(): void
    {
        $category = Category::factory()->create();
        $this->configureFileSource($category);

        app(DemandImporter::class)->run();

        $withPhone = Request::scraped()->where('external_id', 'fb-1001')->first();
        $this->assertSame('01022345504', $withPhone->contact_phone);

        // Re-running imports nothing new (deduped by platform + external_id).
        $summary = app(DemandImporter::class)->run();
        $this->assertSame(0, $summary['file']['imported']);
        $this->assertSame(2, $summary['file']['skipped']);
        $this->assertDatabaseCount('requests', 2);
    }

    public function test_admin_can_approve_imported_demand_to_publish_it(): void
    {
        $category = Category::factory()->create();
        $this->configureFileSource($category);
        app(DemandImporter::class)->run();

        $admin = User::factory()->create(['type' => 'admin']);
        $request = Request::pendingImport()->first();

        Volt::actingAs($admin)->test('admin.demand')->call('approve', $request->id);

        $this->assertSame('open', $request->fresh()->status);
    }

    public function test_offer_on_imported_demand_is_free_and_needs_no_credit(): void
    {
        $merchant = MerchantProfile::factory()->verified()->create([
            'credits_balance' => 0, 'subscription_tier' => 'none',
        ]);
        $request = Request::factory()->published()->create([
            'commission_exempt' => true,
            'buyer_id' => null, // scraped requests have no buyer account
        ]);
        $lead = Lead::factory()->create([
            'request_id' => $request->id,
            'merchant_profile_id' => $merchant->id,
            'status' => 'notified',
        ]);

        $offer = app(OfferService::class)->submit($merchant, $lead, ['price' => 12000]);

        $this->assertSame('submitted', $offer->status);
        $this->assertSame(0, $merchant->fresh()->credits_balance); // no credit consumed
        $this->assertSame('offered', $lead->fresh()->status);
    }
}
