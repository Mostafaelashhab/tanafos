<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentApproved;
use App\Notifications\PaymentRejected;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private CreditService $credits)
    {
    }

    /** Approve a pending payment: apply the package/plan, then mark it approved. */
    public function approve(Payment $payment, User $admin): void
    {
        if (! $payment->isPending()) {
            return;
        }

        DB::transaction(function () use ($payment, $admin) {
            $merchant = $payment->merchantProfile;

            if ($payment->kind === 'package') {
                $this->credits->purchasePackage($merchant, $payment->item_key);
            } else {
                $this->credits->subscribe($merchant, $payment->item_key);
            }

            $payment->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
        });

        $payment->merchantProfile->user->notify(new PaymentApproved($payment));
    }

    public function reject(Payment $payment, User $admin): void
    {
        if (! $payment->isPending()) {
            return;
        }

        $payment->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $payment->merchantProfile->user->notify(new PaymentRejected($payment));
    }
}
