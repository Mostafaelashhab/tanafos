<?php

use App\Livewire\Forms\RequestForm;
use App\Models\Category;
use App\Support\CategoryFields;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public RequestForm $form;
    public array $images = [];
    public int $step = 1;
    public const STEPS = 4;

    #[Computed]
    public function categories()
    {
        return Category::with('children')->whereNull('parent_id')
            ->where('is_active', true)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function selectedCategory()
    {
        return $this->form->category_id ? Category::find($this->form->category_id) : null;
    }

    #[Computed]
    public function fields()
    {
        return CategoryFields::for($this->selectedCategory());
    }

    public function selectCategory(int $id): void
    {
        $this->form->category_id = $id;
        unset($this->selectedCategory, $this->fields);
    }

    public function next(): void
    {
        match ($this->step) {
            1 => $this->validate(['form.category_id' => ['required']], ['form.category_id.required' => __('Please choose a category.')]),
            2 => $this->validate(['form.title' => ['required', 'string', 'min:3', 'max:255']]),
            3 => $this->validate([
                'form.budget_min' => ['nullable', 'integer', 'min:0'],
                'form.budget_max' => ['nullable', 'integer', 'min:0', 'gte:form.budget_min'],
            ]),
            default => null,
        };

        $this->step = min($this->step + 1, self::STEPS);
    }

    public function back(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function save(bool $publish = false): void
    {
        $this->validate(['images.*' => ['image', 'max:5120']]);

        $request = $this->form->store(Auth::user(), $publish);

        foreach ($this->images as $i => $image) {
            $path = $image->store("requests/{$request->id}", 'public');
            $request->attachments()->create([
                'disk' => 'public', 'path' => $path,
                'mime' => $image->getMimeType(), 'size' => $image->getSize(), 'sort_order' => $i,
            ]);
        }

        session()->flash('status', $publish ? __('Your request is now live.') : __('Draft saved.'));
        $this->redirectRoute('requests.show', $request, navigate: true);
    }

    public function with(): array
    {
        return [
            'steps' => [
                ['n' => 1, 'icon' => 'tag', 'label' => __('Category')],
                ['n' => 2, 'icon' => 'document', 'label' => __('Details')],
                ['n' => 3, 'icon' => 'currency', 'label' => __('Budget')],
                ['n' => 4, 'icon' => 'photo', 'label' => __('Review')],
            ],
        ];
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 py-5">

    {{-- Stepper --}}
    <div class="flex items-center justify-between mb-6">
        @foreach ($steps as $s)
            @php($done = $step > $s['n'])
            @php($current = $step === $s['n'])
            <div class="flex items-center {{ ! $loop->last ? 'flex-1' : '' }}">
                <div class="flex flex-col items-center gap-1 shrink-0">
                    <span @class([
                        'w-10 h-10 rounded-full flex items-center justify-center transition',
                        'bg-brand-600 text-white shadow-fab' => $current,
                        'bg-brand-100 text-brand-600' => $done,
                        'bg-gray-100 text-gray-400' => ! $current && ! $done,
                    ])>
                        @if ($done) <x-icon name="check" class="w-5 h-5" />
                        @else <x-icon :name="$s['icon']" class="w-5 h-5" /> @endif
                    </span>
                    <span class="text-[10px] {{ $current ? 'text-brand-600 font-bold' : 'text-gray-400' }}">{{ $s['label'] }}</span>
                </div>
                @unless ($loop->last)
                    <span class="flex-1 h-1 mx-1 rounded-full {{ $done ? 'bg-brand-400' : 'bg-gray-100' }} -mt-4"></span>
                @endunless
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6">

        {{-- STEP 1: Category --}}
        @if ($step === 1)
            <h2 class="font-extrabold text-lg mb-1">ماذا تريد أن تطلب؟</h2>
            <p class="text-sm text-gray-400 mb-5">{{ __('Pick the category that fits your request.') }}</p>

            @php($chips = [['bg-brand-50','text-brand-600'],['bg-rose-50','text-rose-500'],['bg-amber-50','text-amber-600'],['bg-sky-50','text-sky-600'],['bg-emerald-50','text-emerald-600'],['bg-violet-50','text-violet-600']])
            @php($openParent = $this->selectedCategory()?->parent_id ?? $this->selectedCategory()?->id)
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($this->categories as $cat)
                    @php($chip = $chips[$loop->index % count($chips)])
                    @php($on = $openParent === $cat->id)
                    <button type="button" wire:click="selectCategory({{ $cat->id }})"
                            @class([
                                'flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition text-center',
                                'border-brand-500 bg-brand-50/40' => $on,
                                'border-transparent bg-gray-50 hover:bg-gray-100' => ! $on,
                            ])>
                        <span class="w-12 h-12 rounded-2xl {{ $chip[0] }} {{ $chip[1] }} flex items-center justify-center">
                            <x-icon :name="\App\Support\CategoryFields::icon($cat)" class="w-6 h-6" />
                        </span>
                        <span class="text-sm font-semibold text-gray-800">{{ $cat->name_ar }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Subcategories of the chosen parent --}}
            @php($active = $this->categories->firstWhere('id', $openParent))
            @if ($active && $active->children->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs text-gray-400 mb-2">{{ __('Refine (optional)') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="selectCategory({{ $active->id }})"
                                @class(['px-4 py-1.5 rounded-full text-sm border', 'bg-brand-600 text-white border-brand-600' => $this->form->category_id === $active->id, 'bg-white text-gray-600 border-gray-200' => $this->form->category_id !== $active->id])>
                            {{ __('All') }} {{ $active->name_ar }}
                        </button>
                        @foreach ($active->children as $child)
                            <button type="button" wire:click="selectCategory({{ $child->id }})"
                                    @class(['px-4 py-1.5 rounded-full text-sm border', 'bg-brand-600 text-white border-brand-600' => $this->form->category_id === $child->id, 'bg-white text-gray-600 border-gray-200' => $this->form->category_id !== $child->id])>
                                {{ $child->name_ar }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
            <x-input-error :messages="$errors->get('form.category_id')" class="mt-3" />
        @endif

        {{-- STEP 2: Details (+ category-specific fields) --}}
        @if ($step === 2)
            <h2 class="font-extrabold text-lg mb-4">{{ __('Tell us the details') }}</h2>
            <div class="space-y-4">
                <div>
                    <x-input-label for="title" :value="__('What do you need?')" />
                    <x-text-input wire:model="form.title" id="title" class="block mt-1 w-full" type="text" autofocus />
                    <x-input-error :messages="$errors->get('form.title')" class="mt-2" />
                </div>

                @if (count($this->fields))
                    <div class="rounded-2xl bg-brand-50/50 p-4 space-y-4">
                        <p class="text-xs font-semibold text-brand-700 flex items-center gap-1">
                            <x-icon :name="\App\Support\CategoryFields::icon($this->selectedCategory())" class="w-4 h-4" />
                            {{ __('Specifications for :cat', ['cat' => $this->selectedCategory()->name_ar]) }}
                        </p>
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ($this->fields as $f)
                                <div>
                                    <x-input-label :value="__($f['label'])" />
                                    @if ($f['type'] === 'select')
                                        <select wire:model="form.specifications.{{ $f['key'] }}" class="field mt-1">
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach ($f['options'] as $opt)
                                                <option value="{{ $opt }}">{{ __($opt) }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <x-text-input wire:model="form.specifications.{{ $f['key'] }}"
                                                      type="{{ $f['type'] === 'number' ? 'number' : 'text' }}" class="mt-1" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <x-input-label for="description" :value="__('Additional details')" />
                    <textarea wire:model="form.description" id="description" rows="3" class="field mt-1"></textarea>
                </div>
            </div>
        @endif

        {{-- STEP 3: Budget & location --}}
        @if ($step === 3)
            <h2 class="font-extrabold text-lg mb-4">{{ __('Budget & preferences') }}</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="bmin" :value="__('Budget from (EGP)')" />
                        <x-text-input wire:model="form.budget_min" id="bmin" type="number" min="0" class="block mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="bmax" :value="__('Budget to (EGP)')" />
                        <x-text-input wire:model="form.budget_max" id="bmax" type="number" min="0" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('form.budget_max')" class="mt-2" />
                    </div>
                </div>
                <div>
                    <x-input-label for="city" :value="__('City')" />
                    <x-text-input wire:model="form.city" id="city" type="text" class="block mt-1 w-full" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="condition" :value="__('Condition')" />
                        <select wire:model="form.condition" id="condition" class="field mt-1">
                            <option value="any">{{ __('Any') }}</option>
                            <option value="new">{{ __('New') }}</option>
                            <option value="used">{{ __('Used') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="urgency" :value="__('Urgency')" />
                        <select wire:model="form.urgency" id="urgency" class="field mt-1">
                            <option value="low">{{ __('Low') }}</option>
                            <option value="normal">{{ __('Normal') }}</option>
                            <option value="high">{{ __('High') }}</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2.5 rounded-2xl bg-gray-50 px-4 py-3 cursor-pointer">
                    <input type="checkbox" wire:model="form.warranty_required" class="field-check" />
                    <span class="text-sm font-medium text-gray-700">{{ __('Warranty required') }}</span>
                </label>
            </div>
        @endif

        {{-- STEP 4: Review & images --}}
        @if ($step === 4)
            <h2 class="font-extrabold text-lg mb-4">{{ __('Review & images') }}</h2>

            <div class="rounded-2xl bg-gray-50 p-4 space-y-2 text-sm mb-5">
                <div class="flex justify-between"><span class="text-gray-400">{{ __('Category') }}</span><span class="font-semibold">{{ $this->selectedCategory()?->name_ar }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">{{ __('Title') }}</span><span class="font-semibold truncate ms-3">{{ $form->title }}</span></div>
                @if ($form->budget_min || $form->budget_max)
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('Budget') }}</span><span class="font-semibold">{{ $form->budget_min ?: '—' }} – {{ $form->budget_max ?: '—' }} {{ __('EGP') }}</span></div>
                @endif
                @foreach (array_filter($form->specifications, fn ($v) => filled($v)) as $k => $v)
                    <div class="flex justify-between"><span class="text-gray-400">{{ __(\Illuminate\Support\Str::headline($k)) }}</span><span class="font-semibold">{{ $v }}</span></div>
                @endforeach
            </div>

            <div>
                <x-input-label for="images" :value="__('Images')" />
                <input type="file" wire:model="images" id="images" multiple accept="image/*" class="field-file mt-2" />
                <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
                <div wire:loading wire:target="images" class="mt-2 text-sm text-gray-500">{{ __('Uploading…') }}</div>
                @if ($images)
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($images as $image)
                            <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 rounded-xl object-cover" />
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Wizard controls --}}
        <div class="mt-6 flex items-center gap-3">
            @if ($step > 1)
                <button type="button" wire:click="back"
                        class="shrink-0 w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 active:scale-95 transition">
                    <x-icon name="arrow-right" class="w-5 h-5" />
                </button>
            @endif
            @if ($step < 4)
                <button type="button" wire:click="next"
                        class="flex-1 inline-flex items-center justify-center gap-2 h-12 rounded-full bg-brand-600 text-white font-bold text-[15px] shadow-fab active:scale-[.98] transition">
                    {{ __('Next') }} <x-icon name="arrow-left" class="w-5 h-5" />
                </button>
            @else
                <button type="button" wire:click="save(true)"
                        class="flex-1 inline-flex items-center justify-center gap-2 h-12 rounded-full bg-brand-600 text-white font-bold text-[15px] shadow-fab active:scale-[.98] transition">
                    <x-icon name="check" class="w-5 h-5" /> {{ __('Publish request') }}
                </button>
            @endif
        </div>
        @if ($step === 4)
            <div class="mt-3 text-center">
                <button type="button" wire:click="save(false)" class="text-sm text-gray-400 underline">{{ __('Save as draft') }}</button>
            </div>
        @endif
    </div>
</div>
