<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['key' => 'starter', 'name' => 'Starter', 'name_ar' => 'المبتدئ', 'credits' => 100, 'price' => 299, 'grants_tier' => null, 'sort_order' => 0],
            ['key' => 'growth', 'name' => 'Growth', 'name_ar' => 'النمو', 'credits' => 500, 'price' => 999, 'grants_tier' => null, 'sort_order' => 1],
            ['key' => 'pro', 'name' => 'Pro', 'name_ar' => 'المحترف', 'credits' => null, 'price' => 2999, 'grants_tier' => 'premium', 'sort_order' => 2],
        ];

        foreach ($packages as $p) {
            CreditPackage::updateOrCreate(['key' => $p['key']], $p);
        }

        $plans = [
            ['key' => 'basic', 'name' => 'Basic', 'name_ar' => 'أساسي', 'tier' => 'basic', 'price' => 299, 'sort_order' => 0],
            ['key' => 'gold', 'name' => 'Gold', 'name_ar' => 'ذهبي', 'tier' => 'gold', 'price' => 799, 'sort_order' => 1],
            ['key' => 'premium', 'name' => 'Premium', 'name_ar' => 'بريميوم', 'tier' => 'premium', 'price' => 1999, 'sort_order' => 2],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(['key' => $p['key']], $p);
        }
    }
}
