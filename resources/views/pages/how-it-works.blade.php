<x-marketing-layout :title="__('How it works')" description="كيف يعمل سوق الطلب العكسي: من نشر الطلب إلى إتمام الصفقة.">

    <section class="max-w-4xl mx-auto px-6 pt-16 pb-10 text-center">
        <h1 class="text-4xl font-extrabold mb-4">كيف يعمل {{ config('app.name') }}؟</h1>
        <p class="text-lg text-gray-600">نموذج بسيط يقلب السوق: أنت تنشر الطلب، والتجار يأتون إليك.</p>
    </section>

    {{-- Buyer flow --}}
    <section class="max-w-5xl mx-auto px-6 py-10">
        <h2 class="text-2xl font-bold mb-8 flex items-center gap-2"><span class="text-brand-600"><x-icon name="shopping-bag" class="w-7 h-7" /></span> رحلة المشتري</h2>
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['plus', 'انشر طلبك', 'العنوان، الفئة، الميزانية، المدينة، والصور إن وجدت.'],
                ['sparkles', 'إثراء بالذكاء الاصطناعي', 'نستخلص المواصفات ونقترح ميزانية مناسبة تلقائيًا.'],
                ['inbox', 'مطابقة فورية', 'نرسل طلبك للتجار الأنسب حسب الفئة والموقع والتقييم.'],
                ['chat', 'استقبل وتفاوض', 'قارن العروض وتحدث مع التجار مباشرة.'],
                ['badge-check', 'اختر الفائز', 'اعتمد أفضل عرض بنقرة واحدة.'],
                ['star', 'قيّم التجربة', 'قيّم التاجر لمساعدة بقية المشترين.'],
            ] as [$icon, $title, $desc])
                <div class="bg-white rounded-2xl p-6 shadow-soft">
                    <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center mb-3"><x-icon :name="$icon" class="w-6 h-6" /></div>
                    <h3 class="font-bold mb-1">{{ $title }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Merchant flow --}}
    <section class="bg-gray-50 py-16">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-2xl font-bold mb-8 flex items-center gap-2"><span class="text-brand-600"><x-icon name="storefront" class="w-7 h-7" /></span> رحلة التاجر</h2>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['bell', 'إشعار بعميل محتمل', 'يصلك تنبيه فوري بكل طلب يناسب نشاطك.'],
                    ['eye', 'راجع المتطلبات', 'اطّلع على تفاصيل الطلب والميزانية والموقع.'],
                    ['currency', 'قدّم عرضك', 'سعر، ضمان، ومدة تسليم — تستهلك نقطة واحدة.'],
                    ['chat', 'تفاوض', 'تواصل مع المشتري لإغلاق الصفقة.'],
                    ['trophy', 'اربح العميل', 'كل صفقة ترفع تقييمك ومستواك.'],
                    ['trending-up', 'تابع أداءك', 'معدل الفوز، التحويل، والإيرادات في لوحتك.'],
                ] as [$icon, $title, $desc])
                    <div class="bg-white rounded-2xl p-6 shadow-soft">
                        <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center mb-3"><x-icon :name="$icon" class="w-6 h-6" /></div>
                        <h3 class="font-bold mb-1">{{ $title }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="max-w-3xl mx-auto px-6 py-16 text-center">
        <h2 class="text-2xl font-bold mb-4">جاهز تبدأ؟</h2>
        <a href="{{ route('register') }}" class="inline-block px-8 py-3 rounded-full bg-brand-600 text-white font-bold text-lg hover:bg-brand-700">أنشئ حسابك مجانًا</a>
    </section>

</x-marketing-layout>
