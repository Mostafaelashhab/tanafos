<?php

use App\Livewire\Forms\RequestForm;
use App\Models\Category;
use App\Models\Request as DemandRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public DemandRequest $request;
    public RequestForm $form;
    public array $images = [];

    public function mount(DemandRequest $request): void
    {
        $this->authorize('update', $request);
        $this->request = $request;
        $this->form->setRequest($request);
    }

    #[Computed]
    public function categories()
    {
        return Category::with('children')->whereNull('parent_id')
            ->where('is_active', true)->orderBy('sort_order')->get();
    }

    public function save(): void
    {
        $this->authorize('update', $this->request);
        $this->validate(['images.*' => ['image', 'max:5120']]);

        $this->form->update();

        $existing = $this->request->attachments()->count();
        foreach ($this->images as $i => $image) {
            $path = $image->store("requests/{$this->request->id}", 'public');
            $this->request->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'mime' => $image->getMimeType(),
                'size' => $image->getSize(),
                'sort_order' => $existing + $i,
            ]);
        }

        session()->flash('status', __('Request updated.'));
        $this->redirectRoute('requests.show', $this->request, navigate: true);
    }
}; ?>

<div class="py-10">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <h1 class="font-semibold text-2xl text-gray-800 mb-6">{{ __('Edit request') }}</h1>

        <form wire:submit="save" class="bg-white shadow-soft rounded-2xl p-6 sm:p-8">
            @include('requests._fields', ['categories' => $this->categories])

            <div class="mt-6">
                <x-input-label for="images" :value="__('Add more images')" />
                <input type="file" wire:model="images" id="images" multiple accept="image/*"
                       class="field-file mt-2" />
                <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('requests.show', $request) }}" wire:navigate
                   class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save changes') }}</x-primary-button>
            </div>
        </form>
    </div>
</div>
