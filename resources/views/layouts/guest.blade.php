<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Tanafos') }}</title>

        <x-pwa-head />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">

            {{-- Brand panel (desktop) --}}
            <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-indigo-600 text-white p-12">
                <div class="absolute -top-24 -start-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-32 -end-24 w-96 h-96 bg-violet-400/20 rounded-full blur-3xl"></div>

                <a href="{{ route('home') }}" wire:navigate class="relative flex items-center gap-2 font-extrabold text-2xl">
                    <img src="/icons/icon.svg" class="w-9 h-9" alt="">
                    {{ config('app.name') }}
                </a>

                <div class="relative">
                    <h1 class="text-4xl font-extrabold leading-tight">أنت تطلب…<br>والتجار يتنافسون عليك</h1>
                    <p class="mt-4 text-indigo-100 text-lg max-w-md">انشر ما تحتاجه واحصل على أفضل العروض من تجار موثوقين — بسرعة ومجانًا.</p>

                    <ul class="mt-8 space-y-3 text-indigo-50">
                        @foreach (['عروض متعددة تتنافس عليك', 'تجار موثوقون في منطقتك', 'قارن وتفاوض واختر الأفضل'] as $point)
                            <li class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                    <x-icon name="check" class="w-4 h-4" />
                                </span>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-sm text-indigo-200">© {{ date('Y') }} {{ config('app.name') }}</p>
            </div>

            {{-- Form panel --}}
            <div class="flex flex-col justify-center px-6 py-10 sm:px-12 bg-gray-50 lg:bg-white">
                <div class="w-full max-w-md mx-auto">
                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" wire:navigate class="lg:hidden flex items-center justify-center gap-2 font-extrabold text-2xl text-indigo-600 mb-8">
                        <img src="/icons/icon.svg" class="w-9 h-9" alt="">
                        {{ config('app.name') }}
                    </a>

                    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6 sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-sm text-gray-400">
                        <a href="{{ route('home') }}" wire:navigate class="hover:text-indigo-600 inline-flex items-center gap-1">
                            <x-icon name="arrow-left" class="w-4 h-4" /> {{ __('Back to home') }}
                        </a>
                    </p>
                </div>
            </div>

        </div>
    </body>
</html>
