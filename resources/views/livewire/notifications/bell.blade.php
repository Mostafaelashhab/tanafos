<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    #[Computed]
    public function items()
    {
        return Auth::user()->notifications()->latest()->limit(8)->get();
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->items);
    }

    public function open(string $id): void
    {
        $note = Auth::user()->notifications()->find($id);
        $note?->markAsRead();

        $this->redirect(\App\Support\Notifications::url($note), navigate: true);
    }
}; ?>

<div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
    <button @click="open = !open" wire:poll.30s
            class="relative flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 text-gray-600">
        <x-icon name="bell" class="w-6 h-6" />
        @if ($this->unreadCount > 0)
            <span class="absolute top-1.5 {{ app()->getLocale() === 'ar' ? 'left-1.5' : 'right-1.5' }} min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" x-transition
         class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-80 max-w-[90vw] bg-white rounded-xl shadow-lg shadow-soft overflow-hidden z-50">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <span class="font-semibold text-gray-900">{{ __('Notifications') }}</span>
            @if ($this->unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs text-brand-600 hover:underline">{{ __('Mark all read') }}</button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
            @forelse ($this->items as $note)
                <button wire:click="open('{{ $note->id }}')"
                        class="w-full text-start px-4 py-3 hover:bg-gray-50 flex gap-3 {{ $note->read_at ? '' : 'bg-brand-50/40' }}">
                    <span class="mt-0.5 text-brand-500 shrink-0">
                        <x-icon :name="\App\Support\Notifications::icon($note)" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm text-gray-800">{{ \App\Support\Notifications::title($note) }}</span>
                        <span class="block text-xs text-gray-400">{{ $note->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">{{ __('No notifications yet.') }}</div>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}" wire:navigate
           class="block px-4 py-3 text-center text-sm text-brand-600 hover:bg-gray-50 border-t border-gray-100">
            {{ __('View all') }}
        </a>
    </div>
</div>
