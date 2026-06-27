<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type = 'all';

    public function updating($name): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->type !== 'all', fn ($q) => $q->where('type', $this->type))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(20);
    }
}; ?>

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="font-bold text-2xl text-gray-900 mb-6">{{ __('Admin') }}</h1>
        @include('admin._nav')

        <div class="flex flex-wrap gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-3' : 'left-3' }} flex items-center text-gray-400">
                    <x-icon name="search" class="w-5 h-5" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search users…') }}"
                       class="w-full {{ app()->getLocale() === 'ar' ? 'pr-10' : 'pl-10' }} field" />
            </div>
            <select wire:model.live="type" class="field text-sm !w-auto">
                <option value="all">{{ __('All') }}</option>
                <option value="buyer">{{ __('Buyers') }}</option>
                <option value="merchant">{{ __('Merchants') }}</option>
                <option value="admin">{{ __('Admins') }}</option>
            </select>
        </div>

        <div class="bg-white rounded-xl shadow-soft divide-y divide-gray-50 overflow-hidden">
            @forelse ($this->users as $u)
                <div class="px-5 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold shrink-0">
                            {{ mb_substr($u->name, 0, 1) }}
                        </span>
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900 truncate">{{ $u->name }}</div>
                            <div class="text-sm text-gray-500 truncate">{{ $u->email }}</div>
                        </div>
                    </div>
                    <span @class([
                        'shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium',
                        'bg-emerald-100 text-emerald-700' => $u->type === 'buyer',
                        'bg-brand-100 text-brand-700' => $u->type === 'merchant',
                        'bg-gray-800 text-white' => $u->type === 'admin',
                    ])>{{ __(ucfirst($u->type)) }}</span>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">{{ __('No users found.') }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->users->links() }}</div>
    </div>
</div>
