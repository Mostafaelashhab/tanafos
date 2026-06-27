<?php

use App\Models\CreditPackage;
use App\Models\Plan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $type = 'package'; // package | plan
    public ?int $editId = null;
    public bool $showForm = false;

    public string $key = '';
    public string $name = '';
    public string $name_ar = '';
    public ?int $credits = null;
    public ?int $price = 0;
    public string $tier = 'basic';
    public string $grants_tier = '';
    public bool $is_active = true;

    #[Computed]
    public function packages()
    {
        return CreditPackage::orderBy('sort_order')->get();
    }

    #[Computed]
    public function plans()
    {
        return Plan::orderBy('sort_order')->get();
    }

    public function start(string $type): void
    {
        $this->reset(['editId', 'key', 'name', 'name_ar', 'credits', 'tier', 'grants_tier']);
        $this->type = $type;
        $this->price = 0;
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(string $type, int $id): void
    {
        $this->type = $type;
        $this->editId = $id;
        $this->showForm = true;

        if ($type === 'package') {
            $m = CreditPackage::findOrFail($id);
            $this->fill($m->only('key', 'name', 'name_ar', 'credits', 'price', 'is_active'));
            $this->grants_tier = (string) $m->grants_tier;
        } else {
            $m = Plan::findOrFail($id);
            $this->fill($m->only('key', 'name', 'name_ar', 'tier', 'price', 'is_active'));
        }
    }

    public function save(): void
    {
        $table = $this->type === 'package' ? 'credit_packages' : 'plans';

        $rules = [
            'key' => ['required', 'string', 'max:50', Rule::unique($table, 'key')->ignore($this->editId)],
            'name' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
        if ($this->type === 'package') {
            $rules['credits'] = ['nullable', 'integer', 'min:1'];
        } else {
            $rules['tier'] = ['required', Rule::in(['basic', 'gold', 'premium'])];
        }
        $this->validate($rules);

        if ($this->type === 'package') {
            CreditPackage::updateOrCreate(['id' => $this->editId], [
                'key' => $this->key, 'name' => $this->name, 'name_ar' => $this->name_ar,
                'credits' => $this->credits ?: null, 'price' => $this->price,
                'grants_tier' => $this->grants_tier ?: null, 'is_active' => $this->is_active,
            ]);
        } else {
            Plan::updateOrCreate(['id' => $this->editId], [
                'key' => $this->key, 'name' => $this->name, 'name_ar' => $this->name_ar,
                'tier' => $this->tier, 'price' => $this->price, 'is_active' => $this->is_active,
            ]);
        }

        $this->showForm = false;
        unset($this->packages, $this->plans);
        session()->flash('status', __('Saved.'));
    }

    public function toggle(string $type, int $id): void
    {
        $m = $type === 'package' ? CreditPackage::find($id) : Plan::find($id);
        $m?->update(['is_active' => ! $m->is_active]);
        unset($this->packages, $this->plans);
    }

    public function remove(string $type, int $id): void
    {
        ($type === 'package' ? CreditPackage::find($id) : Plan::find($id))?->delete();
        unset($this->packages, $this->plans);
    }
}; ?>

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="font-bold text-2xl text-gray-900 mb-6">{{ __('Admin') }}</h1>
        @include('admin._nav')

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        {{-- Editor --}}
        @if ($showForm)
            <div class="bg-white rounded-2xl ring-1 ring-brand-100 p-6 mb-6">
                <h2 class="font-semibold text-gray-900 mb-4">
                    {{ $editId ? __('Edit') : __('Add') }} — {{ $type === 'package' ? __('Credit package') : __('Plan') }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :value="__('Key')" />
                        <x-text-input wire:model="key" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('key')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label :value="__('Price (EGP)')" />
                        <x-text-input wire:model="price" class="block mt-1 w-full" type="number" min="0" />
                        <x-input-error :messages="$errors->get('price')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label :value="__('Name')" />
                        <x-text-input wire:model="name" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label :value="__('Name (Arabic)')" />
                        <x-text-input wire:model="name_ar" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('name_ar')" class="mt-1" />
                    </div>
                    @if ($type === 'package')
                        <div>
                            <x-input-label :value="__('Credits (blank = unlimited)')" />
                            <x-text-input wire:model="credits" class="block mt-1 w-full" type="number" min="1" />
                        </div>
                        <div>
                            <x-input-label :value="__('Grants tier (for unlimited)')" />
                            <select wire:model="grants_tier" class="block mt-1 w-full border-gray-200 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                                <option value="">—</option>
                                <option value="basic">{{ __('Basic') }}</option>
                                <option value="gold">{{ __('Gold') }}</option>
                                <option value="premium">{{ __('Premium') }}</option>
                            </select>
                        </div>
                    @else
                        <div>
                            <x-input-label :value="__('Tier')" />
                            <select wire:model="tier" class="block mt-1 w-full border-gray-200 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                                <option value="basic">{{ __('Basic') }}</option>
                                <option value="gold">{{ __('Gold') }}</option>
                                <option value="premium">{{ __('Premium') }}</option>
                            </select>
                        </div>
                    @endif
                    <label class="flex items-center gap-2 mt-6">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm text-gray-700">{{ __('Active') }}</span>
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="$set('showForm', false)" class="text-sm text-gray-500">{{ __('Cancel') }}</button>
                    <x-primary-button wire:click="save">{{ __('Save') }}</x-primary-button>
                </div>
            </div>
        @endif

        {{-- Packages --}}
        <div class="bg-white rounded-2xl shadow-soft overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <span class="font-semibold text-gray-900">{{ __('Credit packages') }}</span>
                <button wire:click="start('package')" class="inline-flex items-center gap-1 text-sm text-brand-600 font-medium">
                    <x-icon name="plus" class="w-4 h-4" /> {{ __('Add') }}
                </button>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach ($this->packages as $p)
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900">{{ $p->name }} <span class="text-gray-400 text-sm">({{ $p->key }})</span></div>
                            <div class="text-sm text-gray-500">{{ $p->isUnlimited() ? __('Unlimited') : $p->credits.' '.__('credits') }} · {{ $p->price }} {{ __('EGP') }}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="toggle('package', {{ $p->id }})" @class([
                                'px-2.5 py-1 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $p->is_active,
                                'bg-gray-100 text-gray-500' => ! $p->is_active,
                            ])>{{ $p->is_active ? __('Active') : __('Inactive') }}</button>
                            <button wire:click="edit('package', {{ $p->id }})" class="text-gray-400 hover:text-brand-600"><x-icon name="pencil" class="w-4 h-4" /></button>
                            <button wire:click="remove('package', {{ $p->id }})" wire:confirm="{{ __('Delete?') }}" class="text-gray-400 hover:text-red-600"><x-icon name="trash" class="w-4 h-4" /></button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Plans --}}
        <div class="bg-white rounded-2xl shadow-soft overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <span class="font-semibold text-gray-900">{{ __('Subscription plans') }}</span>
                <button wire:click="start('plan')" class="inline-flex items-center gap-1 text-sm text-brand-600 font-medium">
                    <x-icon name="plus" class="w-4 h-4" /> {{ __('Add') }}
                </button>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach ($this->plans as $p)
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900">{{ $p->name }} <span class="text-gray-400 text-sm">({{ $p->tier }})</span></div>
                            <div class="text-sm text-gray-500">{{ $p->price }} {{ __('EGP') }}/{{ __('mo') }}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="toggle('plan', {{ $p->id }})" @class([
                                'px-2.5 py-1 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $p->is_active,
                                'bg-gray-100 text-gray-500' => ! $p->is_active,
                            ])>{{ $p->is_active ? __('Active') : __('Inactive') }}</button>
                            <button wire:click="edit('plan', {{ $p->id }})" class="text-gray-400 hover:text-brand-600"><x-icon name="pencil" class="w-4 h-4" /></button>
                            <button wire:click="remove('plan', {{ $p->id }})" wire:confirm="{{ __('Delete?') }}" class="text-gray-400 hover:text-red-600"><x-icon name="trash" class="w-4 h-4" /></button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
