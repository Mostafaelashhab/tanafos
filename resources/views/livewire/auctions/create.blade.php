<?php

use App\Models\Auction;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $title = '';
    public ?int $category_id = null;
    public string $condition = 'used';
    public string $city = '';
    public string $description = '';
    public ?int $starting_price = null;
    public int $bid_increment = 50;
    public ?int $reserve_price = null;
    public int $duration_days = 3;

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'condition' => ['required', 'in:new,used,any'],
            'city' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starting_price' => ['required', 'integer', 'min:1'],
            'bid_increment' => ['required', 'integer', 'min:1'],
            'reserve_price' => ['nullable', 'integer', 'gte:starting_price'],
            'duration_days' => ['required', 'integer', 'in:1,3,7,14'],
        ]);

        $auction = Auction::create([
            'seller_id' => Auth::id(),
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'condition' => $data['condition'],
            'city' => $data['city'] ?: null,
            'currency' => config('banha.currency', 'EGP'),
            'starting_price' => $data['starting_price'],
            'bid_increment' => $data['bid_increment'],
            'reserve_price' => $data['reserve_price'],
            'current_price' => $data['starting_price'],
            'status' => 'live',
            'ends_at' => now()->addDays($data['duration_days']),
        ]);

        session()->flash('status', __('Your auction is now live.'));
        $this->redirectRoute('auctions.show', $auction, navigate: true);
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 py-5">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6">
        <h1 class="font-extrabold text-lg mb-4">{{ __('List an item for auction') }}</h1>

        <form wire:submit="save" class="space-y-4">
            <div>
                <x-input-label for="title" :value="__('What are you selling?')" />
                <x-text-input wire:model="title" id="title" type="text" class="mt-1" autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="category_id" :value="__('Category')" />
                    <select wire:model="category_id" id="category_id" class="field mt-1">
                        <option value="">{{ __('General') }}</option>
                        @foreach ($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="condition" :value="__('Condition')" />
                    <select wire:model="condition" id="condition" class="field mt-1">
                        <option value="used">{{ __('Used') }}</option>
                        <option value="new">{{ __('New') }}</option>
                        <option value="any">{{ __('Any') }}</option>
                    </select>
                </div>
            </div>

            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea wire:model="description" id="description" rows="3" class="field mt-1"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="starting_price" :value="__('Starting price (EGP)')" />
                    <x-text-input wire:model="starting_price" id="starting_price" type="number" min="1" class="mt-1" />
                    <x-input-error :messages="$errors->get('starting_price')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="bid_increment" :value="__('Min. bid step (EGP)')" />
                    <x-text-input wire:model="bid_increment" id="bid_increment" type="number" min="1" class="mt-1" />
                    <x-input-error :messages="$errors->get('bid_increment')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="reserve_price" :value="__('Reserve price (optional)')" />
                    <x-text-input wire:model="reserve_price" id="reserve_price" type="number" min="0" class="mt-1" />
                    <x-input-error :messages="$errors->get('reserve_price')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="city" :value="__('City')" />
                    <x-text-input wire:model="city" id="city" type="text" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label :value="__('Auction length')" />
                <div class="mt-1 grid grid-cols-4 gap-2">
                    @foreach ([1 => __('1 day'), 3 => __('3 days'), 7 => __('7 days'), 14 => __('14 days')] as $d => $label)
                        <button type="button" wire:click="$set('duration_days', {{ $d }})" @class([
                            'py-2.5 rounded-xl text-sm font-semibold transition',
                            'bg-brand-600 text-white' => $duration_days === $d,
                            'bg-gray-50 text-gray-600 ring-1 ring-gray-200' => $duration_days !== $d,
                        ])>{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 h-12 rounded-full bg-brand-600 text-white font-bold text-[15px] shadow-fab active:scale-[.98] transition">
                <x-icon name="gavel" class="w-5 h-5" /> {{ __('Start auction') }}
            </button>
        </form>
    </div>
</div>
