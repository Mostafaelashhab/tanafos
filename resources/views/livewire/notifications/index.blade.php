<?php

use App\Support\Notifications;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Computed]
    public function notifications()
    {
        return Auth::user()->notifications()->latest()->paginate(20);
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
        unset($this->notifications);
    }

    public function open(string $id): void
    {
        $note = Auth::user()->notifications()->find($id);
        $note?->markAsRead();
        $this->redirect(Notifications::url($note), navigate: true);
    }
}; ?>

<div class="py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
            <h1 class="font-bold text-2xl text-gray-900">{{ __('Notifications') }}</h1>
            <div class="flex items-center gap-3">
                <x-push-button />
                <button wire:click="markAllRead" class="text-sm text-brand-600 hover:underline">{{ __('Mark all read') }}</button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-soft divide-y divide-gray-50 overflow-hidden">
            @forelse ($this->notifications as $note)
                <button wire:click="open('{{ $note->id }}')"
                        class="w-full text-start px-5 py-4 hover:bg-gray-50 flex gap-3 {{ $note->read_at ? '' : 'bg-brand-50/40' }}">
                    <span class="mt-0.5 text-brand-500 shrink-0">
                        <x-icon :name="\App\Support\Notifications::icon($note)" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-gray-800">{{ \App\Support\Notifications::title($note) }}</span>
                        <span class="block text-xs text-gray-400 mt-0.5">{{ $note->created_at->diffForHumans() }}</span>
                    </span>
                    @unless ($note->read_at)
                        <span class="mt-2 w-2 h-2 rounded-full bg-brand-500 shrink-0"></span>
                    @endunless
                </button>
            @empty
                <div class="px-5 py-16 text-center text-gray-400">
                    <x-icon name="bell" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->notifications->links() }}</div>
    </div>
</div>
