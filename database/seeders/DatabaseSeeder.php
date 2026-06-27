<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);
        $this->call(PlanSeeder::class);

        // Demo admin
        User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@banha.shop',
            'type' => 'admin',
        ]);

        // Demo buyer with a couple of sample requests
        $buyer = User::factory()->create([
            'name' => 'Demo Buyer',
            'email' => 'buyer@banha.shop',
            'type' => 'buyer',
        ]);

        $phones = Category::where('slug', 'phones')->value('id');
        $laptops = Category::where('slug', 'laptops')->value('id');

        Request::factory()->published()->for($buyer, 'buyer')->create([
            'category_id' => $phones,
            'title' => 'محتاج آيفون 15 برو ماكس جديد',
            'budget_min' => 60000,
            'budget_max' => 75000,
            'city' => 'بنها',
        ]);

        Request::factory()->for($buyer, 'buyer')->create([
            'category_id' => $laptops,
            'title' => 'لابتوب للألعاب بكارت شاشة قوي',
            'status' => 'draft',
            'budget_min' => 30000,
            'budget_max' => 45000,
            'city' => 'القاهرة',
        ]);

        // Demo merchant + profile, serving a few categories
        $merchant = User::factory()->merchant()->create([
            'name' => 'Demo Merchant',
            'email' => 'merchant@banha.shop',
        ]);

        $profile = MerchantProfile::factory()->verified()->create([
            'user_id' => $merchant->id,
            'business_name' => 'متجر بنها للإلكترونيات',
            'credits_balance' => 100,
            'subscription_tier' => 'none', // credit-based, so offers visibly consume credits
        ]);

        $profile->categories()->sync(
            Category::whereIn('slug', ['electronics', 'phones', 'laptops'])->pluck('id')
        );

        // A handful of additional merchants for matching tests later
        MerchantProfile::factory(8)->verified()->create()->each(function (MerchantProfile $p) {
            $p->categories()->sync(Category::inRandomOrder()->limit(3)->pluck('id'));
        });
    }
}
