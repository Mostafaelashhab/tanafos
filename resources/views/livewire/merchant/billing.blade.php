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

    #[Computed]
    public function transactions()
    {
        return $this->profile()->creditTransactions()->limit(20)->get();
    }

    #[Computed]
    public function pending()
    {
        return $this->profile()->payments()->pending()->get();
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

<div class="max-w-2xl mx-auto px-4 py-5 space-y-6">

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-700 flex items-center gap-2">
            <x-icon name="check" class="w-5 h-5 shrink-0" /> {{ session('status') }}
        </div>
    @endif

    {{-- Balance hero --}}
    <div class="relative overflow-hidden rounded-3xl bg-brand-600 text-white p-6">
        <div class="absolute -top-12 -end-8 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="text-brand-100 text-sm">{{ __('Credits balance') }}</div>
                <div class="text-4xl font-extrabold mt-1">{{ $profile->credits_balance }}</div>
            </div>
            <div class="text-end">
                <div class="text-brand-100 text-sm">{{ __('Subscription') }}</div>
                <div class="text-lg font-bold mt-1">{{ $profile->onSubscription() ? __(ucfirst($profile->subscription_tier)) : __('None') }}</div>
            </div>
        </div>
    </div>

    {{-- Pending payments awaiting review --}}
    @if ($this->pending->isNotEmpty())
        <div class="bg-amber-50 ring-1 ring-amber-200 rounded-2xl p-4 space-y-2">
            <div class="text-sm font-bold text-amber-800 flex items-center gap-1.5">
                <x-icon name="clock" class="w-4 h-4" /> {{ __('Awaiting review') }}
            </div>
            @foreach ($this->pending as $p)
                <div class="flex items-center justify-between text-sm text-amber-900">
                    <span>{{ $p->itemLabel() }} · {{ $p->methodLabel() }}</span>
                    <span class="font-bold">{{ $p->amount }} {{ __('EGP') }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Credit packages --}}
    <section>
        <h2 class="font-bold text-gray-900 mb-3">{{ __('Buy lead credits') }}</h2>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($packages as $pkg)
                @php($featured = $pkg->key === 'growth')
                <div @class([
                    'rounded-2xl p-5 flex flex-col',
                    'bg-brand-600 text-white shadow-fab' => $featured,
                    'bg-white shadow-soft' => ! $featured,
                ])>
                    <div class="font-bold">{{ $pkg->label() }}</div>
                    <div class="mt-1 text-3xl font-extrabold">
                        {{ $pkg->isUnlimited() ? '∞' : $pkg->credits }}
                        <span class="text-xs font-medium {{ $featured ? 'text-brand-100' : 'text-gray-400' }}">{{ $pkg->isUnlimited() ? __('Unlimited') : __('credits') }}</span>
                    </div>
                    <div class="mt-1 font-bold {{ $featured ? 'text-white' : 'text-brand-600' }}">{{ $pkg->price }} {{ __('EGP') }}</div>
                    <a href="{{ route('merchant.checkout', ['kind' => 'package', 'key' => $pkg->key]) }}" wire:navigate
                            @class([
                                'mt-4 w-full text-center px-4 py-2.5 text-sm font-bold rounded-full',
                                'bg-white text-brand-700 hover:bg-brand-50' => $featured,
                                'bg-brand-600 text-white hover:bg-brand-700' => ! $featured,
                            ])>{{ __('Buy') }}</a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Subscription plans --}}
    <section>
        <h2 class="font-bold text-gray-900 mb-3">{{ __('Subscription plans') }}</h2>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($plans as $plan)
                @php($current = $profile->subscription_tier === $plan->tier)
                <div class="bg-white shadow-soft rounded-2xl p-5 flex flex-col {{ $current ? 'ring-2 ring-brand-500' : '' }}">
                    <div class="font-bold text-gray-900">{{ $plan->label() }}</div>
                    <div class="mt-1 text-brand-600 font-bold">{{ $plan->price }} <span class="text-xs font-medium text-gray-400">{{ __('EGP') }}/{{ __('mo') }}</span></div>
                    @if ($current)
                        <span class="mt-4 w-full text-center px-4 py-2.5 text-sm font-bold rounded-full bg-gray-100 text-gray-400">{{ __('Current plan') }}</span>
                    @else
                        <a href="{{ route('merchant.checkout', ['kind' => 'plan', 'key' => $plan->key]) }}" wire:navigate
                           class="mt-4 w-full text-center px-4 py-2.5 text-sm font-bold rounded-full bg-gray-900 text-white hover:bg-black">{{ __('Subscribe') }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Transaction history --}}
    <section class="bg-white shadow-soft rounded-3xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50 font-semibold text-gray-900">{{ __('History') }}</div>
        <div class="divide-y divide-gray-50">
            @forelse ($this->transactions as $tx)
                <div class="px-5 py-3 flex items-center gap-3 text-sm">
                    <span @class([
                        'w-9 h-9 rounded-full flex items-center justify-center shrink-0',
                        'bg-emerald-50 text-emerald-600' => $tx->amount >= 0,
                        'bg-rose-50 text-rose-500' => $tx->amount < 0,
                    ])>
                        <x-icon :name="$tx->amount >= 0 ? 'plus' : 'bolt'" class="w-4 h-4" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="text-gray-800 truncate">{{ $tx->description }}</div>
                        <div class="text-xs text-gray-400">{{ $tx->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="text-end shrink-0">
                        <div class="{{ $tx->amount >= 0 ? 'text-emerald-600' : 'text-rose-500' }} font-bold">{{ $tx->amount > 0 ? '+' : '' }}{{ $tx->amount }}</div>
                        @if ($tx->price)<div class="text-[11px] text-gray-400">{{ $tx->price }} {{ __('EGP') }}</div>@endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-400">{{ __('No transactions yet.') }}</div>
            @endforelse
        </div>
    </section>
</div>
