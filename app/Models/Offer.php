<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'request_id', 'merchant_profile_id', 'lead_id', 'price', 'currency',
    'warranty', 'delivery_days', 'description', 'negotiation_enabled', 'status',
])]
class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'negotiation_enabled' => 'boolean',
            'price' => 'integer',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['submitted', 'shortlisted', 'accepted']);
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }
}
