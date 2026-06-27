<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'merchant_profile_id', 'kind', 'item_key', 'method', 'amount',
    'sender_number', 'reference', 'proof_path', 'status', 'reviewed_by', 'reviewed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['amount' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** The purchased item (CreditPackage or Plan), resolved by kind+key. */
    public function item(): CreditPackage|Plan|null
    {
        return $this->kind === 'package'
            ? CreditPackage::where('key', $this->item_key)->first()
            : Plan::where('key', $this->item_key)->first();
    }

    public function itemLabel(): string
    {
        return $this->item()?->label() ?? $this->item_key;
    }

    public function methodLabel(): string
    {
        $m = config("banha.payment.methods.{$this->method}");

        return $m ? (app()->getLocale() === 'ar' ? $m['name_ar'] : $m['name']) : $this->method;
    }
}
