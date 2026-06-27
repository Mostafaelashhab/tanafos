<?php

use App\Models\Request as DemandRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $filter = 'all'; // all | active | draft | completed

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return DemandRequest::query()
            ->forBuyer(Auth::user())
            ->with('category')
            ->withCount('attachments')
            ->when($this->filter === 'active', fn ($q) => $q->active())
            ->when($this->filter === 'draft', fn ($q) => $q->where('status', 'draft'))
            ->when($this->filter === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->latest()
            ->paginate(10);
    }
}; ?>

<div class="py-10">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-semibold text-2xl text-gray-800">{{ __('My requests') }}</h1>
            <a href="{{ route('requests.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                {{ __('New request') }}
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        {{-- Filters --}}
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (['all' => __('All'), 'active' => __('Active'), 'draft' => __('Drafts'), 'completed' => __('Completed')] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')"
                        class="px-3 py-1.5 rounded-full text-sm border
                               {{ $filter === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg divide-y">
            @forelse ($this->requests as $request)
                <a href="{{ route('requests.show', $request) }}" wire:navigate
                   class="flex items-center justify-between p-4 hover:bg-gray-50">
                    <div>
                        <div class="font-medium text-gray-900">{{ $request->title }}</div>
                        <div class="text-sm text-gray-500">
                            {{ $request->category->label() }}
                            @if ($request->attachments_count) · {{ $request->attachments_count }} {{ __('images') }} @endif
                        </div>
                    </div>
                    <x-request-status-badge :status="$request->status" />
                </a>
            @empty
                <div class="p-8 text-center text-gray-500">
                    {{ __('No requests yet.') }}
                    <a href="{{ route('requests.create') }}" wire:navigate class="text-indigo-600 underline">{{ __('Publish your first request') }}</a>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->requests->links() }}</div>
    </div>
</div>
