<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Merchant dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php($profile = auth()->user()->merchantProfile)

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-gray-900">{{ $profile?->business_name }}</div>
                    @if ($profile)
                        <x-merchant-badges :profile="$profile" class="mt-2" />
                    @endif
                </div>
                <a href="{{ route('merchant.billing') }}" wire:navigate class="text-end">
                    <div class="text-sm text-gray-500">{{ __('Credits balance') }}</div>
                    <div class="text-2xl font-semibold text-indigo-600">{{ $profile?->credits_balance ?? 0 }}</div>
                    <div class="text-xs text-indigo-500 underline">{{ __('Buy credits') }}</div>
                </a>
            </div>

            @php($newLeads = $profile ? $profile->leads()->open()->count() : 0)

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('merchant.leads.index') }}" wire:navigate class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('New leads') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-indigo-600">{{ $newLeads }}</div>
                </a>
                @foreach ([
                    __('Submitted offers'),
                    __('Win rate'),
                    __('Conversion rate'),
                ] as $card)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500">{{ $card }}</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-400">—</div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-500">
                {{ __('Submitting offers on leads arrives in Phase 3.') }}
            </div>
        </div>
    </div>
</x-app-layout>
