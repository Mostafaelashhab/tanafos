<?php

use App\Models\Conversation;
use App\Models\Request as DemandRequest;
use App\Services\ReviewService;
use App\Services\SelectionService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public DemandRequest $request;

    public string $sort = 'price'; // price | delivery | rating

    // Review form
    public int $rating = 5;
    public string $comment = '';

    public function mount(DemandRequest $request): void
    {
        $this->authorize('view', $request);
        $this->request = $request->load('category', 'attachments', 'selectedOffer.merchantProfile', 'review');
    }

    public function selectWinner(int $offerId, SelectionService $selection): void
    {
        $this->authorize('update', $this->request);

        $offer = $this->request->offers()->whereKey($offerId)->firstOrFail();
        $selection->selectWinner($offer);

        session()->flash('status', __('You selected the winning offer.'));
        $this->request->refresh()->load('selectedOffer.merchantProfile', 'review');
    }

    public function chat(int $offerId): void
    {
        $offer = $this->request->offers()->whereKey($offerId)->firstOrFail();

        $conversation = Conversation::firstOrCreate(
            ['request_id' => $this->request->id, 'merchant_profile_id' => $offer->merchant_profile_id],
            ['buyer_id' => $this->request->buyer_id],
        );

        $this->redirectRoute('conversations.show', $conversation, navigate: true);
    }

    public function submitReview(ReviewService $reviews): void
    {
        abort_unless($this->request->buyer_id === auth()->id(), 403);
        abort_unless($this->request->isCompleted() && ! $this->request->review, 403);

        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $reviews->submit($this->request, ['rating' => $this->rating, 'comment' => $this->comment ?: null]);

        session()->flash('status', __('Thanks for your review.'));
        $this->request->refresh()->load('selectedOffer.merchantProfile', 'review');
    }

    public function offers()
    {
        return $this->request->offers()
            ->active()
            ->with('merchantProfile', 'lead')
            ->get()
            ->sortBy(fn ($o) => match ($this->sort) {
                'delivery' => $o->delivery_days ?? PHP_INT_MAX,
                'rating' => -1 * (float) $o->merchantProfile->rating_avg,
                default => $o->price,
            })
            ->values();
    }

    public function publish(): void
    {
        $this->authorize('update', $this->request);

        if ($this->request->isDraft()) {
            $this->request->publish(); // fires matching via the model's saved event
            session()->flash('status', __('Your request is now live.'));
        }
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->request);
        $this->request->delete();
        session()->flash('status', __('Request deleted.'));
        $this->redirectRoute('requests.index', navigate: true);
    }

    public function with(): array
    {
        return ['budget' => $this->formatBudget()];
    }

    private function formatBudget(): ?string
    {
        $min = $this->request->budget_min;
        $max = $this->request->budget_max;

        return match (true) {
            $min && $max => "{$min} – {$max} {$this->request->currency}",
            (bool) $max => __('Up to :n :c', ['n' => $max, 'c' => $this->request->currency]),
            (bool) $min => __('From :n :c', ['n' => $min, 'c' => $this->request->currency]),
            default => null,
        };
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 py-5 space-y-5">
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-700 flex items-center gap-2">
            <x-icon name="check" class="w-5 h-5 shrink-0" /> {{ session('status') }}
        </div>
    @endif

    {{-- Request hero --}}
    <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <span class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                <x-icon :name="\App\Support\CategoryFields::icon($request->category)" class="w-6 h-6" />
            </span>
            <div class="min-w-0 flex-1">
                <h1 class="font-extrabold text-xl text-gray-900 leading-snug">{{ $request->title }}</h1>
                <div class="text-sm text-gray-400">{{ $request->category->label() }}</div>
            </div>
            <x-request-status-badge :status="$request->status" class="shrink-0" />
        </div>

        {{-- Meta chips --}}
        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            @if ($budget)
                <span class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 rounded-full px-3 py-1.5 font-semibold"><x-icon name="currency" class="w-4 h-4" /> {{ $budget }}</span>
            @endif
            @if ($request->city)
                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5"><x-icon name="map-pin" class="w-4 h-4" /> {{ $request->city }}</span>
            @endif
            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5">{{ __(ucfirst($request->condition)) }}</span>
            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5"><x-icon name="clock" class="w-4 h-4" /> {{ __(ucfirst($request->urgency)) }}</span>
            @if ($request->warranty_required)
                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5"><x-icon name="shield-check" class="w-4 h-4" /> {{ __('Warranty') }}</span>
            @endif
        </div>

        @if ($request->description)
            <p class="mt-4 text-gray-700 text-sm whitespace-pre-line leading-relaxed">{{ $request->description }}</p>
        @endif

        @if (! empty($request->specifications))
            <div class="mt-4 rounded-2xl bg-brand-50/50 p-4">
                <div class="text-xs font-semibold text-brand-700 flex items-center gap-1 mb-2">
                    <x-icon name="sparkles" class="w-4 h-4" /> {{ __('Specifications') }}
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                    @foreach ($request->specifications as $key => $value)
                        <div class="flex justify-between gap-2">
                            <span class="text-gray-400">{{ __(\Illuminate\Support\Str::headline($key)) }}</span>
                            <span class="text-gray-800 font-medium text-end">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($request->attachments->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($request->attachments as $attachment)
                    <img src="{{ $attachment->url() }}" class="h-24 w-24 rounded-2xl object-cover" />
                @endforeach
            </div>
        @endif

        {{-- Owner actions --}}
        @can('update', $request)
            <div class="mt-5 pt-4 border-t border-gray-50 flex items-center gap-2">
                @if ($request->isDraft())
                    <x-primary-button wire:click="publish">{{ __('Publish') }}</x-primary-button>
                @endif
                <a href="{{ route('requests.edit', $request) }}" wire:navigate
                   class="inline-flex items-center gap-1 px-3 py-2 rounded-full text-sm text-gray-600 bg-gray-100 hover:bg-gray-200">
                    <x-icon name="pencil" class="w-4 h-4" /> {{ __('Edit') }}
                </a>
                <div class="flex-1"></div>
                @can('delete', $request)
                    <button wire:click="delete" wire:confirm="{{ __('Delete this request?') }}"
                            class="inline-flex items-center gap-1 px-3 py-2 rounded-full text-sm text-red-600 hover:bg-red-50">
                        <x-icon name="trash" class="w-4 h-4" />
                    </button>
                @endcan
            </div>
        @endcan
    </div>

    {{-- Offers --}}
    @php($offers = $this->offers())
    <div class="flex items-center justify-between px-1">
        <h2 class="font-bold text-gray-900">{{ __('Offers') }} <span class="text-brand-600">({{ $offers->count() }})</span></h2>
    </div>

    {{-- Sort segmented control --}}
    @if ($offers->count() > 1)
        <div class="flex gap-1 bg-gray-100 rounded-full p-1 text-sm">
            @foreach (['price' => __('Lowest price'), 'delivery' => __('Fastest delivery'), 'rating' => __('Top rated')] as $key => $label)
                <button wire:click="$set('sort', '{{ $key }}')"
                        @class([
                            'flex-1 rounded-full py-1.5 font-medium transition',
                            'bg-white text-brand-700 shadow-sm' => $sort === $key,
                            'text-gray-500' => $sort !== $key,
                        ])>{{ $label }}</button>
            @endforeach
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($offers as $offer)
            <div @class([
                'bg-white shadow-soft rounded-2xl p-4',
                'ring-2 ring-emerald-400' => $offer->isAccepted(),
            ])>
                @if ($offer->isAccepted())
                    <div class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-full px-2.5 py-1 mb-3">
                        <x-icon name="check" class="w-4 h-4" /> {{ __('Winner') }}
                    </div>
                @endif

                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold shrink-0">
                        {{ mb_substr($offer->merchantProfile->business_name, 0, 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-gray-900 truncate">{{ $offer->merchantProfile->business_name }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                            @if ($offer->merchantProfile->rating_avg > 0)
                                <span class="inline-flex items-center gap-1 text-amber-500"><x-icon name="star" class="w-3.5 h-3.5" /> {{ $offer->merchantProfile->rating_avg }}</span>
                            @endif
                            @if ($offer->delivery_days !== null)
                                <span class="inline-flex items-center gap-1"><x-icon name="clock" class="w-3.5 h-3.5" /> {{ __('Delivery: :n days', ['n' => $offer->delivery_days]) }}</span>
                            @endif
                            @if ($offer->warranty)
                                <span class="inline-flex items-center gap-1"><x-icon name="shield-check" class="w-3.5 h-3.5" /> {{ $offer->warranty }}</span>
                            @endif
                            @if ($offer->lead?->distance_km !== null)
                                <span class="inline-flex items-center gap-1"><x-icon name="map-pin" class="w-3.5 h-3.5" /> {{ $offer->lead->distance_km }} {{ __('km away') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end shrink-0">
                        <div class="text-xl font-extrabold text-brand-600">{{ $offer->price }}</div>
                        <div class="text-[10px] text-gray-400">{{ __('EGP') }}</div>
                    </div>
                </div>

                @if ($offer->description)
                    <p class="mt-3 text-sm text-gray-600 whitespace-pre-line">{{ $offer->description }}</p>
                @endif

                <div class="mt-3 flex items-center gap-2">
                    @if ($offer->negotiation_enabled)
                        <span class="text-xs text-emerald-600 font-medium me-auto">{{ __('Negotiable') }}</span>
                    @else
                        <span class="me-auto"></span>
                    @endif
                    <button wire:click="chat({{ $offer->id }})"
                            class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100">
                        <x-icon name="chat" class="w-4 h-4" /> {{ __('Chat') }}
                    </button>
                    @if (! $request->isCompleted())
                        <x-primary-button wire:click="selectWinner({{ $offer->id }})"
                                          wire:confirm="{{ __('Select this offer as the winner?') }}">
                            {{ __('Select') }}
                        </x-primary-button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white shadow-soft rounded-2xl p-10 text-center">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-brand-50 text-brand-500 items-center justify-center mb-3">
                    <x-icon name="inbox" class="w-7 h-7" />
                </span>
                <p class="text-gray-500">{{ __('No offers yet. Merchants are reviewing your request.') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Review the winning merchant --}}
    @if ($request->isCompleted())
        <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">
            @if ($request->review)
                <h2 class="font-bold text-gray-900">{{ __('Your review') }}</h2>
                <div class="mt-2 text-amber-500 text-lg tracking-wide">{{ str_repeat('★', $request->review->rating) }}{{ str_repeat('☆', 5 - $request->review->rating) }}</div>
                @if ($request->review->comment)
                    <p class="mt-2 text-sm text-gray-700">{{ $request->review->comment }}</p>
                @endif
            @else
                <h2 class="font-bold text-gray-900 mb-4">
                    {{ __('Rate :merchant', ['merchant' => $request->selectedOffer->merchantProfile->business_name]) }}
                </h2>
                <form wire:submit="submitReview" class="space-y-4">
                    <div>
                        <x-input-label :value="__('Rating')" />
                        <div class="mt-2 flex gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('rating', {{ $i }})"
                                        class="text-3xl {{ $rating >= $i ? 'text-amber-400' : 'text-gray-200' }}">★</button>
                            @endfor
                        </div>
                        <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="comment" :value="__('Comment')" />
                        <textarea wire:model="comment" id="comment" rows="3"
                                  class="field mt-1"></textarea>
                        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                    </div>
                    <x-primary-button class="w-full">{{ __('Submit review') }}</x-primary-button>
                </form>
            @endif
        </div>
    @endif
</div>
