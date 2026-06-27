<?php

namespace App\Models;

use App\Jobs\EnrichRequestJob;
use App\Jobs\MatchRequestJob;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'buyer_id', 'category_id', 'title', 'description',
    'budget_min', 'budget_max', 'currency', 'city', 'lat', 'lng',
    'condition', 'urgency', 'payment_method', 'warranty_required',
    'preferred_delivery', 'specifications', 'status',
    'selected_offer_id', 'published_at', 'expires_at',
])]
class Request extends Model
{
    /** @use HasFactory<\Database\Factories\RequestFactory> */
    use HasFactory;

    public const CONDITIONS = ['new', 'used', 'any'];
    public const URGENCIES = ['low', 'normal', 'high'];
    public const PAYMENT_METHODS = ['cash', 'card', 'installment', 'any'];

    /** Statuses a buyer still considers "live". */
    public const ACTIVE_STATUSES = ['open', 'matched'];

    /** Dispatch matching whenever a request becomes "open" (new or transitioned). */
    protected static function booted(): void
    {
        static::saved(function (self $request) {
            $becameOpen = $request->status === 'open'
                && ($request->wasRecentlyCreated || $request->wasChanged('status'));

            if ($becameOpen) {
                // Enrich first so matching can use AI-extracted specs; both queued.
                EnrichRequestJob::dispatch($request);
                MatchRequestJob::dispatch($request);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'warranty_required' => 'boolean',
            'specifications' => 'array',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function selectedOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'selected_offer_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeForBuyer(Builder $query, User $buyer): Builder
    {
        return $query->where('buyer_id', $buyer->id);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** Transition draft → open and dispatch matching (matching wired in Phase 2). */
    public function publish(): void
    {
        $this->forceFill([
            'status' => 'open',
            'published_at' => $this->published_at ?? now(),
        ])->save();
    }
}
