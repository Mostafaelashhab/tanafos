<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['request_id', 'buyer_id', 'merchant_profile_id', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    /** Is this user a participant (buyer or the merchant's owner)? */
    public function includes(User $user): bool
    {
        return $user->id === $this->buyer_id
            || $user->id === $this->merchantProfile->user_id;
    }
}
