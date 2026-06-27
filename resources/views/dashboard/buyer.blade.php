<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buyer dashboard') }}
        </h2>
    </x-slot>

    @php
        $user = auth()->user();
        $counts = [
            'active' => \App\Models\Request::forBuyer($user)->active()->count(),
            'draft' => \App\Models\Request::forBuyer($user)->where('status', 'draft')->count(),
            'completed' => \App\Models\Request::forBuyer($user)->where('status', 'completed')->count(),
        ];
        $recent = \App\Models\Request::forBuyer($user)->active()->with('category')->latest()->limit(5)->get();
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between">
                <div class="text-gray-900">{{ __('Welcome, :name', ['name' => $user->name]) }}</div>
                <a href="{{ route('requests.create') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                    {{ __('New request') }}
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('requests.index') }}?filter=active" wire:navigate class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Active requests') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-indigo-600">{{ $counts['active'] }}</div>
                </a>
                <a href="{{ route('requests.index') }}?filter=draft" wire:navigate class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Drafts') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-700">{{ $counts['draft'] }}</div>
                </a>
                <a href="{{ route('requests.index') }}?filter=completed" wire:navigate class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Completed') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-700">{{ $counts['completed'] }}</div>
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b text-sm font-medium text-gray-700">{{ __('Active requests') }}</div>
                <div class="divide-y">
                    @forelse ($recent as $request)
                        <a href="{{ route('requests.show', $request) }}" wire:navigate
                           class="flex items-center justify-between p-4 hover:bg-gray-50">
                            <div>
                                <div class="font-medium text-gray-900">{{ $request->title }}</div>
                                <div class="text-sm text-gray-500">{{ $request->category->label() }}</div>
                            </div>
                            <x-request-status-badge :status="$request->status" />
                        </a>
                    @empty
                        <div class="p-6 text-center text-gray-500">
                            {{ __('No active requests.') }}
                            <a href="{{ route('requests.create') }}" wire:navigate class="text-indigo-600 underline">{{ __('Publish your first request') }}</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
