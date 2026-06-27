<?php

namespace App\Services;

use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\MerchantProfile;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditService
{
    /**
     * Apply a purchased credit package: add credits (or grant a tier for Pro),
     * record the ledger entry. Payment capture is handled upstream (gateway/manual).
     * Accepts a CreditPackage or its key.
     */
    public function purchasePackage(MerchantProfile $merchant, CreditPackage|string $package): CreditTransaction
    {
        $package = $package instanceof CreditPackage
            ? $package
            : CreditPackage::where('key', $package)->first();

        if (! $package) {
            throw new InvalidArgumentException('Unknown credit package.');
        }

        return DB::transaction(function () use ($merchant, $package) {
            $merchant = MerchantProfile::whereKey($merchant->id)->lockForUpdate()->firstOrFail();

            // Pro / unlimited grants a subscription tier instead of a finite balance.
            if ($package->isUnlimited()) {
                $merchant->update(['subscription_tier' => $package->grants_tier ?? 'premium']);

                return $this->record($merchant, 'purchase', 0, $package->price,
                    __('Purchased :name (unlimited)', ['name' => $package->name]));
            }

            $merchant->increment('credits_balance', $package->credits);

            return $this->record($merchant->refresh(), 'purchase', $package->credits, $package->price,
                __('Purchased :name (:n credits)', ['name' => $package->name, 'n' => $package->credits]));
        });
    }

    /** Switch the merchant's subscription plan and log it. Accepts a Plan or its key. */
    public function subscribe(MerchantProfile $merchant, Plan|string $plan): CreditTransaction
    {
        $plan = $plan instanceof Plan ? $plan : Plan::where('key', $plan)->first();

        if (! $plan) {
            throw new InvalidArgumentException('Unknown plan.');
        }

        return DB::transaction(function () use ($merchant, $plan) {
            $merchant->update(['subscription_tier' => $plan->tier]);

            return $this->record($merchant, 'subscription', 0, $plan->price,
                __('Subscribed to :name', ['name' => $plan->name]));
        });
    }

    /** Admin grant/adjustment of points (can be negative). Logs a ledger entry. */
    public function grantPoints(MerchantProfile $merchant, int $amount, ?string $note = null): CreditTransaction
    {
        return DB::transaction(function () use ($merchant, $amount, $note) {
            $merchant = MerchantProfile::whereKey($merchant->id)->lockForUpdate()->firstOrFail();

            // Never let an adjustment drive the balance negative.
            $delta = max($amount, -$merchant->credits_balance);
            $merchant->increment('credits_balance', $delta);

            return $this->record($merchant->refresh(), 'bonus', $delta, null,
                $note ?: __('Admin adjustment'));
        });
    }

    /**
     * Record consumption of credits for an action (e.g. submitting an offer).
     * Assumes the balance was already decremented in the same transaction;
     * pass the post-decrement balance.
     */
    public function recordConsumption(MerchantProfile $merchant, int $balanceAfter, Model $reference = null): CreditTransaction
    {
        return $this->record($merchant, 'consume', -1, null, __('Lead credit used'), $balanceAfter, $reference);
    }

    private function record(
        MerchantProfile $merchant,
        string $type,
        int $amount,
        ?int $price,
        string $description,
        ?int $balanceAfter = null,
        ?Model $reference = null,
    ): CreditTransaction {
        return CreditTransaction::create([
            'merchant_profile_id' => $merchant->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter ?? $merchant->credits_balance,
            'price' => $price,
            'description' => $description,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
        ]);
    }
}
