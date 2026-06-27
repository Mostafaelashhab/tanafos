{{-- Shared request form fields. Expects $form (RequestForm) and $categories. --}}
<div class="space-y-6">
    {{-- Title --}}
    <div>
        <x-input-label for="title" :value="__('What do you need?')" />
        <x-text-input wire:model="form.title" id="title" class="block mt-1 w-full" type="text" required autofocus />
        <x-input-error :messages="$errors->get('form.title')" class="mt-2" />
    </div>

    {{-- Category --}}
    <div>
        <x-input-label for="category_id" :value="__('Category')" />
        <select wire:model="form.category_id" id="category_id"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">{{ __('Select a category') }}</option>
            @foreach ($categories as $parent)
                <optgroup label="{{ $parent->label() }}">
                    <option value="{{ $parent->id }}">{{ $parent->label() }}</option>
                    @foreach ($parent->children as $child)
                        <option value="{{ $child->id }}">— {{ $child->label() }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('form.category_id')" class="mt-2" />
    </div>

    {{-- Budget range --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="budget_min" :value="__('Budget from (EGP)')" />
            <x-text-input wire:model="form.budget_min" id="budget_min" class="block mt-1 w-full" type="number" min="0" />
            <x-input-error :messages="$errors->get('form.budget_min')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="budget_max" :value="__('Budget to (EGP)')" />
            <x-text-input wire:model="form.budget_max" id="budget_max" class="block mt-1 w-full" type="number" min="0" />
            <x-input-error :messages="$errors->get('form.budget_max')" class="mt-2" />
        </div>
    </div>

    {{-- City --}}
    <div>
        <x-input-label for="city" :value="__('City')" />
        <x-text-input wire:model="form.city" id="city" class="block mt-1 w-full" type="text" />
        <x-input-error :messages="$errors->get('form.city')" class="mt-2" />
    </div>

    {{-- Condition / Urgency / Payment --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="condition" :value="__('Condition')" />
            <select wire:model="form.condition" id="condition"
                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="any">{{ __('Any') }}</option>
                <option value="new">{{ __('New') }}</option>
                <option value="used">{{ __('Used') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="urgency" :value="__('Urgency')" />
            <select wire:model="form.urgency" id="urgency"
                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="low">{{ __('Low') }}</option>
                <option value="normal">{{ __('Normal') }}</option>
                <option value="high">{{ __('High') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="payment_method" :value="__('Payment method')" />
            <select wire:model="form.payment_method" id="payment_method"
                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="any">{{ __('Any') }}</option>
                <option value="cash">{{ __('Cash') }}</option>
                <option value="card">{{ __('Card') }}</option>
                <option value="installment">{{ __('Installment') }}</option>
            </select>
        </div>
    </div>

    {{-- Warranty + preferred delivery --}}
    <div class="grid gap-4 sm:grid-cols-2 items-end">
        <label class="flex items-center gap-2 mt-1">
            <input type="checkbox" wire:model="form.warranty_required"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            <span class="text-sm text-gray-700">{{ __('Warranty required') }}</span>
        </label>
        <div>
            <x-input-label for="preferred_delivery" :value="__('Preferred delivery')" />
            <x-text-input wire:model="form.preferred_delivery" id="preferred_delivery" class="block mt-1 w-full" type="text" />
        </div>
    </div>

    {{-- Description --}}
    <div>
        <x-input-label for="description" :value="__('Additional details')" />
        <textarea wire:model="form.description" id="description" rows="4"
                  class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
        <x-input-error :messages="$errors->get('form.description')" class="mt-2" />
    </div>
</div>
