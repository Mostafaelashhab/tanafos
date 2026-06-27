<?php

use App\Exceptions\InsufficientCreditsException;
use App\Livewire\Forms\OfferForm;
use App\Models\Conversation;
use App\Models\Lead;
use App\Services\OfferService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public Lead $lead;
    public OfferForm $form;

    public function mount(Lead $lead): void
    {
        $this->authorize('view', $lead);
        $lead->markViewed();
        $this->lead = $lead->load('request.category', 'request.attachments', 'offer');
    }

    public function submit(): void
    {
        $this->authorize('submitOffer', $this->lead);
        $this->form->validate();

        try {
            app(OfferService::class)->submit(Auth::user()->merchantProfile, $this->lead, $this->form->payload());
        } catch (InsufficientCreditsException) {
            $this->addError('form.price', __('You do not have enough credits to submit an offer.'));
            return;
        }

        session()->flash('status', __('Your offer has been submitted.'));
        $this->redirectRoute('merchant.leads.index', navigate: true);
    }

    public function chat(): void
    {
        $conversation = Conversation::firstOrCreate(
            ['request_id' => $this->lead->request_id, 'merchant_profile_id' => $this->lead->merchant_profile_id],
            ['buyer_id' => $this->lead->request->buyer_id],
        );

        $this->redirectRoute('conversations.show', $conversation, navigate: true);
    }

    public function with(): array
    {
        $r = $this->lead->request;
        $merchant = Auth::user()->merchantProfile;

        return [
            'budget' => match (true) {
                $r->budget_min && $r->budget_max => "{$r->budget_min} – {$r->budget_max} {$r->currency}",
                (bool) $r->budget_max => __('Up to :n :c', ['n' => $r->budget_max, 'c' => $r->currency]),
                (bool) $r->budget_min => __('From :n :c', ['n' => $r->budget_min, 'c' => $r->currency]),
                default => null,
            },
            'canOffer' => Auth::user()->can('submitOffer', $this->lead),
            'canAfford' => $merchant->canSubmitOffer(),
            'onSubscription' => $merchant->onSubscription(),
            'credits' => $merchant->credits_balance,
        ];
    }
}; ?>

<div class="py-10">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @php($request = $lead->request)

        <div class="bg-white shadow-sm sm:rounded-lg p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="font-semibold text-2xl text-gray-900">{{ $request->title }}</h1>
                    <div class="mt-1 text-sm text-gray-500">{{ $request->category->label() }}</div>
                </div>
                <div class="text-end">
                    <div class="text-xs text-gray-400">{{ __('Match') }}</div>
                    <div class="text-2xl font-semibold text-indigo-600">{{ $lead->quality_score }}%</div>
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                @if ($budget)
                    <div><dt class="text-gray-500">{{ __('Budget') }}</dt><dd class="text-gray-900">{{ $budget }}</dd></div>
                @endif
                @if ($request->city)
                    <div><dt class="text-gray-500">{{ __('City') }}</dt><dd class="text-gray-900">{{ $request->city }}</dd></div>
                @endif
                @if ($lead->distance_km !== null)
                    <div><dt class="text-gray-500">{{ __('Distance') }}</dt><dd class="text-gray-900">{{ $lead->distance_km }} {{ __('km away') }}</dd></div>
                @endif
                <div><dt class="text-gray-500">{{ __('Condition') }}</dt><dd class="text-gray-900">{{ __(ucfirst($request->condition)) }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Urgency') }}</dt><dd class="text-gray-900">{{ __(ucfirst($request->urgency)) }}</dd></div>
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
        </div>

<a href="{{ route('merchant.leads.index') }}" wire:navigate class="inline-block text-sm text-gray-600 underline">{{ __('Back to leads') }}</a>

        {{-- Offer: already submitted, form, or blocked --}}
        @if ($lead->offer)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900">{{ __('Your offer') }}</h2>
                    <span class="text-lg font-semibold text-indigo-600">{{ $lead->offer->price }} {{ $lead->offer->currency }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-500">{{ __('Submitted. The buyer will review and respond.') }}</p>
                <button wire:click="chat" class="mt-3 text-sm text-indigo-600 underline">{{ __('Chat with buyer') }}</button>
            </div>
        @elseif ($canOffer)
            <div class="bg-white shadow-sm sm:rounded-lg p-6 sm:p-8">
                <h2 class="font-semibold text-gray-900 mb-4">{{ __('Submit your offer') }}</h2>

                @unless ($onSubscription)
                    <p class="mb-4 text-sm {{ $canAfford ? 'text-gray-500' : 'text-red-600' }}">
                        {{ __('Submitting an offer uses 1 credit. Balance: :n', ['n' => $credits]) }}
                    </p>
                @endunless

                <form wire:submit="submit" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="price" :value="__('Price (EGP)')" />
                            <x-text-input wire:model="form.price" id="price" type="number" min="1" class="block mt-1 w-full" required />
                            <x-input-error :messages="$errors->get('form.price')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="delivery_days" :value="__('Delivery (days)')" />
                            <x-text-input wire:model="form.delivery_days" id="delivery_days" type="number" min="0" class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('form.delivery_days')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="warranty" :value="__('Warranty')" />
                        <x-text-input wire:model="form.warranty" id="warranty" type="text" class="block mt-1 w-full" placeholder="{{ __('e.g. 1 year') }}" />
                    </div>

                    <div>
                        <x-input-label for="offer_description" :value="__('Offer details')" />
                        <textarea wire:model="form.description" id="offer_description" rows="3"
                                  class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                        <x-input-error :messages="$errors->get('form.description')" class="mt-2" />
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="form.negotiation_enabled"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">{{ __('Allow negotiation') }}</span>
                    </label>

                    <div class="flex justify-end">
                        <x-primary-button :disabled="! $canAfford">{{ __('Submit offer') }}</x-primary-button>
                    </div>
                </form>
            </div>
        @else
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-500">
                {{ __('This lead is no longer open for offers.') }}
            </div>
        @endif
    </div>
</div>
