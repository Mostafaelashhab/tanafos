<?php

use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';
    #[Url]
    public ?int $category = null;
    #[Url]
    public string $city = '';
    #[Url]
    public string $type = 'all'; // all | free | paid
    #[Url]
    public string $status = 'all'; // all | new | viewed | offered
    #[Url]
    public string $sort = 'match'; // match | latest | near

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'category', 'city', 'type', 'status');
        $this->sort = 'match';
        $this->resetPage();
    }

    private function profile()
    {
        return Auth::user()->merchantProfile;
    }

    #[Computed]
    public function leads()
    {
        $q = Lead::query()
            ->forMerchant($this->profile())
            ->with('request.category')
            ->whereHas('request');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $q->whereHas('request', fn ($r) => $r->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('description', 'like', $term)));
        }
        if ($this->category) {
            $q->whereHas('request', fn ($r) => $r->where('category_id', $this->category));
        }
        if ($this->city !== '') {
            $q->whereHas('request', fn ($r) => $r->where('city', $this->city));
        }
        if ($this->type === 'free') {
            $q->whereHas('request', fn ($r) => $r->where('commission_exempt', true));
        } elseif ($this->type === 'paid') {
            $q->whereHas('request', fn ($r) => $r->where('commission_exempt', false));
        }

        match ($this->status) {
            'new' => $q->where('status', 'notified'),
            'viewed' => $q->where('status', 'viewed'),
            'offered' => $q->where('status', 'offered'),
            default => null,
        };

        match ($this->sort) {
            'latest' => $q->latest(),
            'near' => $q->orderByRaw('distance_km is null, distance_km asc'),
            default => $q->orderByDesc('quality_score')->latest(),
        };

        return $q->paginate(12);
    }

    #[Computed]
    public function categories()
    {
        $cats = $this->profile()->categories()->where('is_active', true)->orderBy('sort_order')->get();

        return $cats->isNotEmpty()
            ? $cats
            : \App\Models\Category::where('is_active', true)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function cities()
    {
        return Lead::forMerchant($this->profile())
            ->join('requests', 'requests.id', '=', 'leads.request_id')
            ->whereNotNull('requests.city')->where('requests.city', '!=', '')
            ->distinct()->orderBy('requests.city')->pluck('requests.city');
    }

    #[Computed]
    public function stats(): array
    {
        $base = fn () => Lead::forMerchant($this->profile())->whereHas('request');

        return [
            'all' => $base()->count(),
            'new' => $base()->where('leads.status', 'notified')->count(),
            'free' => $base()->whereHas('request', fn ($r) => $r->where('commission_exempt', true))->count(),
        ];
    }

    public function with(): array
    {
        return [
            'activeFilters' => $this->search !== '' || $this->category || $this->city !== '' || $this->type !== 'all' || $this->status !== 'all',
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 py-5">

    {{-- Header + quick stats --}}
    <div class="flex items-end justify-between gap-3 mb-4">
        <div>
            <h1 class="font-extrabold text-2xl text-gray-900">{{ __('Opportunities') }}</h1>
            <p class="text-sm text-gray-400">{{ __('Browse demand and filter to what fits you.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-4">
        @foreach ([
            ['all', __('All'), 'inbox', 'bg-brand-50 text-brand-600'],
            ['new', __('New'), 'bell', 'bg-sky-50 text-sky-600'],
            ['free', __('Free'), 'bolt', 'bg-emerald-50 text-emerald-600'],
        ] as [$k, $label, $icon, $chip])
            <div class="bg-white rounded-2xl shadow-soft p-3 text-center">
                <span class="inline-flex w-9 h-9 rounded-xl {{ $chip }} items-center justify-center mb-1">
                    <x-icon :name="$icon" class="w-5 h-5" />
                </span>
                <div class="text-xl font-extrabold text-gray-900 leading-none">{{ $this->stats[$k] }}</div>
                <div class="text-[11px] text-gray-400 mt-1">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="relative mb-3">
        <span class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-4' : 'left-4' }} flex items-center text-gray-400">
            <x-icon name="search" class="w-5 h-5" />
        </span>
        <input wire:model.live.debounce.400ms="search" type="search"
               placeholder="{{ __('Search demand…') }}"
               class="field {{ app()->getLocale() === 'ar' ? 'pr-12' : 'pl-12' }}" />
    </div>

    {{-- Filter chips: type + status + sort --}}
    <div class="space-y-2 mb-3">
        <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1">
            @foreach (['all' => __('All'), 'free' => __('No commission'), 'paid' => __('Paid leads')] as $k => $label)
                <button wire:click="$set('type', '{{ $k }}')" @class([
                    'shrink-0 px-4 py-1.5 rounded-full text-sm font-semibold transition',
                    'bg-brand-600 text-white' => $type === $k,
                    'bg-white ring-1 ring-gray-200 text-gray-600' => $type !== $k,
                ])>{{ $label }}</button>
            @endforeach
            <span class="w-px bg-gray-200 mx-1 shrink-0"></span>
            @foreach (['all' => __('Any status'), 'new' => __('New'), 'viewed' => __('Seen'), 'offered' => __('Offered')] as $k => $label)
                <button wire:click="$set('status', '{{ $k }}')" @class([
                    'shrink-0 px-4 py-1.5 rounded-full text-sm font-semibold transition',
                    'bg-gray-900 text-white' => $status === $k,
                    'bg-white ring-1 ring-gray-200 text-gray-600' => $status !== $k,
                ])>{{ $label }}</button>
            @endforeach
        </div>

        {{-- Category chips --}}
        @if ($this->categories->isNotEmpty())
            <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1">
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
        @endif

        {{-- City + sort row --}}
        <div class="flex gap-2">
            @if ($this->cities->isNotEmpty())
                <select wire:model.live="city" class="field !w-auto flex-1 text-sm">
                    <option value="">{{ __('All cities') }}</option>
                    @foreach ($this->cities as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            @endif
            <select wire:model.live="sort" class="field !w-auto flex-1 text-sm">
                <option value="match">{{ __('Best match') }}</option>
                <option value="latest">{{ __('Newest') }}</option>
                <option value="near">{{ __('Nearest') }}</option>
            </select>
            @if ($activeFilters)
                <button wire:click="resetFilters" class="shrink-0 px-4 rounded-xl bg-gray-100 text-gray-500 text-sm font-semibold">{{ __('Clear') }}</button>
            @endif
        </div>
    </div>

    {{-- Results --}}
    <div class="space-y-3">
        @forelse ($this->leads as $lead)
            @php($r = $lead->request)
            <a href="{{ route('merchant.leads.show', $lead) }}" wire:navigate
               class="block bg-white shadow-soft rounded-2xl p-4 active:scale-[.99] transition">
                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                        <x-icon :name="\App\Support\CategoryFields::icon($r->category)" class="w-5 h-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-bold text-gray-900 leading-snug line-clamp-2">{{ $r->title }}</h3>
                            <div class="text-center shrink-0">
                                <div class="text-base font-extrabold text-brand-600 leading-none">{{ $lead->quality_score }}%</div>
                                <div class="text-[9px] text-brand-400">{{ __('Match') }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $r->category->label() }}</div>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                            @if ($r->commission_exempt)
                                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 rounded-full px-2 py-0.5 font-bold"><x-icon name="bolt" class="w-3 h-3" /> {{ __('Free') }}</span>
                            @endif
                            @if ($r->budget_max || $r->budget_min)
                                <span class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 rounded-full px-2 py-0.5 font-semibold"><x-icon name="currency" class="w-3 h-3" /> {{ $r->budget_min ?: '—' }}–{{ $r->budget_max ?: '—' }}</span>
                            @endif
                            @if ($r->city)
                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 rounded-full px-2 py-0.5"><x-icon name="map-pin" class="w-3 h-3" /> {{ $r->city }}</span>
                            @endif
                            @if ($lead->distance_km !== null)
                                <span class="bg-gray-100 text-gray-500 rounded-full px-2 py-0.5">{{ $lead->distance_km }} {{ __('km') }}</span>
                            @endif
                            @if ($lead->status === 'offered')
                                <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 rounded-full px-2 py-0.5 font-semibold"><x-icon name="check" class="w-3 h-3" /> {{ __('Offer sent') }}</span>
                            @elseif ($lead->status === 'notified')
                                <span class="bg-sky-50 text-sky-600 rounded-full px-2 py-0.5 font-semibold">{{ __('New') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="bg-white shadow-soft rounded-2xl p-10 text-center text-gray-500">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 items-center justify-center mb-3">
                    <x-icon name="inbox" class="w-7 h-7" />
                </span>
                <p class="font-medium">{{ $activeFilters ? __('No demand matches these filters.') : __('No opportunities yet.') }}</p>
                @if ($activeFilters)
                    <button wire:click="resetFilters" class="mt-3 text-sm text-brand-600 font-semibold underline">{{ __('Clear filters') }}</button>
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $this->leads->links() }}</div>
</div>
