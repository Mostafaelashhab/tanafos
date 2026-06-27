<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'request_id', 'merchant_profile_id', 'quality_score',
    'distance_km', 'status', 'charged_at', 'viewed_at',
])]
class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'charged_at' => 'datetime',
            'viewed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }

    public function offer(): HasOne
    {
        return $this->hasOne(Offer::class);
    }

    /** Leads belonging to a given merchant profile. */
    public function scopeForMerchant(Builder $query, MerchantProfile $profile): Builder
    {
        return $query->where('merchant_profile_id', $profile->id);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['notified', 'viewed']);
    }

    public function markViewed(): void
    {
        if ($this->status === 'notified') {
            $this->forceFill(['status' => 'viewed', 'viewed_at' => now()])->save();
        }
    }
}
