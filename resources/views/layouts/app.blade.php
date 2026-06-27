<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'banha.shop') }}</title>

        <x-pwa-head />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        @php($user = auth()->user())

        <div class="min-h-screen flex flex-col">
            {{-- Top app bar --}}
            <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100 pt-safe">
                <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-extrabold text-lg text-indigo-600 shrink-0">
                        <img src="/icons/icon.svg" class="w-8 h-8" alt="">
                        <span class="hidden sm:inline">{{ config('app.name') }}</span>
                    </a>

                    {{-- Desktop nav --}}
                    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                        @foreach (\App\Support\Nav::primary($user) as $item)
                            <a href="{{ route($item['route']) }}" wire:navigate
                               @class([
                                   'flex items-center gap-2 px-3 py-2 rounded-lg transition',
                                   'bg-indigo-50 text-indigo-700' => request()->routeIs($item['active']),
                                   'text-gray-600 hover:bg-gray-100' => ! request()->routeIs($item['active']),
                               ])>
                                <x-icon :name="$item['icon']" class="w-5 h-5" />
                                {{ __($item['label']) }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="flex items-center gap-1">
                        <livewire:notifications.bell />

                        {{-- Profile menu --}}
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100">
                                    <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </span>
                                    <x-icon name="chevron-down" class="w-4 h-4 text-gray-400" />
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <div class="font-semibold text-gray-900 truncate">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                                </div>
                                <x-dropdown-link :href="route('profile')" wire:navigate>{{ __('Profile') }}</x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>

            @isset($header)
                <div class="bg-white border-b border-gray-100">
                    <div class="max-w-6xl mx-auto px-4 py-5">{{ $header }}</div>
                </div>
            @endisset

            {{-- Content --}}
            <main class="flex-1 pb-24 md:pb-8">
                {{ $slot }}
            </main>
        </div>

        {{-- Mobile bottom navigation (Material-style active pill) --}}
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur border-t border-gray-100 pb-safe">
            <div class="flex h-16 px-1">
                @php($tabs = array_merge(\App\Support\Nav::primary($user), [['route' => 'profile', 'active' => 'profile', 'icon' => 'user', 'label' => 'Profile']]))
                @foreach ($tabs as $item)
                    @php($on = request()->routeIs($item['active']))
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="flex-1 flex flex-col items-center justify-center gap-1 {{ $on ? 'text-indigo-700' : 'text-gray-400' }}">
                        <span class="flex items-center justify-center w-14 h-8 rounded-full transition {{ $on ? 'bg-indigo-100' : '' }}">
                            <x-icon :name="$item['icon']" class="w-6 h-6" />
                        </span>
                        <span class="text-[11px] {{ $on ? 'font-semibold' : '' }}">{{ __($item['label']) }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    </body>
</html>
