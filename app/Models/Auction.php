<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'seller_id', 'category_id', 'title', 'description', 'condition', 'city', 'currency',
    'starting_price', 'bid_increment', 'reserve_price', 'current_price', 'bids_count',
    'highest_bid_id', 'winner_id', 'status', 'ends_at', 'closed_at',
])]
class Auction extends Model
{
    /** @use HasFactory<\Database\Factories\AuctionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ends_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function highestBid(): BelongsTo
    {
        return $this->belongsTo(Bid::class, 'highest_bid_id');
    }

    /** Accepting bids right now. */
    public function isLive(): bool
    {
        return $this->status === 'live' && $this->ends_at->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->status !== 'live' || $this->ends_at->isPast();
    }

    /** Smallest acceptable next bid. */
    public function minNextBid(): int
    {
        return $this->bids_count > 0
            ? $this->current_price + $this->bid_increment
            : $this->starting_price;
    }

    /** Reserve (if any) has been met by the current price. */
    public function reserveMet(): bool
    {
        return $this->reserve_price === null || $this->current_price >= $this->reserve_price;
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'live')->where('ends_at', '>', now());
    }

    public function scopeForSeller(Builder $query, User $user): Builder
    {
        return $query->where('seller_id', $user->id);
    }
}
