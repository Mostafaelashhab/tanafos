<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Tanafos') }}</title>

        @php($rootRoutes = ['dashboard', 'requests.index', 'merchant.leads.index', 'merchant.billing', 'leaderboard', 'notifications.index', 'profile', 'admin.dashboard', 'admin.merchants', 'admin.users', 'admin.requests', 'admin.plans'])
        @php($isRoot = collect($rootRoutes)->contains(fn ($r) => request()->routeIs($r)))

        <x-pwa-head />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- Mark installed/standalone launches so the splash shows only there --}}
        <script>if (matchMedia('(display-mode: standalone)').matches || navigator.standalone) document.documentElement.classList.add('standalone');</script>
    </head>
    <body class="font-sans antialiased bg-app text-gray-900">
        @php($user = auth()->user())

        {{-- Splash (installed app only) --}}
        <div id="splash">
            <img class="mark" src="/icons/icon.svg" alt="">
            <div class="font-extrabold text-2xl">{{ config('app.name') }}</div>
            <span class="dot"></span>
        </div>

        {{-- Pull-to-refresh indicator --}}
        <div id="ptr"><span class="ring"></span></div>

        {{-- Navigation skeleton (shown by JS during slower navigations) --}}
        <div id="nav-skeleton" hidden class="fixed inset-x-0 top-16 bottom-0 z-30 bg-app overflow-hidden md:start-0">
            <div class="max-w-2xl mx-auto px-4 py-5 space-y-4">
                <div class="skel h-28 rounded-3xl"></div>
                <div class="skel h-5 w-28"></div>
                <div class="skel h-20"></div>
                <div class="skel h-20"></div>
                <div class="skel h-20"></div>
            </div>
        </div>

        <div class="min-h-screen flex flex-col">
            {{-- Top app bar --}}
            <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100 pt-safe">
                <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-1 shrink-0">
                        {{-- Inner mobile screens get a native back button instead of the logo --}}
                        @unless ($isRoot)
                            <button type="button" onclick="history.back()"
                                    class="md:hidden -ms-2 p-2 rounded-full hover:bg-gray-100 active:scale-90 transition text-gray-700"
                                    aria-label="{{ __('Back') }}">
                                <x-icon name="arrow-left" class="w-6 h-6 rotate-180" />
                            </button>
                        @endunless
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="{{ $isRoot ? 'flex' : 'hidden md:flex' }} items-center gap-2 font-extrabold text-lg text-brand-600">
                            <img src="/icons/icon.svg" class="w-8 h-8" alt="">
                            <span class="hidden sm:inline">{{ config('app.name') }}</span>
                        </a>
                    </div>

                    {{-- Mobile screen title (centered) — empty spacer on root --}}
                    <div class="md:hidden flex-1 min-w-0 text-center px-2">
                        @unless ($isRoot)
                            <span class="font-bold text-[17px] text-gray-900 truncate block">{{ \App\Support\Nav::title() }}</span>
                        @endunless
                    </div>

                    {{-- Desktop nav --}}
                    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                        @foreach (\App\Support\Nav::primary($user) as $item)
                            <a href="{{ route($item['route']) }}" wire:navigate
                               @class([
                                   'flex items-center gap-2 px-3 py-2 rounded-lg transition',
                                   'bg-brand-50 text-brand-700' => request()->routeIs($item['active']),
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
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100">
                                    <span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </span>
                                    <x-icon name="chevron-down" class="hidden md:block w-4 h-4 text-gray-400" />
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
            <main id="app-main" class="flex-1 pb-24 md:pb-8">
                {{ $slot }}
            </main>
        </div>

        {{-- Add-to-home-screen prompt (shown by JS when installable) --}}
        <div id="install-banner" hidden class="fixed inset-x-0 bottom-0 z-40 md:hidden">
            <div class="mx-auto max-w-md m-3 mb-[6.5rem] bg-white shadow-soft rounded-2xl p-3.5 flex items-center gap-3">
                <img src="/icons/icon.svg" class="w-10 h-10 rounded-xl" alt="">
                <div class="flex-1 text-sm leading-tight">
                    <div class="font-bold text-gray-900">{{ __('Install :app', ['app' => config('app.name')]) }}</div>
                    <div class="text-gray-400 text-xs">{{ __('Add to your home screen for the full app experience.') }}</div>
                </div>
                <button onclick="window.TanafosInstall.install()" class="bg-brand-600 text-white text-sm font-bold rounded-full px-4 py-2 shrink-0">{{ __('Install') }}</button>
                <button onclick="window.TanafosInstall.dismiss()" class="text-gray-300 p-1 shrink-0" aria-label="{{ __('Dismiss') }}">
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>
        </div>

        {{-- Floating bottom navigation with center FAB (Figma look) --}}
        @php($tabs = array_merge(\App\Support\Nav::primary($user), [['route' => 'profile', 'active' => 'profile', 'icon' => 'user', 'label' => 'Profile']]))
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-30 pb-safe pointer-events-none">
            <div class="relative mx-auto max-w-md px-4 pb-3">
                <div class="pointer-events-auto bg-white rounded-[26px] shadow-soft h-16 flex items-center justify-around px-2">
                    @foreach ($tabs as $item)
                        @php($on = request()->routeIs($item['active']))
                        <a href="{{ route($item['route']) }}" wire:navigate
                           class="flex-1 flex flex-col items-center justify-center gap-1 {{ $on ? 'text-brand-600' : 'text-gray-400' }}">
                            <span class="flex items-center justify-center w-11 h-8 rounded-full transition {{ $on ? 'bg-brand-100' : '' }}">
                                <x-icon :name="$item['icon']" class="w-6 h-6" />
                            </span>
                            <span class="text-[10px] {{ $on ? 'font-semibold' : '' }}">{{ __($item['label']) }}</span>
                        </a>
                    @endforeach
                </div>

                @if ($user->isBuyer())
                    <a href="{{ route('requests.create') }}" wire:navigate
                       class="pointer-events-auto absolute -top-5 left-1/2 -translate-x-1/2 w-14 h-14 rounded-full bg-brand-600 text-white flex items-center justify-center shadow-fab ring-4 ring-[#f5f4fb] active:scale-95 transition"
                       aria-label="{{ __('New request') }}">
                        <x-icon name="plus" class="w-7 h-7" />
                    </a>
                @endif
            </div>
        </nav>
    </body>
</html>
