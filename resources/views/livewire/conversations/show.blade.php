<?php

use App\Events\MessageSent;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public Conversation $conversation;
    public string $body = '';

    public function mount(Conversation $conversation): void
    {
        abort_unless($conversation->includes(Auth::user()), 403);
        $this->conversation = $conversation;
        $this->markRead();
    }

    /** Real-time: re-render when a message is broadcast on this conversation's private channel. */
    #[On('echo-private:conversations.{conversation.id},MessageSent')]
    public function onMessageSent(): void
    {
        $this->markRead();
    }

    public function send(): void
    {
        $this->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $this->conversation->messages()->create([
            'sender_id' => Auth::id(),
            'body' => $this->body,
        ]);

        $this->conversation->forceFill(['last_message_at' => $message->created_at])->save();
        $this->body = '';

        broadcast(new MessageSent($message))->toOthers();

        // Notify the other participant (DB + live broadcast).
        $recipient = Auth::id() === $this->conversation->buyer_id
            ? $this->conversation->merchantProfile->user
            : $this->conversation->buyer;
        $recipient?->notify(new \App\Notifications\NewMessage($message));
    }

    /** Mark messages from the other participant as read. */
    public function markRead(): void
    {
        $this->conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::id())
            ->update(['read_at' => now()]);
    }

    public function with(): array
    {
        $this->markRead();

        return [
            'messages' => $this->conversation->messages()->with('sender')->get(),
            'request' => $this->conversation->request,
        ];
    }
}; ?>

<div class="py-10" wire:poll.5s="$refresh">{{-- Polling is the primary update mechanism (shared hosting); Echo takes over automatically when a websocket service is configured --}}
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="font-semibold text-xl text-gray-800">{{ $request->title }}</h1>
            <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-gray-600 underline">{{ __('Back') }}</a>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg p-4 h-[28rem] overflow-y-auto flex flex-col gap-2">
            @forelse ($messages as $message)
                @php($mine = $message->sender_id === auth()->id())
                <div class="max-w-[75%] {{ $mine ? 'self-end' : 'self-start' }}">
                    <div class="rounded-2xl px-4 py-2 text-sm {{ $mine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                        {{ $message->body }}
                    </div>
                    <div class="text-[11px] text-gray-400 mt-0.5 {{ $mine ? 'text-end' : '' }}">
                        {{ $message->created_at->diffForHumans() }}
                    </div>
                </div>
            @empty
                <div class="m-auto text-sm text-gray-400">{{ __('No messages yet. Say hello!') }}</div>
            @endforelse
        </div>

        <form wire:submit="send" class="flex items-center gap-2">
            <input wire:model="body" type="text" autocomplete="off"
                   class="flex-1 border-gray-300 rounded-full focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="{{ __('Type a message…') }}" />
            <x-primary-button class="rounded-full">{{ __('Send') }}</x-primary-button>
        </form>
        <x-input-error :messages="$errors->get('body')" />
    </div>
</div>
