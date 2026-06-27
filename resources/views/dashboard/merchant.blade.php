<x-app-layout>
    @php
        $user = auth()->user();
        $profile = $user->merchantProfile;
        $firstName = explode(' ', trim($user->name))[0];
        $leads = $profile
            ? $profile->leads()->open()->whereDoesntHave('offer')->with('request.category')->orderByDesc('quality_score')->limit(8)->get()
            : collect();
        $wonThisMonth = $profile ? $profile->offers()->where('status', 'accepted')->count() : 0;
    @endphp

    <div class="max-w-2xl mx-auto px-4 py-5 space-y-6">

        {{-- Greeting + status chip --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-400">{{ __('Hi,') }}</p>
                <h1 class="text-xl font-extrabold text-gray-900">{{ $firstName }} 👋</h1>
            </div>
            <a href="{{ route('merchant.billing') }}" wire:navigate
               class="flex items-center gap-2 bg-white shadow-soft rounded-full ps-3 pe-1 py-1">
                <span class="text-sm font-bold text-brand-600">{{ $profile?->credits_balance ?? 0 }}</span>
                <span class="text-xs text-gray-400">{{ __('credits') }}</span>
                <span class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center">
                    <x-icon name="plus" class="w-4 h-4" />
                </span>
            </a>
        </div>

        {{-- Level + badges strip --}}
        @if ($profile)
            <x-merchant-badges :profile="$profile" />
        @endif

        {{-- Low-credit nudge --}}
        @if ($profile && ! $profile->onSubscription() && $profile->credits_balance < 1)
            <a href="{{ route('merchant.billing') }}" wire:navigate
               class="flex items-center gap-3 bg-amber-50 ring-1 ring-amber-200 text-amber-800 rounded-2xl p-4">
                <x-icon name="bolt" class="w-5 h-5 shrink-0" />
                <span class="text-sm flex-1">{{ __('You are out of credits. Top up to send offers.') }}</span>
                <x-icon name="arrow-left" class="w-4 h-4 shrink-0 rotate-180" />
            </a>
        @endif

        {{-- Opportunities feed (the core merchant screen) --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-gray-900">{{ __('New opportunities') }}</h2>
                <a href="{{ route('merchant.leads.index') }}" wire:navigate class="text-sm text-brand-600">{{ __('View all') }}</a>
            </div>

            @forelse ($leads as $lead)
                <a href="{{ route('merchant.leads.show', $lead) }}" wire:navigate
                   class="block bg-white rounded-2xl shadow-soft p-4 mb-3 active:bg-gray-50 transition">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900 truncate">{{ $lead->request->title }}</div>
                            <div class="text-xs text-gray-400 flex items-center gap-2 mt-0.5">
                                <x-icon name="tag" class="w-3.5 h-3.5" /> {{ $lead->request->category->label() }}
                                @if ($lead->distance_km !== null)
                                    · {{ $lead->distance_km }} {{ __('km away') }}
                                @endif
                            </div>
                        </div>
                        <div class="text-end shrink-0">
                            <div class="text-lg font-extrabold text-brand-600">{{ $lead->quality_score }}%</div>
                            <div class="text-[10px] text-gray-400">{{ __('Match') }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-2xl shadow-soft p-8 text-center">
                    <span class="inline-flex w-14 h-14 rounded-2xl bg-brand-50 text-brand-500 items-center justify-center mb-3">
                        <x-icon name="inbox" class="w-7 h-7" />
                    </span>
                    <p class="text-gray-500">{{ __('No new opportunities right now.') }}</p>
                    <p class="text-sm text-gray-400 mt-1">{{ __('We will notify you the moment a matching buyer posts.') }}</p>
                </div>
            @endforelse
        </section>

    </div>
</x-app-layout>
