<?php

use App\Exceptions\BidException;
use App\Models\Auction;
use App\Services\AuctionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public Auction $auction;
    public ?int $amount = null;

    public function mount(Auction $auction): void
    {
        $this->auction = $auction->load('seller', 'category');
    }

    #[Computed]
    public function bids()
    {
        return $this->auction->bids()->with('bidder')->latest()->take(30)->get();
    }

    public function placeBid(): void
    {
        $this->validate(['amount' => ['required', 'integer', 'min:1']]);

        try {
            app(AuctionService::class)->placeBid(Auth::user(), $this->auction, (int) $this->amount);
        } catch (BidException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->auction->refresh();
        $this->amount = null;
        unset($this->bids);
        session()->flash('status', __('Your bid is in — you are the highest bidder!'));
    }

    public function closeNow(): void
    {
        abort_unless($this->auction->seller_id === Auth::id(), 403);
        app(AuctionService::class)->close($this->auction);
        $this->auction->refresh();
        unset($this->bids);
    }

    public function cancel(): void
    {
        abort_unless($this->auction->seller_id === Auth::id(), 403);
        app(AuctionService::class)->cancel($this->auction);
        $this->auction->refresh();
    }

    public function with(): array
    {
        return [
            'isSeller' => $this->auction->seller_id === Auth::id(),
            'live' => $this->auction->isLive(),
            'minNext' => $this->auction->minNextBid(),
            'youLead' => $this->auction->highestBid && $this->auction->highestBid->bidder_id === Auth::id(),
        ];
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 py-5 space-y-4">

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 text-emerald-700 px-4 py-3 text-sm font-medium">{{ session('status') }}</div>
    @endif

    {{-- Item --}}
    <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <span class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                <x-icon :name="$auction->category ? \App\Support\CategoryFields::icon($auction->category) : 'gavel'" class="w-6 h-6" />
            </span>
            <div class="min-w-0 flex-1">
                <h1 class="font-extrabold text-xl text-gray-900 leading-snug">{{ $auction->title }}</h1>
                <div class="text-sm text-gray-400">{{ $auction->category?->label() ?? __('General') }} · {{ __(ucfirst($auction->condition)) }}</div>
            </div>
        </div>

        @if ($auction->city)
            <div class="mt-3 inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5 text-xs">
                <x-icon name="map-pin" class="w-4 h-4" /> {{ $auction->city }}
            </div>
        @endif

        @if ($auction->description)
            <p class="mt-4 text-gray-700 text-sm whitespace-pre-line leading-relaxed">{{ $auction->description }}</p>
        @endif
    </div>

    {{-- Live price + countdown (polls while live) --}}
    <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6" @if ($live) wire:poll.10s @endif>
        <div class="flex items-end justify-between gap-4">
            <div>
                <div class="text-xs text-gray-400">{{ $auction->bids_count > 0 ? __('Current bid') : __('Starting price') }}</div>
                <div class="text-3xl font-extrabold text-brand-600 leading-none mt-1">
                    {{ number_format($auction->current_price) }} <span class="text-sm font-medium text-gray-400">{{ $auction->currency }}</span>
                </div>
                <div class="text-xs text-gray-400 mt-1">{{ $auction->bids_count }} {{ __('bids') }}</div>
            </div>

            <div class="text-end">
                @if ($live)
                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1 text-[11px] font-bold mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> {{ __('Live') }}
                    </span>
                    <div x-data="{
                            end: {{ $auction->ends_at->getTimestamp() }} * 1000,
                            left: '',
                            tick() {
                                let d = Math.max(0, this.end - Date.now());
                                let s = Math.floor(d/1000), days = Math.floor(s/86400);
                                let h = Math.floor((s%86400)/3600), m = Math.floor((s%3600)/60), sec = s%60;
                                this.left = days > 0 ? days + ' {{ __('d') }} ' + h + ' {{ __('h') }}'
                                    : (h>0 ? h + ':' : '') + String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
                            }
                         }"
                         x-init="tick(); setInterval(() => tick(), 1000)"
                         class="font-extrabold text-gray-900 tabular-nums" x-text="left"></div>
                    <div class="text-[11px] text-gray-400">{{ __('time left') }}</div>
                @else
                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 rounded-full px-2.5 py-1 text-[11px] font-semibold">
                        {{ $auction->status === 'cancelled' ? __('Cancelled') : __('Ended') }}
                    </span>
                @endif
            </div>
        </div>

        @if ($auction->reserve_price && ! $auction->reserveMet())
            <div class="mt-3 text-xs text-amber-600 font-medium flex items-center gap-1">
                <x-icon name="bolt" class="w-4 h-4" /> {{ __('Reserve price not met yet') }}
            </div>
        @endif

        {{-- Bid form / states --}}
        @if ($live && ! $isSeller)
            @if ($youLead)
                <div class="mt-4 rounded-2xl bg-emerald-50 text-emerald-700 px-4 py-3 text-sm font-semibold flex items-center gap-2">
                    <x-icon name="check" class="w-5 h-5" /> {{ __('You are the highest bidder') }}
                </div>
            @endif
            <form wire:submit="placeBid" class="mt-4">
                <div class="flex gap-2">
                    <div class="flex-1">
                        <input wire:model="amount" type="number" min="{{ $minNext }}" inputmode="numeric"
                               placeholder="{{ __('Min :n :c', ['n' => number_format($minNext), 'c' => $auction->currency]) }}"
                               class="field" />
                    </div>
                    <button type="submit"
                            class="shrink-0 inline-flex items-center justify-center gap-2 px-6 h-12 rounded-xl bg-brand-600 text-white font-bold text-[15px] shadow-fab active:scale-[.98] transition">
                        <x-icon name="gavel" class="w-5 h-5" /> {{ __('Bid') }}
                    </button>
                </div>
                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ([0, 1, 4] as $mult)
                        @php($quick = $minNext + $mult * $auction->bid_increment)
                        <button type="button" wire:click="$set('amount', {{ $quick }})"
                                class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">{{ number_format($quick) }}</button>
                    @endforeach
                </div>
            </form>
        @elseif ($isSeller && $live)
            <div class="mt-4 flex gap-2">
                <button wire:click="closeNow" wire:confirm="{{ __('End the auction now and pick the highest bidder?') }}"
                        class="flex-1 h-11 rounded-full bg-brand-600 text-white font-bold text-sm active:scale-[.98] transition">{{ __('End auction now') }}</button>
                <button wire:click="cancel" wire:confirm="{{ __('Cancel this auction?') }}"
                        class="px-4 h-11 rounded-full bg-gray-100 text-gray-600 font-semibold text-sm">{{ __('Cancel') }}</button>
            </div>
            <p class="mt-2 text-xs text-gray-400 text-center">{{ __('You cannot bid on your own auction.') }}</p>
        @elseif (! $live)
            @if ($auction->winner_id)
                <div class="mt-4 rounded-2xl bg-brand-50 text-brand-700 px-4 py-3 text-sm font-semibold flex items-center gap-2">
                    <x-icon name="trophy" class="w-5 h-5" />
                    {{ $auction->winner_id === Auth::id() ? __('You won this auction!') : __('Won at :n :c', ['n' => number_format($auction->current_price), 'c' => $auction->currency]) }}
                </div>
            @else
                <div class="mt-4 rounded-2xl bg-gray-50 text-gray-500 px-4 py-3 text-sm">{{ __('This auction ended with no winner.') }}</div>
            @endif
        @endif
    </div>

    {{-- Bid history --}}
    <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">
        <h2 class="font-bold text-gray-900 mb-3">{{ __('Bid history') }}</h2>
        @forelse ($this->bids as $bid)
            <div class="flex items-center justify-between py-2.5 {{ ! $loop->last ? 'border-b border-gray-50' : '' }}">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ mb_substr($bid->bidder->name, 0, 1) }}
                    </span>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-800 truncate">{{ $bid->bidder->name }}</div>
                        <div class="text-[11px] text-gray-400">{{ $bid->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                <div class="text-end shrink-0">
                    <div class="font-extrabold text-gray-900">{{ number_format($bid->amount) }}</div>
                    @if ($bid->status === 'won')
                        <span class="text-[10px] font-bold text-brand-600">{{ __('Winner') }}</span>
                    @elseif ($bid->status === 'leading')
                        <span class="text-[10px] font-bold text-emerald-600">{{ __('Leading') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 py-4 text-center">{{ __('No bids yet — be the first!') }}</p>
        @endforelse
    </div>
</div>
