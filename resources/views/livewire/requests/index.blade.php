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

<div class="py-8">
    <div class="max-w-5xl mx-auto px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-bold text-2xl text-gray-900">{{ __('My requests') }}</h1>
            <a href="{{ route('requests.create') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-full hover:bg-brand-700">
                <x-icon name="plus" class="w-5 h-5" /> {{ __('New request') }}
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-green-50 p-4 text-sm text-green-700 flex items-center gap-2">
                <x-icon name="check" class="w-5 h-5" /> {{ session('status') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (['all' => __('All'), 'active' => __('Active'), 'draft' => __('Drafts'), 'completed' => __('Completed')] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')"
                        @class([
                            'px-4 py-1.5 rounded-full text-sm font-medium border transition',
                            'bg-brand-600 text-white border-brand-600' => $filter === $key,
                            'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' => $filter !== $key,
                        ])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl shadow-soft divide-y divide-gray-50 overflow-hidden">
            @forelse ($this->requests as $request)
                <a href="{{ route('requests.show', $request) }}" wire:navigate
                   class="flex items-center justify-between gap-3 p-4 hover:bg-gray-50">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-900 truncate">{{ $request->title }}</div>
                        <div class="text-sm text-gray-500 flex items-center gap-2 mt-0.5">
                            <x-icon name="tag" class="w-4 h-4 text-gray-300" /> {{ $request->category->label() }}
                            @if ($request->attachments_count)
                                <span class="inline-flex items-center gap-1"><x-icon name="photo" class="w-4 h-4 text-gray-300" /> {{ $request->attachments_count }}</span>
                            @endif
                        </div>
                    </div>
                    <x-request-status-badge :status="$request->status" class="shrink-0" />
                </a>
            @empty
                <div class="p-12 text-center text-gray-400">
                    <x-icon name="document" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                    <p>{{ __('No requests yet.') }}</p>
                    <a href="{{ route('requests.create') }}" wire:navigate class="mt-2 inline-block text-brand-600 font-medium hover:underline">{{ __('Publish your first request') }}</a>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->requests->links() }}</div>
    </div>
</div>
