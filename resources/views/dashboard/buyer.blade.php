<x-app-layout>
    @php
        $user = auth()->user();
        $active = \App\Models\Request::forBuyer($user)->active()
            ->withCount(['offers' => fn ($q) => $q->whereIn('status', ['submitted', 'shortlisted', 'accepted'])])
            ->with('category')->latest()->limit(6)->get();
        $recentOffers = \App\Models\Offer::whereHas('request', fn ($q) => $q->where('buyer_id', $user->id))
            ->with('merchantProfile', 'request')->active()->latest()->limit(4)->get();
        $firstName = explode(' ', trim($user->name))[0];
    @endphp

    <div class="max-w-2xl mx-auto px-4 py-5 space-y-6">

        {{-- Greeting --}}
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400">{{ __('Hi,') }}</p>
                <h1 class="text-xl font-extrabold text-gray-900">{{ $firstName }} 👋</h1>
            </div>
        </div>

        {{-- Compose hero (the primary action — feels like a search/compose bar, not a dashboard) --}}
        <a href="{{ route('requests.create') }}" wire:navigate
           class="block relative overflow-hidden rounded-3xl bg-indigo-600 text-white p-6 active:scale-[.99] transition">
            <div class="absolute -top-12 -end-8 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <h2 class="text-2xl font-extrabold leading-snug">ماذا تحتاج اليوم؟</h2>
                <p class="mt-1 text-indigo-100 text-sm">اكتب طلبك ودع التجار يتنافسون عليك.</p>
                <span class="mt-4 inline-flex items-center gap-2 bg-white text-indigo-700 font-semibold rounded-full px-5 py-2.5 text-sm">
                    <x-icon name="plus" class="w-5 h-5" /> {{ __('New request') }}
                </span>
            </div>
        </a>

        {{-- Active requests --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-gray-900">{{ __('Your requests') }}</h2>
                <a href="{{ route('requests.index') }}" wire:navigate class="text-sm text-indigo-600">{{ __('View all') }}</a>
            </div>

            @forelse ($active as $request)
                <a href="{{ route('requests.show', $request) }}" wire:navigate
                   class="flex items-center gap-3 bg-white rounded-2xl ring-1 ring-gray-100 p-4 mb-3 active:bg-gray-50 transition">
                    <span class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <x-icon name="tag" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-gray-900 truncate">{{ $request->title }}</span>
                        <span class="block text-xs text-gray-400">{{ $request->category->label() }}</span>
                    </span>
                    @if ($request->offers_count)
                        <span class="shrink-0 inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full px-2.5 py-1">
                            {{ $request->offers_count }} <span class="font-medium">{{ __('Offers') }}</span>
                        </span>
                    @else
                        <x-request-status-badge :status="$request->status" class="shrink-0" />
                    @endif
                </a>
            @empty
                <div class="bg-white rounded-2xl ring-1 ring-gray-100 p-8 text-center">
                    <span class="inline-flex w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 items-center justify-center mb-3">
                        <x-icon name="inbox" class="w-7 h-7" />
                    </span>
                    <p class="text-gray-500">{{ __('No active requests yet.') }}</p>
                    <p class="text-sm text-gray-400 mt-1">{{ __('Tap above to publish your first one.') }}</p>
                </div>
            @endforelse
        </section>

        {{-- Latest offers feed --}}
        @if ($recentOffers->isNotEmpty())
            <section>
                <h2 class="font-bold text-gray-900 mb-3">{{ __('Latest offers') }}</h2>
                @foreach ($recentOffers as $offer)
                    <a href="{{ route('requests.show', $offer->request) }}" wire:navigate
                       class="flex items-center gap-3 bg-white rounded-2xl ring-1 ring-gray-100 p-4 mb-3 active:bg-gray-50 transition">
                        <span class="w-11 h-11 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center font-bold shrink-0">
                            {{ mb_substr($offer->merchantProfile->business_name, 0, 1) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-gray-900 truncate">{{ $offer->merchantProfile->business_name }}</span>
                            <span class="block text-xs text-gray-400 truncate">{{ $offer->request->title }}</span>
                        </span>
                        <span class="shrink-0 font-bold text-indigo-600">{{ $offer->price }} <span class="text-xs font-medium">{{ __('EGP') }}</span></span>
                    </a>
                @endforeach
            </section>
        @endif

    </div>
</x-app-layout>
