<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Concerns\Pushable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentRejected extends Notification
{
    use Pushable, Queueable;

    public function __construct(public Payment $payment)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $this->withPush(['database', 'broadcast']);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'item' => $this->payment->itemLabel(),
            'amount' => $this->payment->amount,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /** @return array<string, mixed> */
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => __('Payment not verified'),
            'body' => __('We could not verify your transfer. Please contact support.'),
            'url' => route('merchant.billing'),
        ];
    }
}
