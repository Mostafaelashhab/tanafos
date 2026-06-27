<?php

namespace Tests\Feature;

use App\Jobs\MatchRequestJob;
use App\Models\Category;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Models\User;
use App\Notifications\NewLead;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use RefreshDatabase;

    /** Verified merchant serving $category, with credits, optionally located. */
    private function merchant(Category $category, array $attrs = []): MerchantProfile
    {
        $profile = MerchantProfile::factory()->verified()->create(array_merge([
            'credits_balance' => 50,
        ], $attrs));
        $profile->categories()->attach($category);

        return $profile;
    }

    private function openRequest(Category $category, array $attrs = []): Request
    {
        // Queue faked by callers so the saved-event job doesn't auto-run.
        return Request::factory()->published()->create(array_merge([
            'category_id' => $category->id,
        ], $attrs));
    }

    public function test_eligible_merchant_is_matched_and_notified(): void
    {
        Queue::fake();
        Notification::fake();

        $category = Category::factory()->create();
        $merchant = $this->merchant($category);
        $request = $this->openRequest($category);

        $leads = app(MatchingService::class)->match($request);

        $this->assertCount(1, $leads);
        $this->assertDatabaseHas('leads', [
            'request_id' => $request->id,
            'merchant_profile_id' => $merchant->id,
            'status' => 'notified',
        ]);
        Notification::assertSentTo($merchant->user, NewLead::class);
    }

    public function test_unverified_merchant_is_excluded(): void
    {
        Queue::fake();
        $category = Category::factory()->create();
        $profile = MerchantProfile::factory()->create(['credits_balance' => 50, 'verified_at' => null]);
        $profile->categories()->attach($category);

        $leads = app(MatchingService::class)->match($this->openRequest($category));

        $this->assertCount(0, $leads);
    }

    public function test_merchant_without_credits_or_subscription_is_excluded(): void
    {
        Queue::fake();
        $category = Category::factory()->create();
        $this->merchant($category, ['credits_balance' => 0, 'subscription_tier' => 'none']);

        $leads = app(MatchingService::class)->match($this->openRequest($category));

        $this->assertCount(0, $leads);
    }

    public function test_merchant_on_subscription_without_credits_is_matched(): void
    {
        Queue::fake();
        Notification::fake();
        $category = Category::factory()->create();
        $this->merchant($category, ['credits_balance' => 0, 'subscription_tier' => 'gold']);

        $leads = app(MatchingService::class)->match($this->openRequest($category));

        $this->assertCount(1, $leads);
    }

    public function test_merchant_serving_a_different_category_is_excluded(): void
    {
        Queue::fake();
        $wanted = Category::factory()->create();
        $other = Category::factory()->create();
        $this->merchant($other);

        $leads = app(MatchingService::class)->match($this->openRequest($wanted));

        $this->assertCount(0, $leads);
    }

    public function test_parent_category_merchant_matches_child_request(): void
    {
        Queue::fake();
        Notification::fake();
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $this->merchant($parent); // serves the parent only

        $leads = app(MatchingService::class)->match($this->openRequest($child));

        $this->assertCount(1, $leads);
    }

    public function test_exact_category_scores_higher_than_parent_match(): void
    {
        Queue::fake();
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $exact = $this->merchant($child, ['lat' => null, 'lng' => null, 'rating_avg' => 0, 'win_rate' => 0, 'response_minutes_avg' => null]);
        $parentOnly = $this->merchant($parent, ['lat' => null, 'lng' => null, 'rating_avg' => 0, 'win_rate' => 0, 'response_minutes_avg' => null]);

        $service = app(MatchingService::class);
        $request = $this->openRequest($child, ['lat' => null, 'lng' => null]);

        $this->assertGreaterThan(
            $service->score($request, $parentOnly),
            $service->score($request, $exact),
        );
    }

    public function test_closer_merchant_scores_higher(): void
    {
        Queue::fake();
        $category = Category::factory()->create();
        $request = $this->openRequest($category, ['lat' => 30.4667, 'lng' => 31.1833]); // Banha

        $near = $this->merchant($category, ['lat' => 30.47, 'lng' => 31.18, 'rating_avg' => 0, 'win_rate' => 0, 'response_minutes_avg' => null]);
        $far = $this->merchant($category, ['lat' => 31.20, 'lng' => 29.92, 'rating_avg' => 0, 'win_rate' => 0, 'response_minutes_avg' => null]); // Alexandria

        $service = app(MatchingService::class);

        $this->assertGreaterThan(
            $service->score($request, $far),
            $service->score($request, $near),
        );
    }

    public function test_publishing_a_request_dispatches_matching(): void
    {
        Queue::fake();
        $category = Category::factory()->create();
        $buyer = User::factory()->create(['type' => 'buyer']);

        $request = Request::factory()->for($buyer, 'buyer')->create(['status' => 'draft', 'category_id' => $category->id]);
        Queue::assertNotPushed(MatchRequestJob::class);

        $request->publish();

        Queue::assertPushed(MatchRequestJob::class, fn ($job) => $job->request->is($request));
    }

    public function test_matching_is_idempotent_and_capped(): void
    {
        Queue::fake();
        Notification::fake();
        $category = Category::factory()->create();

        // More than MAX_LEADS eligible merchants.
        for ($i = 0; $i < MatchingService::MAX_LEADS + 5; $i++) {
            $this->merchant($category);
        }

        $service = app(MatchingService::class);
        $request = $this->openRequest($category);

        $service->match($request);
        $service->match($request); // re-run must not duplicate

        $this->assertSame(MatchingService::MAX_LEADS, Lead::where('request_id', $request->id)->count());
    }

    public function test_merchant_can_view_their_lead_and_it_is_marked_viewed(): void
    {
        $category = Category::factory()->create();
        $profile = $this->merchant($category);
        $lead = Lead::factory()->create(['merchant_profile_id' => $profile->id, 'status' => 'notified']);

        Volt::actingAs($profile->user)->test('merchant.leads.show', ['lead' => $lead])
            ->assertOk();

        $this->assertSame('viewed', $lead->fresh()->status);
    }

    public function test_merchant_cannot_view_another_merchants_lead(): void
    {
        $category = Category::factory()->create();
        $mine = $this->merchant($category);
        $theirs = $this->merchant($category);
        $lead = Lead::factory()->create(['merchant_profile_id' => $theirs->id]);

        $this->actingAs($mine->user)
            ->get(route('merchant.leads.show', $lead))
            ->assertForbidden();
    }
}
