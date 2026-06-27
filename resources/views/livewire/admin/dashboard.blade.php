<?php

use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Offer;
use App\Models\Request;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function with(): array
    {
        return [
            'stats' => [
                ['label' => __('Buyers'), 'value' => User::where('type', 'buyer')->count(), 'icon' => 'shopping-bag'],
                ['label' => __('Merchants'), 'value' => User::where('type', 'merchant')->count(), 'icon' => 'storefront'],
                ['label' => __('Verified merchants'), 'value' => MerchantProfile::whereNotNull('verified_at')->count(), 'icon' => 'badge-check'],
                ['label' => __('Requests'), 'value' => Request::count(), 'icon' => 'document'],
                ['label' => __('Open requests'), 'value' => Request::where('status', 'open')->count(), 'icon' => 'inbox'],
                ['label' => __('Completed deals'), 'value' => Request::where('status', 'completed')->count(), 'icon' => 'check'],
                ['label' => __('Offers'), 'value' => Offer::count(), 'icon' => 'currency'],
                ['label' => __('Leads'), 'value' => Lead::count(), 'icon' => 'bolt'],
            ],
        ];
    }
}; ?>

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="font-bold text-2xl text-gray-900 mb-6">{{ __('Admin') }}</h1>
        @include('admin._nav')

        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="bg-white rounded-xl ring-1 ring-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">{{ $stat['label'] }}</span>
                        <span class="text-indigo-400"><x-icon :name="$stat['icon']" class="w-5 h-5" /></span>
                    </div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stat['value']) }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
