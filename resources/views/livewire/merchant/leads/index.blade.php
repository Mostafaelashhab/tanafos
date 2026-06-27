<?php

use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Computed]
    public function leads()
    {
        return Lead::query()
            ->forMerchant(Auth::user()->merchantProfile)
            ->open()
            ->with('request.category')
            ->orderByDesc('quality_score')
            ->latest()
            ->paginate(15);
    }
}; ?>

<div class="py-10">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <h1 class="font-semibold text-2xl text-gray-800 mb-6">{{ __('New leads') }}</h1>

        <div class="bg-white shadow-soft rounded-2xl divide-y">
            @forelse ($this->leads as $lead)
                <a href="{{ route('merchant.leads.show', $lead) }}" wire:navigate
                   class="flex items-center justify-between p-4 hover:bg-gray-50">
                    <div>
                        <div class="font-medium text-gray-900">{{ $lead->request->title }}</div>
                        <div class="text-sm text-gray-500">
                            {{ $lead->request->category->label() }}
                            @if ($lead->distance_km !== null)
                                · {{ $lead->distance_km }} {{ __('km away') }}
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-xs text-gray-400">{{ __('Match') }}</div>
                        <div class="text-lg font-semibold text-brand-600">{{ $lead->quality_score }}%</div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-gray-500">{{ __('No new leads yet.') }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->leads->links() }}</div>
    </div>
</div>
