<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ImportedDemandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportedDemandSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_scraped_demand_with_review_queue_and_live_leads(): void
    {
        $this->seed(CategorySeeder::class);
        MerchantProfile::factory()->verified()->count(3)->create();

        $this->seed(ImportedDemandSeeder::class);

        // Everything imported is scraped + commission-exempt.
        $scraped = Request::scraped()->get();
        $this->assertGreaterThan(0, $scraped->count());
        $this->assertTrue($scraped->every(fn (Request $r) => $r->commission_exempt && $r->buyer_id === null));

        // Both a review queue (drafts) and live ones (open) exist.
        $this->assertGreaterThan(0, Request::pendingImport()->count());
        $this->assertGreaterThan(0, Request::scraped()->where('status', 'open')->count());

        // Live demand produced leads for merchants.
        $this->assertGreaterThan(0, Lead::count());

        // Idempotent: a second run adds nothing.
        $before = Request::count();
        $this->seed(ImportedDemandSeeder::class);
        $this->assertSame($before, Request::count());
    }
}
