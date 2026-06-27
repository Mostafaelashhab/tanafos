<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'request_id', 'reviewer_id', 'merchant_profile_id',
    'rating', 'quality_score', 'delivery_score', 'response_score', 'comment',
])]
class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }
}
