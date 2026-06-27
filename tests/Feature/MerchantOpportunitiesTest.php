<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MerchantOpportunitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_sees_and_filters_opportunities(): void
    {
        $category = Category::factory()->create();
        $merchant = MerchantProfile::factory()->verified()->create();

        $paid = Request::factory()->published()->create(['category_id' => $category->id, 'title' => 'لابتوب مدفوع', 'commission_exempt' => false]);
        $free = Request::factory()->published()->create(['category_id' => $category->id, 'title' => 'لابتوب مجاني', 'commission_exempt' => true, 'buyer_id' => null]);

        foreach ([$paid, $free] as $r) {
            Lead::factory()->create(['request_id' => $r->id, 'merchant_profile_id' => $merchant->id, 'status' => 'notified']);
        }

        // Shows everything by default.
        Volt::actingAs($merchant->user)->test('merchant.leads.index')
            ->assertOk()
            ->assertSee('لابتوب مدفوع')
            ->assertSee('لابتوب مجاني');

        // Free-only filter hides the paid one.
        Volt::actingAs($merchant->user)->test('merchant.leads.index')
            ->set('type', 'free')
            ->assertSee('لابتوب مجاني')
            ->assertDontSee('لابتوب مدفوع');

        // Search narrows to a title.
        Volt::actingAs($merchant->user)->test('merchant.leads.index')
            ->set('search', 'مدفوع')
            ->assertSee('لابتوب مدفوع')
            ->assertDontSee('لابتوب مجاني');
    }
}
