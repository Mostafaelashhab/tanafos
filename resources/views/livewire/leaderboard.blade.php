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

<div class="py-10">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <h1 class="font-semibold text-2xl text-gray-800 mb-2">{{ __('Top merchants') }}</h1>
        <p class="text-sm text-gray-500 mb-6">{{ __('Ranked by completed deals and rating.') }}</p>

        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($this->merchants as $i => $merchant)
                <div class="flex items-center gap-4 p-4">
                    <div class="w-8 text-center text-lg font-bold {{ $i < 3 ? 'text-amber-500' : 'text-gray-400' }}">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">{{ $merchant->business_name }}</div>
                        <x-merchant-badges :profile="$merchant" class="mt-1" />
                    </div>
                    <div class="text-end shrink-0">
                        <div class="text-sm font-semibold text-gray-800">{{ $merchant->completed_deals }} {{ __('deals') }}</div>
                        @if ($merchant->rating_avg > 0)
                            <div class="text-xs text-amber-500">★ {{ $merchant->rating_avg }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">{{ __('No ranked merchants yet.') }}</div>
            @endforelse
        </div>
    </div>
</div>
