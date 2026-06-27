<?php

use App\Models\MerchantProfile;
use App\Services\CreditService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?int $pointsFor = null;
    public ?int $pointsAmount = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleVerified(int $id): void
    {
        $profile = MerchantProfile::findOrFail($id);
        $profile->forceFill(['verified_at' => $profile->verified_at ? null : now()])->save();
    }

    public function openPoints(int $id): void
    {
        $this->pointsFor = $this->pointsFor === $id ? null : $id;
        $this->pointsAmount = null;
    }

    public function grantPoints(CreditService $credits): void
    {
        $this->validate(['pointsAmount' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000']]);

        $credits->grantPoints(MerchantProfile::findOrFail($this->pointsFor), $this->pointsAmount, __('Admin adjustment'));

        $this->reset('pointsFor', 'pointsAmount');
        session()->flash('status', __('Points updated.'));
    }

    #[Computed]
    public function merchants()
    {
        return MerchantProfile::query()
            ->with('user')
            ->when($this->search, fn ($q) => $q->where('business_name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);
    }
}; ?>

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="font-bold text-2xl text-gray-900 mb-6">{{ __('Admin') }}</h1>
        @include('admin._nav')

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        <div class="relative mb-4 max-w-sm">
            <span class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-3' : 'left-3' }} flex items-center text-gray-400">
                <x-icon name="search" class="w-5 h-5" />
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search merchants…') }}"
                   class="w-full {{ app()->getLocale() === 'ar' ? 'pr-10' : 'pl-10' }} field" />
        </div>

        <div class="bg-white rounded-xl shadow-soft divide-y divide-gray-50 overflow-hidden">
            @forelse ($this->merchants as $merchant)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900 flex items-center gap-2">
                                {{ $merchant->business_name }}
                                @if ($merchant->isVerified())
                                    <span class="text-blue-500" title="{{ __('Verified seller') }}"><x-icon name="badge-check" class="w-4 h-4" /></span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500 truncate">
                                {{ $merchant->user?->email }} · {{ $merchant->credits_balance }} {{ __('credits') }} · {{ $merchant->completed_deals }} {{ __('deals') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="openPoints({{ $merchant->id }})"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                                {{ __('Points') }}
                            </button>
                            <button wire:click="toggleVerified({{ $merchant->id }})"
                                    @class([
                                        'px-3 py-1.5 rounded-lg text-sm font-medium',
                                        'bg-gray-100 text-gray-600 hover:bg-gray-200' => $merchant->isVerified(),
                                        'bg-brand-600 text-white hover:bg-brand-700' => ! $merchant->isVerified(),
                                    ])>
                                {{ $merchant->isVerified() ? __('Unverify') : __('Verify') }}
                            </button>
                        </div>
                    </div>

                    {{-- Points adjustment panel --}}
                    @if ($pointsFor === $merchant->id)
                        <div class="mt-3 flex items-center gap-2 bg-gray-50 rounded-xl p-3">
                            <input wire:model="pointsAmount" type="number" placeholder="{{ __('e.g. 100 or -50') }}"
                                   class="flex-1 border-gray-200 rounded-lg text-sm focus:border-brand-500 focus:ring-brand-500" />
                            <x-primary-button wire:click="grantPoints">{{ __('Apply') }}</x-primary-button>
                            <button wire:click="$set('pointsFor', null)" class="text-sm text-gray-400 px-2">{{ __('Cancel') }}</button>
                        </div>
                        <x-input-error :messages="$errors->get('pointsAmount')" class="mt-1" />
                    @endif
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">{{ __('No merchants found.') }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->merchants->links() }}</div>
    </div>
</div>
