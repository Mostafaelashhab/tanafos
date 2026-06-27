@php
    $packages = \App\Models\CreditPackage::active()->get();
    $plans = \App\Models\Plan::active()->get();
@endphp

<x-marketing-layout :title="__('Pricing')" description="باقات نقاط العملاء وخطط الاشتراك الشهري للتجار على Tanafos.">

    <section class="max-w-4xl mx-auto px-6 pt-16 pb-8 text-center">
        <h1 class="text-4xl font-extrabold mb-4">أسعار بسيطة وعادلة</h1>
        <p class="text-lg text-gray-600">للمشترين دائمًا مجانًا. التجار يختارون نقاط العملاء أو اشتراكًا شهريًا.</p>
    </section>

    {{-- Credit packages --}}
    <section class="max-w-6xl mx-auto px-6 py-8">
        <h2 class="text-2xl font-bold mb-6 text-center">باقات نقاط العملاء</h2>
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($packages as $pkg)
                @php($featured = $pkg->key === 'growth')
                <div @class([
                    'rounded-2xl p-7 ring-1 flex flex-col',
                    'bg-brand-600 text-white ring-brand-600 shadow-xl shadow-brand-600/20 md:-translate-y-2' => $featured,
                    'bg-white ring-gray-100' => ! $featured,
                ])>
                    @if ($featured)
                        <span class="self-start px-3 py-1 rounded-full bg-white/20 text-xs font-semibold mb-3">الأكثر شيوعًا</span>
                    @endif
                    <div class="font-bold text-lg">{{ $pkg->name_ar }}</div>
                    <div class="mt-3 text-4xl font-extrabold">
                        {{ $pkg->isUnlimited() ? '∞' : $pkg->credits }}
                        <span class="text-base font-medium {{ $featured ? 'text-brand-100' : 'text-gray-500' }}">{{ $pkg->isUnlimited() ? 'غير محدود' : 'نقطة' }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-bold {{ $featured ? 'text-white' : 'text-brand-600' }}">{{ $pkg->price }} <span class="text-sm font-medium">ج.م</span></div>
                    <a href="{{ route('register') }}" @class([
                        'mt-6 text-center px-5 py-2.5 rounded-full font-semibold',
                        'bg-white text-brand-700 hover:bg-brand-50' => $featured,
                        'bg-brand-600 text-white hover:bg-brand-700' => ! $featured,
                    ])>ابدأ الآن</a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Subscription plans --}}
    <section class="bg-gray-50 py-16 mt-8">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-2xl font-bold mb-2 text-center">خطط الاشتراك الشهري</h2>
            <p class="text-gray-600 text-center mb-8">عروض غير محدودة بدون استهلاك نقاط.</p>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($plans as $plan)
                    <div class="bg-white rounded-2xl p-7 shadow-soft flex flex-col">
                        <div class="font-bold text-lg">{{ $plan->name_ar }}</div>
                        <div class="mt-3 text-3xl font-extrabold text-gray-900">{{ $plan->price }} <span class="text-base font-medium text-gray-500">ج.م/شهر</span></div>
                        <ul class="mt-5 space-y-2 text-sm text-gray-700 flex-1">
                            <li class="flex items-center gap-2"><span class="text-brand-600"><x-icon name="check" class="w-5 h-5" /></span> عروض غير محدودة</li>
                            <li class="flex items-center gap-2"><span class="text-brand-600"><x-icon name="check" class="w-5 h-5" /></span> أولوية في المطابقة</li>
                            <li class="flex items-center gap-2"><span class="text-brand-600"><x-icon name="check" class="w-5 h-5" /></span> تحليلات الأداء</li>
                        </ul>
                        <a href="{{ route('register') }}" class="mt-6 text-center px-5 py-2.5 rounded-full bg-gray-900 text-white font-semibold hover:bg-black">اشترك</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="max-w-3xl mx-auto px-6 py-16 text-center">
        <h2 class="text-2xl font-bold mb-4">للمشترين؟ مجاني تمامًا</h2>
        <p class="text-gray-600 mb-6">انشر طلباتك واستقبل العروض دون أي رسوم.</p>
        <a href="{{ route('register') }}" class="inline-block px-8 py-3 rounded-full bg-brand-600 text-white font-bold text-lg hover:bg-brand-700">انشر طلبك الأول</a>
    </section>

</x-marketing-layout>
