<x-marketing-layout :title="__('For merchants')" description="انضم كتاجر إلى Tanafos: عملاء جاهزون للشراء وتكلفة إعلانات أقل.">

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-indigo-50 to-white">
        <div class="absolute -top-24 -end-24 w-96 h-96 bg-indigo-200/40 rounded-full blur-3xl"></div>
        <div class="relative max-w-5xl mx-auto px-6 pt-20 pb-14 text-center">
            <span class="inline-block px-4 py-1.5 rounded-full bg-white/70 ring-1 ring-indigo-100 text-indigo-700 text-sm font-medium mb-6">للتجار</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight">عملاء جاهزون للشراء <span class="text-indigo-600">يأتون إليك</span></h1>
            <p class="mt-5 max-w-2xl mx-auto text-lg text-gray-600">لا مزيد من الإنفاق على إعلانات لا تُحوّل. استقبل طلبات حقيقية من مشترين في منطقتك وقدّم عروضك.</p>
            <a href="{{ route('register') }}" class="mt-8 inline-block px-8 py-3 rounded-full bg-indigo-600 text-white font-bold text-lg hover:bg-indigo-700">سجّل كتاجر</a>
        </div>
    </section>

    {{-- Benefits --}}
    <section class="max-w-6xl mx-auto px-6 py-16">
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['inbox', 'عملاء مؤهّلون', 'طلبات من مشترين لديهم نية شراء فعلية.'],
                ['map-pin', 'استهداف محلي', 'اوصل لعملاء منطقتك حسب الموقع.'],
                ['currency', 'تكلفة أقل', 'ادفع مقابل العملاء المحتملين فقط — لا إعلانات مهدرة.'],
                ['bolt', 'استجابة سريعة', 'إشعارات فورية بكل فرصة جديدة.'],
                ['trophy', 'سمعة تنمو', 'التقييمات والمستويات تبني ثقة المشترين.'],
                ['trending-up', 'تحليلات واضحة', 'معدل الفوز والتحويل والإيرادات في مكان واحد.'],
            ] as [$icon, $title, $desc])
                <div class="bg-white rounded-2xl p-6 ring-1 ring-gray-100">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3"><x-icon :name="$icon" class="w-6 h-6" /></div>
                    <h3 class="font-bold mb-1">{{ $title }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Pricing teaser --}}
    <section class="bg-gray-50 py-16">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-2xl font-bold mb-3">ابدأ بباقة تناسبك</h2>
            <p class="text-gray-600 mb-8">نقاط للعملاء المحتملين أو اشتراك شهري بعروض غير محدودة.</p>
            <a href="{{ route('pricing') }}" wire:navigate class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white ring-1 ring-gray-200 font-semibold hover:bg-gray-50">
                <x-icon name="credit-card" class="w-5 h-5 text-indigo-600" /> {{ __('See pricing') }}
            </a>
        </div>
    </section>

</x-marketing-layout>
