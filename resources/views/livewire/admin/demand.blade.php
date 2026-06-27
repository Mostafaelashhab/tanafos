<?php

use App\Models\Request;
use App\Services\Scraping\DemandImporter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $tab = 'pending';

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return Request::query()
            ->scraped()
            ->with('category')
            ->when($this->tab === 'pending', fn ($q) => $q->where('status', 'draft'))
            ->when($this->tab === 'published', fn ($q) => $q->where('status', '!=', 'draft'))
            ->latest('imported_at')
            ->paginate(15);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Request::query()->pendingImport()->count();
    }

    /** Approve an imported request: take it live (fires matching). */
    public function approve(int $id): void
    {
        $request = Request::scraped()->where('status', 'draft')->findOrFail($id);
        $request->publish();
        unset($this->requests, $this->pendingCount);
    }

    public function reject(int $id): void
    {
        Request::scraped()->where('status', 'draft')->whereKey($id)->delete();
        unset($this->requests, $this->pendingCount);
    }

    /** Run the importer on demand. */
    public function importNow(DemandImporter $importer): void
    {
        if (! $importer->enabled()) {
            session()->flash('status', __('Demand scraping is disabled.'));

            return;
        }

        $summary = $importer->run();
        $total = array_sum(array_column($summary, 'imported'));
        session()->flash('status', __(':n new request(s) imported.', ['n' => $total]));
        unset($this->requests, $this->pendingCount);
    }
}; ?>

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="font-bold text-2xl text-gray-900 mb-6">{{ __('Admin') }}</h1>
        @include('admin._nav')

        @if (session('status'))
            <div class="mb-4 rounded-2xl bg-emerald-50 text-emerald-700 px-4 py-3 text-sm font-medium">{{ session('status') }}</div>
        @endif

        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex flex-wrap gap-2">
                @foreach (['pending' => __('Pending'), 'published' => __('Published'), 'all' => __('All')] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')"
                            @class([
                                'px-4 py-2 rounded-full text-sm font-semibold',
                                'bg-brand-600 text-white' => $tab === $key,
                                'bg-white ring-1 ring-gray-200 text-gray-600' => $tab !== $key,
                            ])>
                        {{ $label }}
                        @if ($key === 'pending' && $this->pendingCount)
                            <span class="ms-1 inline-flex items-center justify-center rounded-full bg-white/25 px-1.5 text-xs">{{ $this->pendingCount }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
            <button wire:click="importNow" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-600 text-white text-sm font-semibold shadow-fab active:scale-95 transition">
                <x-icon name="bolt" class="w-4 h-4" />
                <span wire:loading.remove wire:target="importNow">{{ __('Import now') }}</span>
                <span wire:loading wire:target="importNow">{{ __('Importing…') }}</span>
            </button>
        </div>

        <div class="space-y-3">
            @forelse ($this->requests as $request)
                <div class="bg-white shadow-soft rounded-2xl p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 rounded-full px-2.5 py-0.5 text-[11px] font-semibold">
                                    {{ $request->source_platform }}
                                </span>
                                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-0.5 text-[11px] font-bold">
                                    <x-icon name="bolt" class="w-3 h-3" /> {{ __('No commission') }}
                                </span>
                                @if ($request->status !== 'draft')
                                    <span class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 rounded-full px-2.5 py-0.5 text-[11px] font-semibold">{{ __(ucfirst($request->status)) }}</span>
                                @endif
                            </div>
                            <h3 class="font-bold text-gray-900 mt-2">{{ $request->title }}</h3>
                            <div class="text-sm text-gray-500">{{ $request->category?->label() ?? __('Uncategorized') }}</div>
                            @if ($request->description)
                                <p class="mt-2 text-sm text-gray-600 line-clamp-3 whitespace-pre-line">{{ $request->description }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                @if ($request->city)<span>📍 {{ $request->city }}</span>@endif
                                @if ($request->contact_phone)<span dir="ltr">☎ {{ $request->contact_phone }}</span>@endif
                                @if ($request->source_url)
                                    <a href="{{ $request->source_url }}" target="_blank" rel="noopener" class="text-brand-600 underline">{{ __('Original post') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($request->status === 'draft')
                        <div class="mt-4 flex gap-2">
                            <button wire:click="approve({{ $request->id }})"
                                    class="flex-1 inline-flex items-center justify-center gap-2 h-10 rounded-full bg-brand-600 text-white text-sm font-bold active:scale-[.98] transition">
                                <x-icon name="check" class="w-4 h-4" /> {{ __('Approve & publish') }}
                            </button>
                            <button wire:click="reject({{ $request->id }})"
                                    wire:confirm="{{ __('Delete this imported request?') }}"
                                    class="px-4 h-10 inline-flex items-center justify-center rounded-full bg-gray-100 text-gray-600 text-sm font-semibold">
                                {{ __('Reject') }}
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white shadow-soft rounded-2xl p-10 text-center text-gray-500">
                    <span class="inline-flex w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 items-center justify-center mb-3">
                        <x-icon name="inbox" class="w-7 h-7" />
                    </span>
                    <p>{{ __('No imported demand here yet.') }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->requests->links() }}</div>
    </div>
</div>
