<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'name_ar', 'tier', 'price', 'is_active', 'sort_order'])]
class Plan extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price' => 'integer'];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }

    public function label(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name;
    }
}
