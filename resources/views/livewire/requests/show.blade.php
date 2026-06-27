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

<div class="py-10">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        <div class="bg-white shadow-sm sm:rounded-lg p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="font-semibold text-2xl text-gray-900">{{ $request->title }}</h1>
                    <div class="mt-1 text-sm text-gray-500">{{ $request->category->label() }}</div>
                </div>
                <x-request-status-badge :status="$request->status" />
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                @if ($budget)
                    <div><dt class="text-gray-500">{{ __('Budget') }}</dt><dd class="text-gray-900">{{ $budget }}</dd></div>
                @endif
                @if ($request->city)
                    <div><dt class="text-gray-500">{{ __('City') }}</dt><dd class="text-gray-900">{{ $request->city }}</dd></div>
                @endif
                <div><dt class="text-gray-500">{{ __('Condition') }}</dt><dd class="text-gray-900">{{ __(ucfirst($request->condition)) }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Urgency') }}</dt><dd class="text-gray-900">{{ __(ucfirst($request->urgency)) }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Payment method') }}</dt><dd class="text-gray-900">{{ __(ucfirst($request->payment_method)) }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Warranty required') }}</dt><dd class="text-gray-900">{{ $request->warranty_required ? __('Yes') : __('No') }}</dd></div>
            </dl>

            @if ($request->description)
                <div class="mt-6">
                    <h2 class="text-sm font-medium text-gray-500">{{ __('Additional details') }}</h2>
                    <p class="mt-1 text-gray-800 whitespace-pre-line">{{ $request->description }}</p>
                </div>
            @endif

            @if ($request->attachments->isNotEmpty())
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($request->attachments as $attachment)
                        <img src="{{ $attachment->url() }}" class="h-24 w-24 rounded object-cover border" />
                    @endforeach
                </div>
            @endif

            @if (! empty($request->specifications))
                <div class="mt-6">
                    <h2 class="text-sm font-medium text-gray-500">{{ __('Specifications') }} <span class="text-xs text-indigo-400">✨ {{ __('AI') }}</span></h2>
                    <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
                        @foreach ($request->specifications as $key => $value)
                            <div class="flex justify-between gap-2 border-b border-gray-100 py-1">
                                <dt class="text-gray-500">{{ $key }}</dt>
                                <dd class="text-gray-800 text-end">{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('requests.index') }}" wire:navigate class="text-sm text-gray-600 underline">{{ __('Back to requests') }}</a>
            <div class="flex items-center gap-3">
                @can('update', $request)
                    @if ($request->isDraft())
                        <x-primary-button wire:click="publish">{{ __('Publish') }}</x-primary-button>
                    @endif
                    <a href="{{ route('requests.edit', $request) }}" wire:navigate
                       class="text-sm text-indigo-600 underline">{{ __('Edit') }}</a>
                @endcan
                @can('delete', $request)
                    <button wire:click="delete" wire:confirm="{{ __('Delete this request?') }}"
                            class="text-sm text-red-600 underline">{{ __('Delete') }}</button>
                @endcan
            </div>
        </div>

{{-- Offers comparison --}}
        @php($offers = $this->offers())
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">{{ __('Offers') }} ({{ $offers->count() }})</h2>
                @if ($offers->count() > 1)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-500">{{ __('Sort by') }}</span>
                        <select wire:model.live="sort" class="text-sm border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="price">{{ __('Lowest price') }}</option>
                            <option value="delivery">{{ __('Fastest delivery') }}</option>
                            <option value="rating">{{ __('Top rated') }}</option>
                        </select>
                    </div>
                @endif
            </div>

            <div class="divide-y">
                @forelse ($offers as $offer)
                    <div class="p-6 flex items-start justify-between gap-4">
                        <div>
                            <div class="font-medium text-gray-900">{{ $offer->merchantProfile->business_name }}</div>
                            <div class="mt-1 text-sm text-gray-500 flex flex-wrap gap-x-4 gap-y-1">
                                @if ($offer->merchantProfile->rating_avg > 0)
                                    <span>★ {{ $offer->merchantProfile->rating_avg }}</span>
                                @endif
                                @if ($offer->delivery_days !== null)
                                    <span>{{ __('Delivery: :n days', ['n' => $offer->delivery_days]) }}</span>
                                @endif
                                @if ($offer->warranty)
                                    <span>{{ __('Warranty') }}: {{ $offer->warranty }}</span>
                                @endif
                                @if ($offer->lead?->distance_km !== null)
                                    <span>{{ $offer->lead->distance_km }} {{ __('km away') }}</span>
                                @endif
                            </div>
                            @if ($offer->description)
                                <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $offer->description }}</p>
                            @endif
                        </div>
                        <div class="text-end shrink-0">
                            <div class="text-xl font-semibold text-indigo-600">{{ $offer->price }} {{ $offer->currency }}</div>
                            @if ($offer->negotiation_enabled)
                                <div class="text-xs text-green-600 mt-1">{{ __('Negotiable') }}</div>
                            @endif

                            @if ($offer->isAccepted())
                                <div class="mt-2 text-xs font-semibold text-green-700">✓ {{ __('Winner') }}</div>
                            @endif

                            <div class="mt-3 flex flex-col items-stretch gap-2">
                                <button wire:click="chat({{ $offer->id }})"
                                        class="text-sm text-indigo-600 underline">{{ __('Chat') }}</button>
                                @if (! $request->isCompleted())
                                    <x-primary-button wire:click="selectWinner({{ $offer->id }})"
                                                      wire:confirm="{{ __('Select this offer as the winner?') }}">
                                        {{ __('Select winner') }}
                                    </x-primary-button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">{{ __('No offers yet. Merchants are reviewing your request.') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Review the winning merchant --}}
        @if ($request->isCompleted())
            <div class="bg-white shadow-sm sm:rounded-lg p-6 sm:p-8">
                @if ($request->review)
                    <h2 class="font-semibold text-gray-900">{{ __('Your review') }}</h2>
                    <div class="mt-2 text-amber-500">{{ str_repeat('★', $request->review->rating) }}{{ str_repeat('☆', 5 - $request->review->rating) }}</div>
                    @if ($request->review->comment)
                        <p class="mt-2 text-sm text-gray-700">{{ $request->review->comment }}</p>
                    @endif
                @else
                    <h2 class="font-semibold text-gray-900 mb-4">
                        {{ __('Rate :merchant', ['merchant' => $request->selectedOffer->merchantProfile->business_name]) }}
                    </h2>
                    <form wire:submit="submitReview" class="space-y-4">
                        <div>
                            <x-input-label :value="__('Rating')" />
                            <select wire:model="rating" class="mt-1 border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} ★</option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="comment" :value="__('Comment')" />
                            <textarea wire:model="comment" id="comment" rows="3"
                                      class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                        </div>
                        <div class="flex justify-end">
                            <x-primary-button>{{ __('Submit review') }}</x-primary-button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
