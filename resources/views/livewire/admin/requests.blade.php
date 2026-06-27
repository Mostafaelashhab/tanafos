<?php

use App\Models\Request;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = 'all';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return Request::query()
            ->with(['buyer', 'category'])
            ->withCount('offers')
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);
    }
}; ?>

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="font-bold text-2xl text-gray-900 mb-6">{{ __('Admin') }}</h1>
        @include('admin._nav')

        <div class="flex flex-wrap gap-2 mb-4">
            @foreach (['all' => __('All'), 'open' => __('Open'), 'matched' => __('Matched'), 'completed' => __('Completed'), 'draft' => __('Drafts')] as $key => $label)
                <button wire:click="$set('status', '{{ $key }}')"
                        @class([
                            'px-3 py-1.5 rounded-full text-sm border',
                            'bg-indigo-600 text-white border-indigo-600' => $status === $key,
                            'bg-white text-gray-600 border-gray-200' => $status !== $key,
                        ])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="bg-white rounded-xl ring-1 ring-gray-100 divide-y divide-gray-50 overflow-hidden">
            @forelse ($this->requests as $request)
                <div class="px-5 py-4 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-900 truncate">{{ $request->title }}</div>
                        <div class="text-sm text-gray-500 truncate">
                            {{ $request->category?->label() }} · {{ $request->buyer?->name }} · {{ $request->offers_count }} {{ __('Offers') }}
                        </div>
                    </div>
                    <x-request-status-badge :status="$request->status" class="shrink-0" />
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">{{ __('No requests found.') }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->requests->links() }}</div>
    </div>
</div>
