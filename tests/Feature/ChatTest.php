<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{Conversation, User, User} conversation with its buyer and merchant user. */
    private function conversation(): array
    {
        $buyer = User::factory()->create(['type' => 'buyer']);
        $profile = MerchantProfile::factory()->create();
        $request = Request::factory()->published()->for($buyer, 'buyer')->create();
        $conversation = Conversation::factory()->create([
            'request_id' => $request->id,
            'buyer_id' => $buyer->id,
            'merchant_profile_id' => $profile->id,
        ]);

        return [$conversation, $buyer, $profile->user];
    }

    public function test_participants_can_send_messages(): void
    {
        [$conversation, $buyer, $merchantUser] = $this->conversation();

        Volt::actingAs($buyer)->test('conversations.show', ['conversation' => $conversation])
            ->set('body', 'هل المنتج متوفر؟')
            ->call('send');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => 'هل المنتج متوفر؟',
        ]);
        $this->assertNotNull($conversation->fresh()->last_message_at);

        Volt::actingAs($merchantUser)->test('conversations.show', ['conversation' => $conversation])
            ->set('body', 'نعم متوفر')
            ->call('send');

        $this->assertDatabaseCount('messages', 2);
    }

    public function test_outsider_cannot_view_a_conversation(): void
    {
        [$conversation] = $this->conversation();
        $outsider = User::factory()->create(['type' => 'buyer']);

        $this->actingAs($outsider)
            ->get(route('conversations.show', $conversation))
            ->assertForbidden();
    }

    public function test_opening_marks_other_partys_messages_read(): void
    {
        [$conversation, $buyer, $merchantUser] = $this->conversation();
        $conversation->messages()->create(['sender_id' => $merchantUser->id, 'body' => 'مرحبا']);

        Volt::actingAs($buyer)->test('conversations.show', ['conversation' => $conversation]);

        $this->assertNotNull($conversation->messages()->first()->read_at);
    }

    public function test_message_body_is_required(): void
    {
        [$conversation, $buyer] = $this->conversation();

        Volt::actingAs($buyer)->test('conversations.show', ['conversation' => $conversation])
            ->set('body', '')
            ->call('send')
            ->assertHasErrors('body');
    }
}
