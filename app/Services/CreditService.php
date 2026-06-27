<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\MerchantProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditService
{
    /**
     * Apply a purchased credit package: add credits (or grant a tier for Pro),
     * record the ledger entry. Payment capture is handled upstream (gateway/manual).
     */
    public function purchasePackage(MerchantProfile $merchant, string $packageKey): CreditTransaction
    {
        $package = config("banha.credit_packages.$packageKey");

        if (! $package) {
            throw new InvalidArgumentException("Unknown credit package: {$packageKey}");
        }

        return DB::transaction(function () use ($merchant, $package) {
            $merchant = MerchantProfile::whereKey($merchant->id)->lockForUpdate()->firstOrFail();

            // Pro / unlimited grants a subscription tier instead of a finite balance.
            if (array_key_exists('grants_tier', $package)) {
                $merchant->update(['subscription_tier' => $package['grants_tier']]);

                return $this->record($merchant, 'purchase', 0, (int) $package['price'],
                    __('Purchased :name (unlimited)', ['name' => $package['name']]));
            }

            $credits = (int) $package['credits'];
            $merchant->increment('credits_balance', $credits);

            return $this->record($merchant->refresh(), 'purchase', $credits, (int) $package['price'],
                __('Purchased :name (:n credits)', ['name' => $package['name'], 'n' => $credits]));
        });
    }

    /** Switch the merchant's subscription plan and log it. */
    public function subscribe(MerchantProfile $merchant, string $planKey): CreditTransaction
    {
        $plan = config("banha.plans.$planKey");

        if (! $plan) {
            throw new InvalidArgumentException("Unknown plan: {$planKey}");
        }

        return DB::transaction(function () use ($merchant, $plan) {
            $merchant->update(['subscription_tier' => $plan['tier']]);

            return $this->record($merchant, 'subscription', 0, (int) $plan['price'],
                __('Subscribed to :name', ['name' => $plan['name']]));
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
