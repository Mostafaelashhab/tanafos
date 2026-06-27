<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Notifications\NewLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class NotificationsUiTest extends TestCase
{
    use RefreshDatabase;

    private function merchantWithLead(): array
    {
        $profile = MerchantProfile::factory()->verified()->create();
        $request = Request::factory()->published()->create();
        $lead = Lead::factory()->create(['request_id' => $request->id, 'merchant_profile_id' => $profile->id]);
        $profile->user->notify(new NewLead($lead));

        return [$profile->user, $lead];
    }

    public function test_bell_shows_unread_count_and_marks_all_read(): void
    {
        [$user] = $this->merchantWithLead();

        $component = Volt::actingAs($user)->test('notifications.bell');
        $this->assertSame(1, $component->instance()->unreadCount);

        $component->call('markAllRead');
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_page_renders_for_any_user(): void
    {
        [$user] = $this->merchantWithLead();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('Notifications'));
    }

    public function test_opening_a_notification_marks_it_read_and_redirects(): void
    {
        [$user, $lead] = $this->merchantWithLead();
        $id = $user->notifications()->first()->id;

        Volt::actingAs($user)->test('notifications.bell')
            ->call('open', $id)
            ->assertRedirect(route('merchant.leads.show', $lead));

        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_winning_merchant_is_notified(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $request = \App\Models\Request::factory()->published()->create();
        $offer = \App\Models\Offer::factory()->create(['request_id' => $request->id]);

        app(\App\Services\SelectionService::class)->selectWinner($offer);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $offer->merchantProfile->user,
            \App\Notifications\OfferAccepted::class
        );
    }

    public function test_chat_recipient_is_notified_on_new_message(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $buyer = \App\Models\User::factory()->create(['type' => 'buyer']);
        $profile = MerchantProfile::factory()->create();
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();
        $conversation = \App\Models\Conversation::factory()->create([
            'request_id' => $request->id,
            'buyer_id' => $buyer->id,
            'merchant_profile_id' => $profile->id,
        ]);

        Volt::actingAs($buyer)->test('conversations.show', ['conversation' => $conversation])
            ->set('body', 'متى التسليم؟')
            ->call('send');

        \Illuminate\Support\Facades\Notification::assertSentTo($profile->user, \App\Notifications\NewMessage::class);
    }
}
