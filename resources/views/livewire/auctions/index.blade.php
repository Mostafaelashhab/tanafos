<?php

use App\Models\Auction;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $tab = 'live'; // live | ending | ended | mine
    #[Url]
    public string $search = '';
    #[Url]
    public ?int $category = null;

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    #[Computed]
    public function auctions()
    {
        $q = Auction::query()->with('category')->withCount('bids');

        match ($this->tab) {
            'mine' => $q->forSeller(Auth::user()),
            'ended' => $q->whereIn('status', ['ended', 'cancelled']),
            default => $q->live(),
        };

        if ($this->search !== '') {
            $q->where('title', 'like', '%'.$this->search.'%');
        }
        if ($this->category) {
            $q->where('category_id', $this->category);
        }

        $q = match ($this->tab) {
            'mine' => $q->latest(),
            'ended' => $q->latest('closed_at'),
            default => $q->orderBy('ends_at'), // ending soonest first
        };

        return $q->paginate(12);
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)->orderBy('sort_order')->get();
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 py-5">

    <div class="flex items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="font-extrabold text-2xl text-gray-900">{{ __('Auctions') }}</h1>
            <p class="text-sm text-gray-400">{{ __('List something and let people bid it up.') }}</p>
        </div>
        <a href="{{ route('auctions.create') }}" wire:navigate
           class="shrink-0 inline-flex items-center gap-2 px-4 h-11 rounded-full bg-brand-600 text-white font-bold text-sm shadow-fab active:scale-95 transition">
            <x-icon name="plus" class="w-5 h-5" /> {{ __('New auction') }}
        </a>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1 mb-3">
        @foreach (['live' => __('Live'), 'ended' => __('Ended'), 'mine' => __('My auctions')] as $k => $label)
            <button wire:click="$set('tab', '{{ $k }}')" @class([
                'shrink-0 px-5 py-2 rounded-full text-sm font-semibold transition',
                'bg-brand-600 text-white' => $tab === $k,
                'bg-white ring-1 ring-gray-200 text-gray-600' => $tab !== $k,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Search + category --}}
    <div class="relative mb-3">
        <span class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-4' : 'left-4' }} flex items-center text-gray-400">
            <x-icon name="search" class="w-5 h-5" />
        </span>
        <input wire:model.live.debounce.400ms="search" type="search" placeholder="{{ __('Search auctions…') }}"
               class="field {{ app()->getLocale() === 'ar' ? 'pr-12' : 'pl-12' }}" />
    </div>
    <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1 mb-4">
        <button wire:click="$set('category', null)" @class([
            'shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition',
            'bg-brand-50 text-brand-700 ring-1 ring-brand-200' => ! $category,
            'bg-white ring-1 ring-gray-200 text-gray-500' => (bool) $category,
        ])>{{ __('All categories') }}</button>
        @foreach ($this->categories as $cat)
            <button wire:click="$set('category', {{ $cat->id }})" @class([
                'shrink-0 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium transition',
                'bg-brand-50 text-brand-700 ring-1 ring-brand-200' => $category === $cat->id,
                'bg-white ring-1 ring-gray-200 text-gray-500' => $category !== $cat->id,
            ])>
                <x-icon :name="\App\Support\CategoryFields::icon($cat)" class="w-4 h-4" /> {{ $cat->name_ar }}
            </button>
        @endforeach
    </div>

    {{-- Grid --}}
    <div class="grid sm:grid-cols-2 gap-3">
        @forelse ($this->auctions as $auction)
            @php($live = $auction->isLive())
            <a href="{{ route('auctions.show', $auction) }}" wire:navigate
               class="block bg-white shadow-soft rounded-2xl p-4 active:scale-[.99] transition">
                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                        <x-icon :name="$auction->category ? \App\Support\CategoryFields::icon($auction->category) : 'gavel'" class="w-5 h-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-gray-900 leading-snug line-clamp-2">{{ $auction->title }}</h3>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $auction->category?->label() ?? __('General') }}</div>
                    </div>
                </div>

                <div class="mt-3 flex items-end justify-between">
                    <div>
                        <div class="text-[11px] text-gray-400">{{ $auction->bids_count > 0 ? __('Current bid') : __('Starting price') }}</div>
                        <div class="text-xl font-extrabold text-brand-600">{{ number_format($auction->current_price) }} <span class="text-xs font-medium text-gray-400">{{ $auction->currency }}</span></div>
                    </div>
                    <div class="text-end">
                        @if ($live)
                            <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1 text-[11px] font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> {{ __('Live') }}
                            </span>
                            <div class="text-[11px] text-gray-400 mt-1">{{ $auction->ends_at->diffForHumans(['parts' => 1]) }}</div>
                        @else
                            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 rounded-full px-2.5 py-1 text-[11px] font-semibold">{{ $auction->status === 'cancelled' ? __('Cancelled') : __('Ended') }}</span>
                        @endif
                        <div class="text-[11px] text-gray-400 mt-1">{{ $auction->bids_count }} {{ __('bids') }}</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="sm:col-span-2 bg-white shadow-soft rounded-2xl p-10 text-center text-gray-500">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 items-center justify-center mb-3">
                    <x-icon name="gavel" class="w-7 h-7" />
                </span>
                <p class="font-medium">{{ __('No auctions here yet.') }}</p>
                <a href="{{ route('auctions.create') }}" wire:navigate class="mt-3 inline-block text-sm text-brand-600 font-semibold underline">{{ __('Start an auction') }}</a>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $this->auctions->links() }}</div>
</div>
