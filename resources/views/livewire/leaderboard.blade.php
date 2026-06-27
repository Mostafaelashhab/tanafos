<?php

use App\Models\MerchantProfile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    #[Computed]
    public function merchants()
    {
        return MerchantProfile::query()
            ->whereNotNull('verified_at')
            ->orderByDesc('completed_deals')
            ->orderByDesc('rating_avg')
            ->limit(50)
            ->get();
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 py-5">
    <div class="flex items-center gap-2 mb-1">
        <span class="text-amber-500"><x-icon name="trophy" class="w-7 h-7" /></span>
        <h1 class="font-extrabold text-2xl text-gray-900">{{ __('Top merchants') }}</h1>
    </div>
    <p class="text-sm text-gray-400 mb-6">{{ __('Ranked by completed deals and rating.') }}</p>

    <div class="space-y-3">
        @forelse ($this->merchants as $i => $merchant)
            @php($rankColors = ['bg-amber-400 text-white', 'bg-gray-300 text-white', 'bg-orange-400 text-white'])
            <div @class([
                'flex items-center gap-3 bg-white shadow-soft rounded-2xl p-4',
                'ring-2 ring-amber-300' => $i === 0,
            ])>
                <div @class([
                    'w-9 h-9 rounded-full flex items-center justify-center font-extrabold shrink-0',
                    ($rankColors[$i] ?? 'bg-gray-100 text-gray-500'),
                ])>{{ $i + 1 }}</div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 truncate">{{ $merchant->business_name }}</div>
                    <x-merchant-badges :profile="$merchant" class="mt-1" />
                </div>
                <div class="text-end shrink-0">
                    <div class="text-sm font-bold text-gray-800">{{ $merchant->completed_deals }} <span class="font-medium text-gray-400">{{ __('deals') }}</span></div>
                    @if ($merchant->rating_avg > 0)
                        <div class="text-xs text-amber-500 inline-flex items-center gap-0.5"><x-icon name="star" class="w-3.5 h-3.5" /> {{ $merchant->rating_avg }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white shadow-soft rounded-2xl p-10 text-center text-gray-400">
                <x-icon name="trophy" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                {{ __('No ranked merchants yet.') }}
            </div>
        @endforelse
    </div>
</div>
