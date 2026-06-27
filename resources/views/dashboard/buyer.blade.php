<x-app-layout>
    @php
        $user = auth()->user();
        $counts = [
            'active' => \App\Models\Request::forBuyer($user)->active()->count(),
            'draft' => \App\Models\Request::forBuyer($user)->where('status', 'draft')->count(),
            'completed' => \App\Models\Request::forBuyer($user)->where('status', 'completed')->count(),
        ];
        $offersReceived = \App\Models\Offer::whereHas('request', fn ($q) => $q->where('buyer_id', $user->id))->active()->count();
        $recent = \App\Models\Request::forBuyer($user)->active()->withCount(['offers' => fn ($q) => $q->whereIn('status', ['submitted', 'shortlisted', 'accepted'])])->with('category')->latest()->limit(5)->get();
        $recentOffers = \App\Models\Offer::whereHas('request', fn ($q) => $q->where('buyer_id', $user->id))
            ->with('merchantProfile', 'request')->active()->latest()->limit(4)->get();

        $stats = [
            ['key' => 'active', 'label' => __('Active requests'), 'value' => $counts['active'], 'icon' => 'inbox', 'tint' => 'text-indigo-600', 'href' => route('requests.index').'?filter=active'],
            ['key' => 'offers', 'label' => __('Offers received'), 'value' => $offersReceived, 'icon' => 'currency', 'tint' => 'text-emerald-600', 'href' => route('requests.index').'?filter=active'],
            ['key' => 'draft', 'label' => __('Drafts'), 'value' => $counts['draft'], 'icon' => 'document', 'tint' => 'text-gray-700', 'href' => route('requests.index').'?filter=draft'],
            ['key' => 'completed', 'label' => __('Completed'), 'value' => $counts['completed'], 'icon' => 'check', 'tint' => 'text-gray-700', 'href' => route('requests.index').'?filter=completed'],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 space-y-6">

            {{-- Welcome hero --}}
            <div class="relative overflow-hidden rounded-2xl bg-indigo-600 text-white p-6 sm:p-8">
                <div class="absolute -top-16 -end-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
                <div class="relative flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <div class="text-indigo-100 text-sm">{{ __('Buyer dashboard') }}</div>
                        <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold">{{ __('Welcome, :name', ['name' => $user->name]) }}</h1>
                        <p class="mt-1 text-indigo-100 text-sm">{{ __('Describe what you need and let merchants compete.') }}</p>
                    </div>
                    <a href="{{ route('requests.create') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-white text-indigo-700 font-semibold hover:bg-indigo-50 shrink-0">
                        <x-icon name="plus" class="w-5 h-5" /> {{ __('New request') }}
                    </a>
                </div>
            </div>

            {{-- Stat cards --}}
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <a href="{{ $stat['href'] }}" wire:navigate class="bg-white rounded-2xl ring-1 ring-gray-100 p-5 hover:ring-indigo-200 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">{{ $stat['label'] }}</span>
                            <span class="{{ $stat['tint'] }}"><x-icon :name="$stat['icon']" class="w-5 h-5" /></span>
                        </div>
                        <div class="mt-2 text-3xl font-bold {{ $stat['tint'] }}">{{ $stat['value'] }}</div>
                    </a>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Active requests --}}
                <div class="lg:col-span-2 bg-white rounded-2xl ring-1 ring-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                        <span class="font-semibold text-gray-900">{{ __('Active requests') }}</span>
                        <a href="{{ route('requests.index') }}" wire:navigate class="text-sm text-indigo-600 hover:underline">{{ __('View all') }}</a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @forelse ($recent as $request)
                            <a href="{{ route('requests.show', $request) }}" wire:navigate class="flex items-center justify-between gap-3 p-4 hover:bg-gray-50">
                                <div class="min-w-0">
                                    <div class="font-medium text-gray-900 truncate">{{ $request->title }}</div>
                                    <div class="text-sm text-gray-500 flex items-center gap-2">
                                        <x-icon name="tag" class="w-4 h-4 text-gray-300" /> {{ $request->category->label() }}
                                        @if ($request->offers_count)
                                            · <span class="text-emerald-600 font-medium">{{ $request->offers_count }} {{ __('Offers') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <x-request-status-badge :status="$request->status" class="shrink-0" />
                            </a>
                        @empty
                            <div class="p-10 text-center text-gray-400">
                                <x-icon name="inbox" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                                <p>{{ __('No active requests.') }}</p>
                                <a href="{{ route('requests.create') }}" wire:navigate class="mt-2 inline-block text-indigo-600 font-medium hover:underline">{{ __('Publish your first request') }}</a>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Latest offers --}}
                <div class="bg-white rounded-2xl ring-1 ring-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-50 font-semibold text-gray-900">{{ __('Latest offers') }}</div>
                    <div class="divide-y divide-gray-50">
                        @forelse ($recentOffers as $offer)
                            <a href="{{ route('requests.show', $offer->request) }}" wire:navigate class="block p-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-gray-900 truncate">{{ $offer->merchantProfile->business_name }}</span>
                                    <span class="text-indigo-600 font-semibold shrink-0">{{ $offer->price }} {{ __('EGP') }}</span>
                                </div>
                                <div class="text-xs text-gray-400 truncate mt-0.5">{{ $offer->request->title }}</div>
                            </a>
                        @empty
                            <div class="p-8 text-center text-sm text-gray-400">{{ __('No offers yet.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
