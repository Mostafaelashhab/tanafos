<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo data for the imported-demand (scraping) feature: realistic Arabic
 * "wanted" posts marked as scraped + commission-exempt. Some land in the admin
 * review queue (draft); some are published with leads so merchants see the
 * "no commission" badge immediately. No AI/network calls — safe to run anytime.
 *
 *   php artisan db:seed --class=ImportedDemandSeeder
 */
class ImportedDemandSeeder extends Seeder
{
    use WithoutModelEvents; // don't fire Enrich/Match jobs (no Claude cost)

    public function run(): void
    {
        $merchants = MerchantProfile::query()->whereNotNull('verified_at')->get();
        if ($merchants->isEmpty()) {
            $merchants = MerchantProfile::factory()->verified()->count(3)->create();
        }

        foreach ($this->demand() as $i => $row) {
            $externalId = 'demo-'.($i + 1);

            // Idempotent: skip if this demo post was already imported.
            if (Request::where('source_platform', $row['platform'])->where('external_id', $externalId)->exists()) {
                continue;
            }

            $categoryId = Category::where('slug', $row['slug'])->value('id')
                ?? Category::where('is_active', true)->orderBy('sort_order')->value('id');

            $published = $row['published'] ?? false;

            $request = Request::create([
                'buyer_id' => null,
                'category_id' => $categoryId,
                'title' => $row['title'],
                'description' => $row['text'],
                'budget_min' => $row['budget_min'] ?? null,
                'budget_max' => $row['budget_max'] ?? null,
                'city' => $row['city'] ?? null,
                'condition' => $row['condition'] ?? 'any',
                'urgency' => $row['urgency'] ?? 'normal',
                'contact_name' => $row['name'] ?? null,
                'contact_phone' => $row['phone'] ?? null,
                'source' => 'scraped',
                'source_platform' => $row['platform'],
                'source_url' => $row['url'] ?? null,
                'external_id' => $externalId,
                'commission_exempt' => true,
                'imported_at' => now(),
                'status' => $published ? 'open' : 'draft',
                'published_at' => $published ? now() : null,
            ]);

            // Published demand gets leads so it shows up in merchants' inboxes now.
            if ($published) {
                $targets = $merchants->where('id', '!=', null)
                    ->filter(fn (MerchantProfile $m) => $m->categories->isEmpty() || $m->categories->contains('id', $categoryId))
                    ->take(4);

                if ($targets->isEmpty()) {
                    $targets = $merchants->take(3);
                }

                foreach ($targets as $merchant) {
                    Lead::firstOrCreate(
                        ['request_id' => $request->id, 'merchant_profile_id' => $merchant->id],
                        [
                            'quality_score' => random_int(60, 96),
                            'distance_km' => random_int(0, 1) ? random_int(2, 80) : null,
                            'status' => 'notified',
                        ],
                    );
                }
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function demand(): array
    {
        return [
            // --- Published (show as live leads) ---
            [
                'slug' => 'laptops', 'platform' => 'facebook', 'published' => true,
                'title' => 'محتاج لابتوب لينوفو مستعمل بحالة ممتازة للدراسة',
                'text' => "السلام عليكم، محتاج لابتوب لينوفو i5 جيل تامن أو أحدث، رامات 8 جيجا على الأقل، بحالة كويسة جدًا للدراسة. الميزانية حوالي 15 ألف. للتواصل واتساب.",
                'budget_min' => 12000, 'budget_max' => 16000, 'city' => 'القاهرة', 'condition' => 'used',
                'name' => 'Ahmed', 'phone' => '01022345504', 'url' => 'https://facebook.com/groups/buysell/posts/101',
            ],
            [
                'slug' => 'phones', 'platform' => 'facebook', 'published' => true,
                'title' => 'عايز آيفون 13 نضيف بسعر كويس',
                'text' => "بدوّر على آيفون 13 128 جيجا، البطارية فوق 90%، نضيف ومن غير خدوش. أفضّل من حد موثوق. السعر في حدود 30 ألف.",
                'budget_min' => 26000, 'budget_max' => 32000, 'city' => 'الجيزة', 'condition' => 'used', 'urgency' => 'high',
                'name' => 'Mostafa', 'phone' => '01099887766', 'url' => 'https://facebook.com/groups/buysell/posts/102',
            ],
            [
                'slug' => 'electronics', 'platform' => 'olx', 'published' => true,
                'title' => 'مطلوب تكييف شارب 1.5 حصان بارد ساخن',
                'text' => "محتاج تكييف شارب 1.5 حصان بارد/ساخن إنفرتر، جديد بضمان الوكيل، مع التركيب في المعادي.",
                'budget_min' => 22000, 'budget_max' => 28000, 'city' => 'القاهرة', 'condition' => 'new',
                'name' => 'Sara', 'phone' => '01211223344', 'url' => 'https://olx.com.eg/ad/ac-201',
            ],
            [
                'slug' => 'laptops', 'platform' => 'facebook', 'published' => true,
                'title' => 'لابتوب للألعاب بكارت شاشة RTX',
                'text' => "بدوّر على لابتوب جيمنج كارت RTX 3060 أو أعلى، شاشة 144Hz، رامات 16 جيجا. ممكن جديد أو استيراد.",
                'budget_min' => 35000, 'budget_max' => 50000, 'city' => 'الإسكندرية', 'condition' => 'any',
                'name' => 'Karim', 'phone' => '01533445566', 'url' => 'https://facebook.com/groups/gaming/posts/77',
            ],

            // --- Pending review (admin "Imported demand" queue) ---
            [
                'slug' => 'phones', 'platform' => 'facebook',
                'title' => 'حد يعرف محل شاشات موبايل أصلية في وسط البلد؟',
                'text' => "شاشتي اتكسرت، محتاج أغيّرها بشاشة أصلية سامسونج S21. حد يرشّحلي محل كويس في وسط البلد؟",
                'city' => 'القاهرة', 'name' => 'Nour', 'phone' => '01077665544', 'url' => 'https://facebook.com/groups/cairo/posts/88',
            ],
            [
                'slug' => 'electronics', 'platform' => 'olx',
                'title' => 'مطلوب غسالة أتوماتيك 7 كيلو',
                'text' => "محتاجة غسالة أتوماتيك 7 كيلو، توشيبا أو إل جي، حالة ممتازة أو جديدة، مع التوصيل لفيصل.",
                'budget_min' => 8000, 'budget_max' => 14000, 'city' => 'الجيزة', 'name' => 'Heba', 'phone' => '01400112233',
                'url' => 'https://olx.com.eg/ad/wash-330',
            ],
            [
                'slug' => 'laptops', 'platform' => 'facebook',
                'title' => 'محتاج ماك بوك إير M1 مستعمل',
                'text' => "بدوّر على MacBook Air M1، 256 جيجا، حالة زي الجديد، مع الشاحن الأصلي.",
                'budget_min' => 30000, 'budget_max' => 38000, 'city' => 'القاهرة', 'condition' => 'used',
                'name' => 'Tarek', 'phone' => '01066554433', 'url' => 'https://facebook.com/groups/apple/posts/55',
            ],
            [
                'slug' => 'electronics', 'platform' => 'facebook',
                'title' => 'مطلوب فني يركّب ريسيفر ودش',
                'text' => "محتاج حد يركّب لي دش وريسيفر HD في منطقة مدينة نصر، ضروري الأسبوع ده.",
                'city' => 'القاهرة', 'urgency' => 'high', 'name' => 'Omar', 'phone' => '01122334455',
                'url' => 'https://facebook.com/groups/nasrcity/posts/91',
            ],
        ];
    }
}
