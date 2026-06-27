<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Offer;
use App\Models\Request;
use App\Models\User;
use App\Notifications\NewOffer;
use App\Services\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_a_message_broadcasts_message_sent(): void
    {
        Event::fake([MessageSent::class]);

        $buyer = User::factory()->create(['type' => 'buyer']);
        $profile = MerchantProfile::factory()->create();
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();
        $conversation = Conversation::factory()->create([
            'request_id' => $request->id,
            'buyer_id' => $buyer->id,
            'merchant_profile_id' => $profile->id,
        ]);

        Volt::actingAs($buyer)->test('conversations.show', ['conversation' => $conversation])
            ->set('body', 'مرحبا')
            ->call('send');

        Event::assertDispatched(MessageSent::class, fn (MessageSent $e) => $e->message->conversation_id === $conversation->id);
    }

    public function test_message_event_broadcasts_on_the_private_conversation_channel(): void
    {
        $conversation = Conversation::factory()->create();
        $message = $conversation->messages()->create([
            'sender_id' => $conversation->buyer_id,
            'body' => 'hi',
        ]);

        $channels = (new MessageSent($message))->broadcastOn();

        $this->assertSame('private-conversations.'.$conversation->id, $channels[0]->name);
    }

    public function test_submitting_an_offer_notifies_the_buyer_over_db_and_broadcast(): void
    {
        Notification::fake();

        $buyer = User::factory()->create(['type' => 'buyer']);
        $merchant = MerchantProfile::factory()->verified()->create(['credits_balance' => 5, 'subscription_tier' => 'none']);
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();
        $lead = Lead::factory()->create(['request_id' => $request->id, 'merchant_profile_id' => $merchant->id]);

        app(OfferService::class)->submit($merchant, $lead, ['price' => 9000]);

        Notification::assertSentTo($buyer, NewOffer::class, function (NewOffer $n) use ($buyer) {
            return in_array('database', $n->via($buyer), true)
                && in_array('broadcast', $n->via($buyer), true);
        });
    }

    public function test_conversation_channel_authorizes_only_participants(): void
    {
        $conversation = Conversation::factory()->create();
        $buyer = $conversation->buyer;
        $merchantUser = $conversation->merchantProfile->user;
        $outsider = User::factory()->create(['type' => 'buyer']);

        $this->assertTrue($conversation->includes($buyer));
        $this->assertTrue($conversation->includes($merchantUser));
        $this->assertFalse($conversation->includes($outsider));
    }
}
