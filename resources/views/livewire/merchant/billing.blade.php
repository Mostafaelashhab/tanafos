<?php

use App\Services\CreditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function profile()
    {
        return Auth::user()->merchantProfile;
    }

    // NOTE: payment capture (Paymob/Fawry/etc.) is not wired — this simulates a
    // successful payment then applies the package/plan. Swap in a gateway callback later.
    public function buy(string $packageKey, CreditService $credits): void
    {
        $credits->purchasePackage($this->profile(), $packageKey);
        session()->flash('status', __('Purchase applied. Your credits are updated.'));
    }

    public function subscribe(string $planKey, CreditService $credits): void
    {
        $credits->subscribe($this->profile(), $planKey);
        session()->flash('status', __('Subscription updated.'));
    }

    #[Computed]
    public function transactions()
    {
        return $this->profile()->creditTransactions()->limit(20)->get();
    }

    public function with(): array
    {
        return [
            'profile' => $this->profile(),
            'packages' => \App\Models\CreditPackage::active()->get(),
            'plans' => \App\Models\Plan::active()->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
        <h1 class="font-semibold text-2xl text-gray-800">{{ __('Credits & billing') }}</h1>

        @if (session('status'))
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        {{-- Balance --}}
        <div class="bg-white shadow-soft rounded-2xl p-6 flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">{{ __('Credits balance') }}</div>
                <div class="text-3xl font-bold text-brand-600">{{ $profile->credits_balance }}</div>
            </div>
            <div class="text-end">
                <div class="text-sm text-gray-500">{{ __('Subscription') }}</div>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $profile->onSubscription() ? __(ucfirst($profile->subscription_tier)) : __('None') }}
                </div>
            </div>
        </div>

        {{-- Credit packages --}}
        <div>
            <h2 class="font-semibold text-gray-900 mb-3">{{ __('Buy lead credits') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($packages as $pkg)
                    <div class="bg-white shadow-soft rounded-2xl p-6 flex flex-col">
                        <div class="font-semibold text-gray-900">{{ $pkg->label() }}</div>
                        <div class="mt-1 text-2xl font-bold text-gray-800">
                            {{ $pkg->isUnlimited() ? __('Unlimited') : $pkg->credits }}
                            <span class="text-sm font-normal text-gray-500">{{ $pkg->isUnlimited() ? '' : __('credits') }}</span>
                        </div>
                        <div class="mt-1 text-brand-600 font-semibold">{{ $pkg->price }} {{ __('EGP') }}</div>
                        <button wire:click="buy('{{ $pkg->key }}')"
                                class="mt-4 w-full px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-md hover:bg-brand-700">
                            {{ __('Buy') }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Subscription plans --}}
        <div>
            <h2 class="font-semibold text-gray-900 mb-3">{{ __('Subscription plans') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($plans as $plan)
                    @php($current = $profile->subscription_tier === $plan->tier)
                    <div class="bg-white shadow-soft rounded-2xl p-6 flex flex-col {{ $current ? 'ring-2 ring-brand-500' : '' }}">
                        <div class="font-semibold text-gray-900">{{ $plan->label() }}</div>
                        <div class="mt-1 text-brand-600 font-semibold">{{ $plan->price }} {{ __('EGP') }}/{{ __('mo') }}</div>
                        <button wire:click="subscribe('{{ $plan->key }}')"
                                @disabled($current)
                                class="mt-4 w-full px-4 py-2 text-sm font-semibold rounded-md
                                       {{ $current ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-800 text-white hover:bg-gray-900' }}">
                            {{ $current ? __('Current plan') : __('Subscribe') }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Transaction history --}}
        <div class="bg-white shadow-soft rounded-2xl">
            <div class="px-6 py-4 border-b text-sm font-medium text-gray-700">{{ __('History') }}</div>
            <div class="divide-y">
                @forelse ($this->transactions as $tx)
                    <div class="px-6 py-3 flex items-center justify-between text-sm">
                        <div>
                            <div class="text-gray-800">{{ $tx->description }}</div>
                            <div class="text-xs text-gray-400">{{ $tx->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="text-end">
                            <div class="{{ $tx->amount >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                {{ $tx->amount > 0 ? '+' : '' }}{{ $tx->amount }}
                            </div>
                            @if ($tx->price)
                                <div class="text-xs text-gray-400">{{ $tx->price }} {{ __('EGP') }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">{{ __('No transactions yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
