<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\NewLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private function sub(): array
    {
        return [
            'endpoint' => 'https://push.example.com/sub/'.fake()->uuid(),
            'keys' => ['p256dh' => 'BPubKeyExample', 'auth' => 'authSecret'],
            'contentEncoding' => 'aes128gcm',
        ];
    }

    public function test_user_can_store_a_push_subscription(): void
    {
        $user = User::factory()->create();
        $payload = $this->sub();

        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertNoContent();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'public_key' => 'BPubKeyExample',
        ]);
    }

    public function test_storing_the_same_endpoint_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $payload = $this->sub();

        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertNoContent();
        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertNoContent();

        $this->assertSame(1, $user->pushSubscriptions()->count());
    }

    public function test_user_can_unsubscribe(): void
    {
        $user = User::factory()->create();
        $payload = $this->sub();
        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertNoContent();

        $this->actingAs($user)->deleteJson(route('push.unsubscribe'), ['endpoint' => $payload['endpoint']])->assertNoContent();

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_subscribe_requires_auth(): void
    {
        $this->post(route('push.subscribe'), $this->sub())->assertRedirect(route('login'));
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_webpush_channel_is_added_only_when_configured(): void
    {
        $merchant = MerchantProfile::factory()->verified()->create();
        $request = Request::factory()->published()->create();
        $lead = Lead::factory()->create(['request_id' => $request->id, 'merchant_profile_id' => $merchant->id]);
        $notification = new NewLead($lead);

        config(['banha.push.enabled' => false]);
        $this->assertNotContains(WebPushChannel::class, $notification->via($merchant->user));

        config(['banha.push.enabled' => true]);
        $this->assertContains(WebPushChannel::class, $notification->via($merchant->user));
    }

    public function test_toWebPush_payload_has_title_body_and_url(): void
    {
        $merchant = MerchantProfile::factory()->verified()->create();
        $request = Request::factory()->published()->create(['title' => 'لابتوب']);
        $lead = Lead::factory()->create(['request_id' => $request->id, 'merchant_profile_id' => $merchant->id]);

        $payload = (new NewLead($lead))->toWebPush($merchant->user);

        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('body', $payload);
        $this->assertSame(route('merchant.leads.show', $lead->id), $payload['url']);
    }
}
