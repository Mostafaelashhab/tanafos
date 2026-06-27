@php
    $categories = \App\Models\Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get();
    $stats = [
        ['value' => \App\Models\MerchantProfile::whereNotNull('verified_at')->count(), 'label' => 'تاجر موثّق'],
        ['value' => \App\Models\Request::count(), 'label' => 'طلب منشور'],
        ['value' => \App\Models\Category::count(), 'label' => 'فئة'],
    ];
    $chipColors = [
        ['bg-brand-50', 'text-brand-600'], ['bg-rose-50', 'text-rose-500'], ['bg-amber-50', 'text-amber-600'],
        ['bg-sky-50', 'text-sky-600'], ['bg-emerald-50', 'text-emerald-600'], ['bg-violet-50', 'text-violet-600'],
    ];
@endphp

<x-marketing-layout>

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-50 via-white to-white"></div>
        <div class="absolute -top-32 -start-32 w-[28rem] h-[28rem] bg-brand-200/50 rounded-full blur-3xl"></div>
        <div class="absolute top-40 -end-32 w-[26rem] h-[26rem] bg-violet-200/40 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-6 pt-16 pb-20 lg:pt-24 grid lg:grid-cols-2 gap-12 items-center">
            {{-- Copy --}}
            <div class="text-center lg:text-start">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white ring-1 ring-brand-100 text-brand-700 text-sm font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    سوق الطلب العكسي · مصر
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.12] tracking-tight text-gray-900">
                    أنت تطلب…
                    <br class="hidden sm:block" />
                    <span class="text-brand-600">والتجار يتنافسون عليك</span>
                </h1>
                <p class="mt-6 max-w-xl mx-auto lg:mx-0 text-lg text-gray-600 leading-relaxed">
                    بدل ما تلف على عشرات المحلات، انشر اللي محتاجه واستقبل أحسن العروض من تجار موثوقين — في دقائق، ومجانًا تمامًا.
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center lg:justify-start gap-3">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full bg-brand-600 text-white font-bold text-lg shadow-fab hover:bg-brand-700 active:scale-[.98] transition">
                        <x-icon name="plus" class="w-5 h-5" /> انشر طلبك مجانًا
                    </a>
                    <a href="{{ route('merchants') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full bg-white ring-1 ring-gray-200 text-gray-800 font-bold text-lg hover:bg-gray-50 transition">
                        <x-icon name="storefront" class="w-5 h-5 text-brand-600" /> انضم كتاجر
                    </a>
                </div>

                <div class="mt-10 grid grid-cols-3 max-w-md mx-auto lg:mx-0 gap-4">
                    @foreach ($stats as $stat)
                        <div class="text-center lg:text-start">
                            <div class="text-3xl font-extrabold text-gray-900">{{ number_format($stat['value']) }}+</div>
                            <div class="text-sm text-gray-500 mt-1">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- App mockup --}}
            <div class="relative mx-auto w-full max-w-sm">
                <div class="absolute inset-0 bg-brand-600/10 rounded-[3rem] blur-2xl"></div>
                <div class="relative bg-white rounded-[2.5rem] shadow-soft ring-1 ring-gray-100 p-4 rotate-1">
                    {{-- request card --}}
                    <div class="rounded-3xl bg-gradient-to-br from-brand-600 to-brand-700 text-white p-5">
                        <div class="flex items-center gap-2 text-brand-100 text-xs font-semibold">
                            <x-icon name="document" class="w-4 h-4" /> طلب جديد
                        </div>
                        <div class="mt-2 font-extrabold text-xl leading-snug">محتاج آيفون ١٥ برو ماكس جديد</div>
                        <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                            <span class="bg-white/15 rounded-full px-2.5 py-1">٦٠٬٠٠٠ – ٧٥٬٠٠٠ ج</span>
                            <span class="bg-white/15 rounded-full px-2.5 py-1">القاهرة</span>
                            <span class="bg-white/15 rounded-full px-2.5 py-1">جديد</span>
                        </div>
                    </div>

                    {{-- competing offers --}}
                    <div class="px-1 pt-4 pb-1 text-xs font-bold text-gray-400">٣ عروض متنافسة</div>
                    @foreach ([['متجر الأمانة', '٦٨٬٥٠٠', true], ['تك ستور', '٧١٬٠٠٠', false], ['موبايل بلازا', '٧٣٬٢٠٠', false]] as [$name, $price, $win])
                        <div @class([
                            'flex items-center justify-between gap-3 rounded-2xl p-3 mb-2',
                            'bg-emerald-50 ring-1 ring-emerald-200' => $win,
                            'bg-gray-50' => ! $win,
                        ])>
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-white shadow-sm flex items-center justify-center text-brand-600 shrink-0">
                                    <x-icon name="storefront" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-gray-900 truncate">{{ $name }}</div>
                                    @if ($win)
                                        <div class="text-[10px] font-bold text-emerald-600 flex items-center gap-0.5"><x-icon name="check" class="w-3 h-3" /> الأفضل سعرًا</div>
                                    @else
                                        <div class="text-[10px] text-gray-400">تقييم ٤.٨</div>
                                    @endif
                                </div>
                            </div>
                            <div class="font-extrabold text-brand-700 shrink-0">{{ $price }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- floating badge --}}
                <div class="absolute -bottom-4 -start-4 bg-white rounded-2xl shadow-soft ring-1 ring-gray-100 px-4 py-3 flex items-center gap-2 -rotate-3">
                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-icon name="bolt" class="w-5 h-5" /></span>
                    <div class="leading-tight">
                        <div class="text-xs text-gray-400">وفّرت</div>
                        <div class="font-extrabold text-gray-900 text-sm">٤٬٧٠٠ ج</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ HOW IT WORKS ============ --}}
    <section class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-14">
            <div class="text-brand-600 font-bold text-sm mb-2">بسيطة وسريعة</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold">من الطلب إلى الصفقة في ٣ خطوات</h2>
        </div>
        <div class="relative grid gap-8 md:grid-cols-3">
            {{-- connecting line --}}
            <div class="hidden md:block absolute top-8 inset-x-0 h-0.5 bg-gradient-to-l from-brand-100 via-brand-300 to-brand-100"></div>
            @foreach ([
                ['plus', 'انشر طلبك', 'اكتب اللي عايز تشتريه وميزانيتك ومدينتك في دقيقة.'],
                ['inbox', 'استقبل العروض', 'التجار المناسبون يوصلهم طلبك ويتنافسوا بعروضهم.'],
                ['badge-check', 'اختر الأفضل', 'قارن الأسعار والتقييمات، فاوض، واختر العرض الفائز.'],
            ] as $i => [$icon, $title, $desc])
                <div class="relative bg-white rounded-3xl p-7 shadow-soft text-center">
                    <div class="relative mx-auto w-16 h-16 mb-4">
                        <div class="w-16 h-16 rounded-2xl bg-brand-600 text-white flex items-center justify-center shadow-fab">
                            <x-icon :name="$icon" class="w-7 h-7" />
                        </div>
                        <div class="absolute -top-2 -end-2 w-7 h-7 rounded-full bg-white ring-2 ring-brand-100 text-brand-600 font-extrabold text-sm flex items-center justify-center">{{ $i + 1 }}</div>
                    </div>
                    <h3 class="font-extrabold text-lg mb-2">{{ $title }}</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ DUAL AUDIENCE ============ --}}
    <section class="bg-gradient-to-b from-white to-brand-50/50 py-20">
        <div class="max-w-6xl mx-auto px-6 grid gap-6 md:grid-cols-2">
            <div class="bg-white rounded-3xl p-8 shadow-soft">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-5"><x-icon name="shopping-bag" class="w-7 h-7" /></div>
                <h3 class="text-2xl font-extrabold mb-1">للمشترين</h3>
                <p class="text-gray-500 text-sm mb-5">اطلب مرة، وخلي السوق ييجي لك.</p>
                <ul class="space-y-3 text-gray-700">
                    @foreach (['وفّر وقتك — مفيش لفّ على المحلات', 'عروض متعددة في دقائق', 'قارن الأسعار والتقييمات فورًا', 'فاوض مباشرة قبل ما تقرر'] as $item)
                        <li class="flex items-start gap-2.5"><span class="text-emerald-500 mt-0.5 shrink-0"><x-icon name="check" class="w-5 h-5" /></span><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="mt-7 inline-flex items-center gap-1.5 font-bold text-brand-600 hover:gap-2.5 transition-all">ابدأ كمشتري <x-icon name="arrow-left" class="w-4 h-4" /></a>
            </div>
            <div class="bg-brand-600 text-white rounded-3xl p-8 shadow-fab relative overflow-hidden">
                <div class="absolute -top-12 -end-10 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
                <div class="relative">
                    <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center mb-5"><x-icon name="storefront" class="w-7 h-7" /></div>
                    <h3 class="text-2xl font-extrabold mb-1">للتجار</h3>
                    <p class="text-brand-100 text-sm mb-5">عملاء جاهزين للشراء، بدون إعلانات مكلفة.</p>
                    <ul class="space-y-3">
                        @foreach (['عملاء بنيّة شراء حقيقية', 'تكلفة أقل من الإعلانات', 'معدلات تحويل أعلى', 'طلبات من منطقتك بالظبط'] as $item)
                            <li class="flex items-start gap-2.5"><span class="text-emerald-300 mt-0.5 shrink-0"><x-icon name="check" class="w-5 h-5" /></span><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('merchants') }}" wire:navigate class="mt-7 inline-flex items-center gap-1.5 font-bold text-white hover:gap-2.5 transition-all">انضم كتاجر <x-icon name="arrow-left" class="w-4 h-4" /></a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ CATEGORIES ============ --}}
    <section class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-extrabold mb-3">اطلب أي حاجة</h2>
            <p class="text-gray-500">من الإلكترونيات للعقارات — كل الفئات في مكان واحد.</p>
        </div>
        <div class="flex flex-wrap justify-center gap-3">
            @foreach ($categories as $category)
                @php($chip = $chipColors[$loop->index % count($chipColors)])
                <span class="inline-flex items-center gap-2 ps-2 pe-5 py-2 rounded-full bg-white ring-1 ring-gray-200 text-gray-700 font-semibold hover:ring-brand-300 hover:-translate-y-0.5 transition">
                    <span class="w-8 h-8 rounded-full {{ $chip[0] }} {{ $chip[1] }} flex items-center justify-center">
                        <x-icon :name="\App\Support\CategoryFields::icon($category)" class="w-4 h-4" />
                    </span>
                    {{ $category->name_ar }}
                </span>
            @endforeach
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section class="max-w-3xl mx-auto px-6 py-16">
        <h2 class="text-center text-3xl font-extrabold mb-10">أسئلة شائعة</h2>
        <div class="space-y-3">
            @foreach ([
                ['هل الخدمة مجانية للمشترين؟', 'أيوه، نشر الطلبات واستقبال العروض مجاني تمامًا للمشترين.'],
                ['كيف يربح التجار العملاء؟', 'التجار يشتركوا أو يشتروا نقاط، ويقدّموا عروضهم على الطلبات المناسبة ليهم.'],
                ['كم ياخد وصول العروض؟', 'العروض تبدأ توصل خلال دقائق من نشر طلبك بعد مطابقته بالتجار المناسبين.'],
                ['أقدر أفاوض؟', 'طبعًا — تقدر تكلّم التجار مباشرة وتفاوض قبل ما تختار العرض الفائز.'],
            ] as [$q, $a])
                <details class="group bg-white rounded-2xl shadow-soft p-5">
                    <summary class="flex items-center justify-between cursor-pointer font-bold text-gray-900 list-none">
                        {{ $q }}
                        <span class="text-brand-400 group-open:rotate-180 transition"><x-icon name="chevron-down" class="w-5 h-5" /></span>
                    </summary>
                    <p class="mt-3 text-gray-600 text-sm leading-relaxed">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="max-w-5xl mx-auto px-6 pb-24">
        <div class="rounded-[2.5rem] bg-gradient-to-br from-brand-600 to-violet-700 px-8 py-16 text-center text-white relative overflow-hidden">
            <div class="absolute -top-16 -end-10 w-72 h-72 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-20 -start-10 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
            <h2 class="relative text-3xl sm:text-4xl font-extrabold mb-3">اطلب أي حاجة. خُد أحسن عرض.</h2>
            <p class="relative text-brand-100 mb-8 text-lg">بدل ما تدوّر… خلي السوق ييجي لك.</p>
            <a href="{{ route('register') }}" class="relative inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-white text-brand-700 font-extrabold text-lg hover:bg-brand-50 active:scale-[.98] transition">
                <x-icon name="plus" class="w-5 h-5" /> ابدأ الآن مجانًا
            </a>
        </div>
    </section>

</x-marketing-layout>
