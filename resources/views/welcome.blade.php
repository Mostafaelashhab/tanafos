@php
    $categories = \App\Models\Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get();
    $stats = [
        ['value' => \App\Models\Category::count(), 'label' => 'فئة'],
        ['value' => \App\Models\MerchantProfile::whereNotNull('verified_at')->count(), 'label' => 'تاجر موثّق'],
        ['value' => \App\Models\Request::count(), 'label' => 'طلب منشور'],
    ];
@endphp

<x-marketing-layout>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50 to-white">
        <div class="absolute -top-24 -start-24 w-96 h-96 bg-brand-200/40 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -end-24 w-96 h-96 bg-violet-200/40 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 pt-20 pb-16 text-center">
            <span class="inline-block px-4 py-1.5 rounded-full bg-white/70 ring-1 ring-brand-100 text-brand-700 text-sm font-medium mb-6">
                سوق الطلب العكسي · مصر
            </span>
            <h1 class="text-4xl sm:text-6xl font-extrabold leading-[1.15] tracking-tight">
                أنت تطلب…
                <span class="text-brand-600">والتجار يتنافسون عليك</span>
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-lg text-gray-600">
                بدلاً من البحث في عشرات المتاجر، انشر ما تحتاجه واحصل على أفضل العروض من تجار موثوقين — بسرعة ومجانًا.
            </p>
            <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-7 py-3 rounded-full bg-brand-600 text-white font-semibold text-lg shadow-lg shadow-brand-600/20 hover:bg-brand-700">
                    <x-icon name="plus" class="w-5 h-5" /> {{ __('Request products') }}
                </a>
                <a href="{{ route('merchants') }}" wire:navigate class="inline-flex items-center gap-2 px-7 py-3 rounded-full bg-white ring-1 ring-gray-200 text-gray-800 font-semibold text-lg hover:bg-gray-50">
                    <x-icon name="storefront" class="w-5 h-5" /> {{ __('Sell as a merchant') }}
                </a>
            </div>

            {{-- Trust stats --}}
            <div class="mt-14 grid grid-cols-3 max-w-lg mx-auto gap-4">
                @foreach ($stats as $stat)
                    <div>
                        <div class="text-3xl font-extrabold text-gray-900">{{ number_format($stat['value']) }}+</div>
                        <div class="text-sm text-gray-500 mt-1">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="max-w-6xl mx-auto px-6 py-20">
        <h2 class="text-center text-3xl font-bold mb-3">كيف يعمل؟</h2>
        <p class="text-center text-gray-500 mb-12">ثلاث خطوات من الطلب إلى الصفقة.</p>
        <div class="grid gap-8 md:grid-cols-3">
            @foreach ([
                ['plus', 'انشر طلبك', 'اكتب ما تريد شراءه وميزانيتك ومدينتك في دقيقة واحدة.'],
                ['inbox', 'استقبل العروض', 'يصلك التجار المناسبون، ويتنافسون بعروضهم عليك.'],
                ['badge-check', 'اختر الأفضل', 'قارن الأسعار والتقييمات، تفاوض، واختر العرض الفائز.'],
            ] as $i => [$icon, $title, $desc])
                <div class="relative bg-white rounded-2xl p-7 shadow-soft shadow-sm">
                    <div class="w-12 h-12 rounded-xl bg-brand-600 text-white flex items-center justify-center mb-4">
                        <x-icon :name="$icon" class="w-6 h-6" />
                    </div>
                    <div class="absolute top-7 {{ app()->getLocale() === 'ar' ? 'left-7' : 'right-7' }} text-4xl font-black text-gray-100">{{ $i + 1 }}</div>
                    <h3 class="font-bold text-lg mb-2">{{ $title }}</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Value props --}}
    <section class="bg-gray-50 py-20">
        <div class="max-w-6xl mx-auto px-6 grid gap-10 md:grid-cols-2">
            <div class="bg-white rounded-2xl p-8 shadow-soft">
                <h3 class="text-2xl font-bold mb-5 flex items-center gap-2"><span class="text-brand-600"><x-icon name="shopping-bag" class="w-7 h-7" /></span> للمشترين</h3>
                <ul class="space-y-3 text-gray-700">
                    @foreach (['وفّر وقتك ولا تبحث في عشرات المتاجر', 'احصل على عروض متعددة بسرعة', 'قارن الأسعار فورًا', 'تفاوض مباشرة مع التجار'] as $item)
                        <li class="flex items-start gap-2"><span class="text-brand-600 mt-0.5 shrink-0"><x-icon name="check" class="w-5 h-5" /></span><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-white rounded-2xl p-8 shadow-soft">
                <h3 class="text-2xl font-bold mb-5 flex items-center gap-2"><span class="text-brand-600"><x-icon name="storefront" class="w-7 h-7" /></span> للتجار</h3>
                <ul class="space-y-3 text-gray-700">
                    @foreach (['عملاء جاهزون للشراء', 'تقليل تكاليف الإعلانات', 'معدلات تحويل أعلى', 'اكتساب عملاء من منطقتك'] as $item)
                        <li class="flex items-start gap-2"><span class="text-brand-600 mt-0.5 shrink-0"><x-icon name="check" class="w-5 h-5" /></span><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Categories --}}
    <section class="max-w-6xl mx-auto px-6 py-20">
        <h2 class="text-center text-3xl font-bold mb-3">تسوّق من كل الفئات</h2>
        <p class="text-center text-gray-500 mb-12">من الإلكترونيات إلى العقارات — اطلب أي شيء.</p>
        <div class="flex flex-wrap justify-center gap-3">
            @foreach ($categories as $category)
                <span class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-white ring-1 ring-gray-200 text-gray-700 font-medium hover:ring-brand-300 hover:text-brand-700 transition">
                    <x-icon name="tag" class="w-4 h-4 text-brand-400" /> {{ $category->name_ar }}
                </span>
            @endforeach
        </div>
    </section>

    {{-- FAQ --}}
    <section class="max-w-3xl mx-auto px-6 py-16">
        <h2 class="text-center text-3xl font-bold mb-10">أسئلة شائعة</h2>
        <div class="space-y-3">
            @foreach ([
                ['هل الخدمة مجانية للمشترين؟', 'نعم، نشر الطلبات واستقبال العروض مجاني تمامًا للمشترين.'],
                ['كيف يربح التجار العملاء؟', 'يشترك التجار أو يشترون نقاط العملاء، ويقدّمون عروضهم على الطلبات المناسبة.'],
                ['كم يستغرق وصول العروض؟', 'يبدأ وصول العروض خلال دقائق من نشر طلبك بعد مطابقته بالتجار المناسبين.'],
                ['هل يمكنني التفاوض؟', 'بالتأكيد — يمكنك محادثة التجار مباشرة والتفاوض قبل اختيار العرض الفائز.'],
            ] as [$q, $a])
                <details class="group bg-white rounded-xl shadow-soft p-5">
                    <summary class="flex items-center justify-between cursor-pointer font-semibold text-gray-900 list-none">
                        {{ $q }}
                        <span class="text-gray-400 group-open:rotate-180 transition"><x-icon name="chevron-down" class="w-5 h-5" /></span>
                    </summary>
                    <p class="mt-3 text-gray-600 text-sm leading-relaxed">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="max-w-5xl mx-auto px-6 pb-24">
        <div class="rounded-3xl bg-brand-600 px-8 py-14 text-center text-white relative overflow-hidden">
            <div class="absolute -top-16 -end-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
            <h2 class="relative text-3xl font-extrabold mb-3">اطلب أي شيء. واحصل على أفضل عرض.</h2>
            <p class="relative text-brand-100 mb-8">بدلاً من البحث… دع السوق يأتي إليك.</p>
            <a href="{{ route('register') }}" class="relative inline-block px-8 py-3 rounded-full bg-white text-brand-700 font-bold text-lg hover:bg-brand-50">
                ابدأ الآن مجانًا
            </a>
        </div>
    </section>

</x-marketing-layout>
