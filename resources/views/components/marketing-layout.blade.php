@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name').' — أنت تطلب والتجار يتنافسون عليك' }}</title>
    <meta name="description" content="{{ $description ?? 'سوق الطلب العكسي: انشر ما تحتاجه واحصل على عروض من التجار تتنافس عليك.' }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />

    <x-pwa-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    {{-- Header --}}
    <header class="sticky top-0 z-30 bg-white/85 backdrop-blur border-b border-gray-100">
        <nav class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 font-extrabold text-xl text-brand-600 shrink-0">
                <img src="/icons/icon.svg" class="w-8 h-8" alt="">
                {{ config('app.name') }}
            </a>

            <div class="hidden md:flex items-center gap-1 text-sm font-medium">
                @foreach ([
                    ['home', __('Home')],
                    ['how-it-works', __('How it works')],
                    ['merchants', __('For merchants')],
                    ['pricing', __('Pricing')],
                ] as [$route, $label])
                    <a href="{{ route($route) }}" wire:navigate
                       @class([
                           'px-3 py-2 rounded-lg transition',
                           'text-brand-700 bg-brand-50' => request()->routeIs($route),
                           'text-gray-600 hover:bg-gray-100' => ! request()->routeIs($route),
                       ])>{{ $label }}</a>
                @endforeach
            </div>

            <div class="flex items-center gap-2 text-sm shrink-0">
                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate class="px-4 py-2 rounded-full bg-brand-600 text-white font-semibold hover:bg-brand-700">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline px-4 py-2 text-gray-600 hover:text-gray-900">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-full bg-brand-600 text-white font-semibold hover:bg-brand-700">{{ __('Register') }}</a>
                @endauth
            </div>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-50 border-t border-gray-100 mt-10">
        <div class="max-w-7xl mx-auto px-6 py-12 grid gap-8 sm:grid-cols-2 md:grid-cols-4">
            <div class="sm:col-span-2 md:col-span-1">
                <div class="flex items-center gap-2 font-extrabold text-lg text-brand-600">
                    <img src="/icons/icon.svg" class="w-7 h-7" alt="">
                    {{ config('app.name') }}
                </div>
                <p class="mt-3 text-sm text-gray-500 leading-relaxed">سوق الطلب العكسي — انشر طلبك ودع التجار يتنافسون عليك.</p>
            </div>
            <div>
                <div class="font-semibold text-gray-800 mb-3">{{ __('Product') }}</div>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('how-it-works') }}" wire:navigate class="hover:text-brand-600">{{ __('How it works') }}</a></li>
                    <li><a href="{{ route('pricing') }}" wire:navigate class="hover:text-brand-600">{{ __('Pricing') }}</a></li>
                    <li><a href="{{ route('merchants') }}" wire:navigate class="hover:text-brand-600">{{ __('For merchants') }}</a></li>
                </ul>
            </div>
            <div>
                <div class="font-semibold text-gray-800 mb-3">{{ __('Account') }}</div>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('register') }}" class="hover:text-brand-600">{{ __('Register') }}</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-brand-600">{{ __('Log in') }}</a></li>
                </ul>
            </div>
            <div>
                <div class="font-semibold text-gray-800 mb-3">{{ __('Get the app') }}</div>
                <p class="text-sm text-gray-500">{{ __('Install Tanafos on your phone for instant offers.') }}</p>
            </div>
        </div>
        <div class="border-t border-gray-100 py-6 text-center text-sm text-gray-400">
            © {{ date('Y') }} {{ config('app.name') }} — كل شيء يبدأ بالطلب.
        </div>
    </footer>

</body>
</html>
