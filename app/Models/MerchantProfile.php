<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'business_name', 'description', 'logo_path',
    'city', 'lat', 'lng', 'verified_at', 'credits_balance',
    'subscription_tier', 'rating_avg', 'completed_deals',
    'response_minutes_avg', 'win_rate',
])]
class MerchantProfile extends Model
{
    /** @use HasFactory<\Database\Factories\MerchantProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'rating_avg' => 'decimal:2',
            'win_rate' => 'decimal:2',
            'credits_balance' => 'integer',
            'completed_deals' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    /** Subscription merchants submit without spending credits; otherwise need ≥1. */
    public function onSubscription(): bool
    {
        return $this->subscription_tier !== 'none';
    }

    public function canSubmitOffer(): bool
    {
        return $this->onSubscription() || $this->hasCredits(1);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** The highest level whose deal threshold this merchant has met. */
    public function level(): array
    {
        $level = config('banha.levels')[0];

        foreach (config('banha.levels') as $candidate) {
            if ($this->completed_deals >= $candidate['min_deals']) {
                $level = $candidate;
            }
        }

        return $level;
    }

    /** Earned achievement badges (computed from performance). */
    public function badges(): array
    {
        $badges = [];

        if ($this->isVerified()) {
            $badges[] = 'verified';
        }
        if ($this->rating_avg >= 4.5 && $this->completed_deals >= 10) {
            $badges[] = 'top_merchant';
        }
        if ($this->response_minutes_avg !== null && $this->response_minutes_avg <= 60) {
            $badges[] = 'fast_responder';
        }
        if ($this->completed_deals >= 5) {
            $badges[] = 'rising_star';
        }

        return $badges;
    }

    public function hasCredits(int $amount = 1): bool
    {
        return $this->credits_balance >= $amount;
    }
}
