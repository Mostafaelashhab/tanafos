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
}
