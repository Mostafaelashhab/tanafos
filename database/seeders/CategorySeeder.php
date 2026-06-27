<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Categories from the PRD. Top-level entries may carry `children`.
     * [en, ar, icon, [children...]]
     */
    private array $tree = [
        ['Electronics', 'إلكترونيات', 'cpu-chip', [
            ['Phones', 'هواتف', 'device-phone-mobile'],
            ['Laptops', 'لابتوبات', 'computer-desktop'],
            ['Gaming', 'ألعاب', 'puzzle-piece'],
        ]],
        ['Cars', 'سيارات', 'truck', []],
        ['Real Estate', 'عقارات', 'home-modern', []],
        ['Furniture', 'أثاث', 'rectangle-stack', []],
        ['Construction', 'بناء وتشييد', 'wrench-screwdriver', []],
        ['Medical Equipment', 'معدات طبية', 'heart', []],
        ['Services', 'خدمات', 'briefcase', []],
        ['Education', 'تعليم', 'academic-cap', []],
        ['Travel', 'سفر', 'paper-airplane', []],
        ['Home Appliances', 'أجهزة منزلية', 'home', []],
        ['Industrial Supplies', 'مستلزمات صناعية', 'cog-6-tooth', []],
        ['Fashion', 'أزياء', 'sparkles', []],
        ['Beauty', 'تجميل', 'face-smile', []],
    ];

    public function run(): void
    {
        $order = 0;

        foreach ($this->tree as [$en, $ar, $icon, $children]) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($en)],
                ['name' => $en, 'name_ar' => $ar, 'icon' => $icon, 'sort_order' => $order++, 'is_active' => true],
            );

            $childOrder = 0;
            foreach ($children as [$cen, $car, $cicon]) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($cen)],
                    [
                        'parent_id' => $parent->id,
                        'name' => $cen,
                        'name_ar' => $car,
                        'icon' => $cicon,
                        'sort_order' => $childOrder++,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
