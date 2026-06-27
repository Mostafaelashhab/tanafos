<?php

use App\Livewire\Forms\RequestForm;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public RequestForm $form;

    #[Validate(['images.*' => ['image', 'max:5120']])]
    public array $images = [];

    #[Computed]
    public function categories()
    {
        return Category::with('children')->whereNull('parent_id')
            ->where('is_active', true)->orderBy('sort_order')->get();
    }

    public function save(bool $publish = false): void
    {
        $this->validate(['images.*' => ['image', 'max:5120']]);

        $request = $this->form->store(Auth::user(), $publish);

        foreach ($this->images as $i => $image) {
            $path = $image->store("requests/{$request->id}", 'public');
            $request->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'mime' => $image->getMimeType(),
                'size' => $image->getSize(),
                'sort_order' => $i,
            ]);
        }

        session()->flash('status', $publish ? __('Your request is now live.') : __('Draft saved.'));

        $this->redirectRoute('requests.show', $request, navigate: true);
    }
}; ?>

<div class="py-10">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <h1 class="font-semibold text-2xl text-gray-800 mb-6">{{ __('New request') }}</h1>

        <div>
            <form wire:submit="save" class="bg-white shadow-soft rounded-2xl p-6 sm:p-8">
                @include('requests._fields', ['categories' => $this->categories])

                {{-- Images --}}
                <div class="mt-6">
                    <x-input-label for="images" :value="__('Images')" />
                    <input type="file" wire:model="images" id="images" multiple accept="image/*"
                           class="block mt-1 w-full text-sm text-gray-600" />
                    <x-input-error :messages="$errors->get('images.*')" class="mt-2" />

                    <div wire:loading wire:target="images" class="mt-2 text-sm text-gray-500">{{ __('Uploading…') }}</div>

                    @if ($images)
                        <div class="mt-3 flex flex-wrap gap-3">
                            @foreach ($images as $image)
                                <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 rounded object-cover border" />
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-8 flex items-center justify-end gap-3">
                    <button type="button" wire:click="save(false)"
                            class="text-sm text-gray-600 underline hover:text-gray-900">
                        {{ __('Save as draft') }}
                    </button>
                    <x-primary-button wire:click="save(true)" type="button">
                        {{ __('Publish request') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
