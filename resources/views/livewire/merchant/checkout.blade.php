<?php

use App\Models\CreditPackage;
use App\Models\Plan;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public string $kind;
    public string $key;
    public int $step = 1;

    public string $method = '';
    public string $sender_number = '';
    public string $reference = '';
    public $proof = null;

    public function mount(string $kind, string $key): void
    {
        abort_unless(in_array($kind, ['package', 'plan'], true), 404);
        $this->kind = $kind;
        $this->key = $key;
        abort_unless($this->item(), 404);
    }

    public function item(): CreditPackage|Plan|null
    {
        return $this->kind === 'package'
            ? CreditPackage::where('key', $this->key)->first()
            : Plan::where('key', $this->key)->first();
    }

    public function chooseMethod(string $method): void
    {
        abort_unless(array_key_exists($method, config('banha.payment.methods')), 404);
        $this->method = $method;
        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function submit(): void
    {
        $this->validate([
            'method' => ['required'],
            'sender_number' => ['required', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:100'],
            'proof' => ['nullable', 'image', 'max:5120'],
        ]);

        $merchant = Auth::user()->merchantProfile;
        $path = $this->proof ? $this->proof->store("payments/{$merchant->id}", 'public') : null;

        Payment::create([
            'merchant_profile_id' => $merchant->id,
            'kind' => $this->kind,
            'item_key' => $this->key,
            'method' => $this->method,
            'amount' => $this->item()->price,
            'sender_number' => $this->sender_number,
            'reference' => $this->reference ?: null,
            'proof_path' => $path,
            'status' => 'pending',
        ]);

        session()->flash('status', __('Payment submitted. We will activate it after review.'));
        $this->redirectRoute('merchant.billing', navigate: true);
    }

    public function with(): array
    {
        return [
            'item' => $this->item(),
            'payNumber' => config('banha.payment.number'),
            'methods' => config('banha.payment.methods'),
        ];
    }
}; ?>

<div class="max-w-md mx-auto px-4 py-5" x-data="{ copied: false }">

    {{-- Stepper --}}
    <div class="flex items-center justify-center gap-2 mb-6">
        @foreach ([1, 2, 3] as $n)
            <span @class([
                'w-8 h-1.5 rounded-full transition',
                'bg-brand-600 w-10' => $step === $n,
                'bg-brand-300' => $step > $n,
                'bg-gray-200' => $step < $n,
            ])></span>
        @endforeach
    </div>

    {{-- Item summary --}}
    <div class="bg-white shadow-soft rounded-2xl p-4 mb-4 flex items-center justify-between">
        <div>
            <div class="text-xs text-gray-400">{{ $kind === 'package' ? __('Credit package') : __('Plan') }}</div>
            <div class="font-bold text-gray-900">{{ $item->label() }}</div>
        </div>
        <div class="text-xl font-extrabold text-brand-600">{{ $item->price }} <span class="text-xs font-medium">{{ __('EGP') }}</span></div>
    </div>

    <div class="bg-white shadow-soft rounded-3xl p-5 sm:p-6">

        {{-- STEP 1: choose method --}}
        @if ($step === 1)
            <h2 class="font-extrabold text-lg mb-4">{{ __('Choose payment method') }}</h2>
            <div class="space-y-3">
                @foreach ($methods as $mkey => $m)
                    <button type="button" wire:click="chooseMethod('{{ $mkey }}')"
                            class="w-full flex items-center gap-3 p-4 rounded-2xl border-2 border-gray-100 hover:border-brand-300 hover:bg-brand-50/40 transition text-start">
                        <span class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                            <x-icon name="credit-card" class="w-6 h-6" />
                        </span>
                        <span class="font-semibold text-gray-800 flex-1">{{ app()->getLocale() === 'ar' ? $m['name_ar'] : $m['name'] }}</span>
                        <x-icon name="arrow-left" class="w-5 h-5 text-gray-300" />
                    </button>
                @endforeach
            </div>
        @endif

        {{-- STEP 2: instructions --}}
        @if ($step === 2)
            <h2 class="font-extrabold text-lg mb-1">{{ __('Send the payment') }}</h2>
            <p class="text-sm text-gray-400 mb-5">{{ __('Transfer the amount via :method, then continue.', ['method' => config("banha.payment.methods.$method.name_ar")]) }}</p>

            <div class="rounded-2xl bg-brand-50 p-5 text-center">
                <div class="text-sm text-brand-700">{{ __('Amount') }}</div>
                <div class="text-3xl font-extrabold text-brand-700 mb-4">{{ $item->price }} {{ __('EGP') }}</div>

                <div class="text-sm text-brand-700">{{ __('To this number') }}</div>
                <div class="flex items-center justify-center gap-2 mt-1">
                    <span class="text-2xl font-extrabold text-gray-900 tracking-wider" dir="ltr" x-ref="num">{{ $payNumber }}</span>
                    <button type="button"
                            @click="navigator.clipboard.writeText($refs.num.textContent.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                            class="w-9 h-9 rounded-full bg-white text-brand-600 flex items-center justify-center shadow-sm">
                        <span x-show="!copied"><x-icon name="document" class="w-5 h-5" /></span>
                        <span x-show="copied" x-cloak class="text-emerald-600"><x-icon name="check" class="w-5 h-5" /></span>
                    </button>
                </div>
                <p x-show="copied" x-cloak class="text-xs text-emerald-600 mt-1">{{ __('Copied') }}</p>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="button" wire:click="back" class="px-5 py-2.5 rounded-full bg-gray-100 text-gray-700 font-semibold text-sm">{{ __('Back') }}</button>
                <div class="flex-1"></div>
                <x-primary-button wire:click="$set('step', 3)" type="button">{{ __('I have paid') }}</x-primary-button>
            </div>
        @endif

        {{-- STEP 3: proof --}}
        @if ($step === 3)
            <h2 class="font-extrabold text-lg mb-1">{{ __('Confirm your transfer') }}</h2>
            <p class="text-sm text-gray-400 mb-5">{{ __('Enter your transfer details so we can verify it.') }}</p>

            <form wire:submit="submit" class="space-y-5">
                <div>
                    <x-input-label for="sender" :value="__('Number you paid from')" />
                    <x-text-input wire:model="sender_number" id="sender" type="text" dir="ltr" class="block mt-1 w-full" placeholder="01xxxxxxxxx" />
                    <x-input-error :messages="$errors->get('sender_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="reference" :value="__('Transaction reference (optional)')" />
                    <x-text-input wire:model="reference" id="reference" type="text" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="proof" :value="__('Screenshot (optional)')" />
                    <input type="file" wire:model="proof" id="proof" accept="image/*" class="field-file mt-2" />
                    <x-input-error :messages="$errors->get('proof')" class="mt-2" />
                    <div wire:loading wire:target="proof" class="mt-2 text-sm text-gray-500">{{ __('Uploading…') }}</div>
                    @if ($proof)
                        <img src="{{ $proof->temporaryUrl() }}" class="mt-3 h-28 rounded-xl object-cover" />
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" wire:click="back" class="px-5 py-2.5 rounded-full bg-gray-100 text-gray-700 font-semibold text-sm">{{ __('Back') }}</button>
                    <x-primary-button class="flex-1 justify-center">{{ __('Submit for review') }}</x-primary-button>
                </div>
            </form>
        @endif
    </div>

    <p class="text-center text-xs text-gray-400 mt-4">{{ __('Your purchase is activated by an admin after the transfer is verified.') }}</p>
</div>
