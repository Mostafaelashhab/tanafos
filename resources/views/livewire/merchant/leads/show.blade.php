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
            'exempt' => (bool) $r->commission_exempt,
        ];
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 py-5 space-y-5">
    @php($request = $lead->request)

    {{-- Request summary --}}
    <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <span class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                <x-icon :name="\App\Support\CategoryFields::icon($request->category)" class="w-6 h-6" />
            </span>
            <div class="min-w-0 flex-1">
                <h1 class="font-extrabold text-xl text-gray-900 leading-snug">{{ $request->title }}</h1>
                <div class="text-sm text-gray-400">{{ $request->category->label() }}</div>
            </div>
            <div class="text-center shrink-0 bg-brand-50 rounded-2xl px-3 py-1.5">
                <div class="text-lg font-extrabold text-brand-600 leading-none">{{ $lead->quality_score }}%</div>
                <div class="text-[10px] text-brand-400 mt-0.5">{{ __('Match') }}</div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            @if ($exempt)
                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 rounded-full px-3 py-1.5 font-bold">
                    <x-icon name="bolt" class="w-4 h-4" /> {{ __('No commission') }}
                </span>
            @endif
            @if ($budget)
                <span class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 rounded-full px-3 py-1.5 font-semibold"><x-icon name="currency" class="w-4 h-4" /> {{ $budget }}</span>
            @endif
            @if ($request->city)
                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5"><x-icon name="map-pin" class="w-4 h-4" /> {{ $request->city }}</span>
            @endif
            @if ($lead->distance_km !== null)
                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5"><x-icon name="map-pin" class="w-4 h-4" /> {{ $lead->distance_km }} {{ __('km away') }}</span>
            @endif
            <span class="bg-gray-100 text-gray-600 rounded-full px-3 py-1.5">{{ __(ucfirst($request->condition)) }}</span>
            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-3 py-1.5"><x-icon name="clock" class="w-4 h-4" /> {{ __(ucfirst($request->urgency)) }}</span>
        </div>

        @if ($exempt && ($request->contact_phone || $request->source_url))
            <div class="mt-4 rounded-2xl bg-emerald-50/60 border border-emerald-100 p-4 space-y-2 text-sm">
                <p class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                    <x-icon name="badge-check" class="w-4 h-4" /> {{ __('Imported demand · free to contact') }}
                </p>
                @if ($request->contact_phone)
                    <a href="tel:{{ $request->contact_phone }}" class="flex items-center gap-2 font-semibold text-emerald-800" dir="ltr">
                        <x-icon name="phone" class="w-4 h-4" /> {{ $request->contact_phone }}
                    </a>
                @endif
                @if ($request->source_url)
                    <a href="{{ $request->source_url }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-emerald-700 underline break-all">
                        <x-icon name="arrow-left" class="w-4 h-4 shrink-0" /> {{ __('View original post') }}
                    </a>
                @endif
            </div>
        @endif

        @if (! empty($request->specifications))
            <div class="mt-4 rounded-2xl bg-gray-50 p-4 grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                @foreach ($request->specifications as $key => $value)
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-400">{{ __(\Illuminate\Support\Str::headline($key)) }}</span>
                        <span class="text-gray-800 font-medium text-end">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($request->description)
            <p class="mt-4 text-gray-700 text-sm whitespace-pre-line leading-relaxed">{{ $request->description }}</p>
        @endif

        @if ($request->attachments->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($request->attachments as $attachment)
                    <img src="{{ $attachment->url() }}" class="h-24 w-24 rounded-2xl object-cover" />
                @endforeach
            </div>
        @endif
    </div>

    {{-- Offer: submitted / form / blocked --}}
    @if ($lead->offer)
        <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">
            <div class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-full px-2.5 py-1 mb-3">
                <x-icon name="check" class="w-4 h-4" /> {{ __('Your offer') }}
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">{{ __('Submitted. The buyer will review and respond.') }}</span>
                <span class="text-2xl font-extrabold text-brand-600 shrink-0">{{ $lead->offer->price }} <span class="text-xs font-medium">{{ $lead->offer->currency }}</span></span>
            </div>
            @if ($request->buyer_id)
                <button wire:click="chat" class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100">
                    <x-icon name="chat" class="w-4 h-4" /> {{ __('Chat with buyer') }}
                </button>
            @elseif ($request->contact_phone)
                <a href="tel:{{ $request->contact_phone }}" class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100" dir="ltr">
                    <x-icon name="phone" class="w-4 h-4" /> {{ $request->contact_phone }}
                </a>
            @endif
        </div>
    @elseif ($canOffer)
        <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">
            <h2 class="font-extrabold text-lg mb-1">{{ __('Submit your offer') }}</h2>
            @if ($exempt)
                <p class="mb-4 text-sm flex items-center gap-1.5 text-emerald-600 font-semibold">
                    <x-icon name="bolt" class="w-4 h-4" /> {{ __('This is imported demand — offering is free (no credit used).') }}
                </p>
            @elseif (! $onSubscription)
                <p class="mb-4 text-sm flex items-center gap-1.5 {{ $canAfford ? 'text-gray-400' : 'text-red-600' }}">
                    <x-icon name="bolt" class="w-4 h-4" /> {{ __('Submitting an offer uses 1 credit. Balance: :n', ['n' => $credits]) }}
                </p>
            @endif

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
                    <textarea wire:model="form.description" id="offer_description" rows="3" class="field mt-1"></textarea>
                    <x-input-error :messages="$errors->get('form.description')" class="mt-2" />
                </div>

                <label class="flex items-center gap-2.5 rounded-2xl bg-gray-50 px-4 py-3 cursor-pointer">
                    <input type="checkbox" wire:model="form.negotiation_enabled" class="field-check" />
                    <span class="text-sm font-medium text-gray-700">{{ __('Allow negotiation') }}</span>
                </label>

                <button type="submit" @disabled(! $exempt && ! $canAfford)
                        class="w-full inline-flex items-center justify-center gap-2 h-12 rounded-full bg-brand-600 text-white font-bold text-[15px] shadow-fab active:scale-[.98] transition disabled:opacity-50 disabled:shadow-none">
                    <x-icon name="bolt" class="w-5 h-5" /> {{ __('Submit offer') }}
                </button>
            </form>
        </div>
    @else
        <div class="bg-white shadow-soft rounded-3xl p-8 text-center text-gray-500">
            <span class="inline-flex w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 items-center justify-center mb-3">
                <x-icon name="x-mark" class="w-7 h-7" />
            </span>
            <p>{{ __('This lead is no longer open for offers.') }}</p>
        </div>
    @endif
</div>
