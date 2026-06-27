<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'name_ar', 'credits', 'price', 'grants_tier', 'is_active', 'sort_order'])]
class CreditPackage extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'credits' => 'integer', 'price' => 'integer'];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }

    public function label(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name;
    }

    public function isUnlimited(): bool
    {
        return $this->credits === null;
    }
}
