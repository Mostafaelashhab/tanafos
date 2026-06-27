<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'merchant_profile_id', 'type', 'amount', 'balance_after',
    'price', 'description', 'reference_type', 'reference_id',
])]
class CreditTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\CreditTransactionFactory> */
    use HasFactory;

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
