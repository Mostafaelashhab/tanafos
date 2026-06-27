<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('Welcome') }} — {{ config('app.name') }}</title>
    <x-pwa-head />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col bg-gradient-to-b from-brand-100 via-brand-50 to-white">

        {{-- Illustration zone --}}
        <div class="flex-1 flex items-center justify-center px-8 pt-16 relative overflow-hidden">
            <div class="absolute top-16 start-10 w-24 h-24 rounded-full bg-brand-300/40 blur-2xl"></div>
            <div class="absolute bottom-10 end-10 w-32 h-32 rounded-full bg-accent-200/40 blur-2xl"></div>

            <div class="relative text-center">
                <div class="mx-auto w-40 h-40 rounded-[2.2rem] bg-white shadow-soft flex items-center justify-center mb-2 rotate-3">
                    <img src="/icons/icon.svg" class="w-24 h-24" alt="">
                </div>
                {{-- floating chips --}}
                <span class="absolute -top-3 -start-6 bg-white shadow-soft rounded-2xl px-3 py-1.5 text-xs font-bold text-brand-600 -rotate-6">٥ عروض</span>
                <span class="absolute bottom-2 -end-8 bg-white shadow-soft rounded-2xl px-3 py-1.5 text-xs font-bold text-accent-600 rotate-6">أفضل سعر</span>
            </div>
        </div>

        {{-- Copy + CTA --}}
        <div class="bg-white rounded-t-[2.5rem] shadow-soft px-7 pt-9 pb-10 pb-safe text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 leading-snug text-balance">
                أنت تطلب…<br><span class="text-brand-600">والتجار يتنافسون عليك</span>
            </h1>
            <p class="mt-3 text-gray-500 max-w-sm mx-auto leading-relaxed">
                انشر ما تحتاجه، واستقبل عروضًا من تجار موثوقين، واختر الأفضل — بسرعة ومجانًا.
            </p>

            <div class="mt-6 flex items-center justify-center gap-2">
                <span class="w-6 h-2 rounded-full bg-brand-600"></span>
                <span class="w-2 h-2 rounded-full bg-brand-200"></span>
                <span class="w-2 h-2 rounded-full bg-brand-200"></span>
            </div>

            <form method="POST" action="{{ route('onboarding.complete') }}" class="mt-7">
                @csrf
                <button class="w-full inline-flex items-center justify-center gap-2 bg-brand-600 text-white font-bold text-lg rounded-full py-4 shadow-fab active:scale-[.99] transition">
                    ابدأ الآن
                    <x-icon name="arrow-left" class="w-5 h-5" />
                </button>
            </form>
        </div>
    </div>
</body>
</html>
