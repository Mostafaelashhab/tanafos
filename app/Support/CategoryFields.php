<?php

namespace App\Support;

use App\Models\Category;

/**
 * Category-aware request specifications + iconography.
 * Fields are resolved by the category slug, falling back to its parent slug.
 *
 * Field shape: ['key' => string, 'label' => string, 'type' => 'text|number|select', 'options' => [..]?]
 */
class CategoryFields
{
    /** Extra spec fields keyed by category (or parent) slug. */
    private static function map(): array
    {
        return [
            'electronics' => [
                ['key' => 'brand', 'label' => 'Brand', 'type' => 'text'],
                ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
            ],
            'phones' => [
                ['key' => 'brand', 'label' => 'Brand', 'type' => 'text'],
                ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
                ['key' => 'storage', 'label' => 'Storage', 'type' => 'select', 'options' => ['64GB', '128GB', '256GB', '512GB', '1TB']],
            ],
            'laptops' => [
                ['key' => 'brand', 'label' => 'Brand', 'type' => 'text'],
                ['key' => 'ram', 'label' => 'RAM', 'type' => 'select', 'options' => ['8GB', '16GB', '32GB', '64GB']],
                ['key' => 'storage', 'label' => 'Storage', 'type' => 'select', 'options' => ['256GB', '512GB', '1TB', '2TB']],
            ],
            'gaming' => [
                ['key' => 'platform', 'label' => 'Platform', 'type' => 'select', 'options' => ['PlayStation', 'Xbox', 'PC', 'Nintendo']],
            ],
            'cars' => [
                ['key' => 'make', 'label' => 'Make', 'type' => 'text'],
                ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
                ['key' => 'year', 'label' => 'Year', 'type' => 'number'],
                ['key' => 'transmission', 'label' => 'Transmission', 'type' => 'select', 'options' => ['Automatic', 'Manual']],
                ['key' => 'fuel', 'label' => 'Fuel', 'type' => 'select', 'options' => ['Petrol', 'Diesel', 'Electric', 'Hybrid']],
            ],
            'real-estate' => [
                ['key' => 'property_type', 'label' => 'Property type', 'type' => 'select', 'options' => ['Apartment', 'Villa', 'Office', 'Land', 'Shop']],
                ['key' => 'area_m2', 'label' => 'Area (m²)', 'type' => 'number'],
                ['key' => 'rooms', 'label' => 'Rooms', 'type' => 'number'],
            ],
            'furniture' => [
                ['key' => 'item', 'label' => 'Item', 'type' => 'text'],
                ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
            ],
            'construction' => [
                ['key' => 'material_type', 'label' => 'Material type', 'type' => 'text'],
                ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'text'],
            ],
            'medical-equipment' => [
                ['key' => 'device_type', 'label' => 'Device type', 'type' => 'text'],
            ],
            'services' => [
                ['key' => 'service_type', 'label' => 'Service type', 'type' => 'text'],
            ],
            'education' => [
                ['key' => 'subject', 'label' => 'Subject', 'type' => 'text'],
                ['key' => 'level', 'label' => 'Level', 'type' => 'select', 'options' => ['Primary', 'Preparatory', 'Secondary', 'University']],
            ],
            'travel' => [
                ['key' => 'destination', 'label' => 'Destination', 'type' => 'text'],
                ['key' => 'travelers', 'label' => 'Travelers', 'type' => 'number'],
            ],
            'home-appliances' => [
                ['key' => 'appliance', 'label' => 'Appliance', 'type' => 'text'],
                ['key' => 'brand', 'label' => 'Brand', 'type' => 'text'],
            ],
            'industrial-supplies' => [
                ['key' => 'item', 'label' => 'Item', 'type' => 'text'],
                ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'text'],
            ],
            'fashion' => [
                ['key' => 'item', 'label' => 'Item', 'type' => 'text'],
                ['key' => 'size', 'label' => 'Size', 'type' => 'text'],
            ],
            'beauty' => [
                ['key' => 'product_type', 'label' => 'Product type', 'type' => 'text'],
                ['key' => 'brand', 'label' => 'Brand', 'type' => 'text'],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function for(?Category $category): array
    {
        if (! $category) {
            return [];
        }

        $map = self::map();

        return $map[$category->slug]
            ?? ($category->parent_id ? ($map[optional($category->parent)->slug] ?? []) : []);
    }

    /** Icon name (from the x-icon set) for a category, resolving parent. */
    public static function icon(?Category $category): string
    {
        if (! $category) {
            return 'tag';
        }

        $icons = [
            'electronics' => 'cpu', 'phones' => 'phone', 'laptops' => 'laptop', 'gaming' => 'gamepad',
            'cars' => 'car', 'real-estate' => 'building', 'furniture' => 'sofa', 'construction' => 'wrench',
            'medical-equipment' => 'heart', 'services' => 'briefcase', 'education' => 'academic',
            'travel' => 'plane', 'home-appliances' => 'home', 'industrial-supplies' => 'cog',
            'fashion' => 'shirt', 'beauty' => 'sparkles',
        ];

        return $icons[$category->slug]
            ?? ($category->parent_id ? ($icons[optional($category->parent)->slug] ?? 'tag') : 'tag');
    }
}
