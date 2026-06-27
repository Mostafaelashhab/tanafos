<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewOffer extends Notification
{
    use Queueable;

    public function __construct(public Offer $offer)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'offer_id' => $this->offer->id,
            'request_id' => $this->offer->request_id,
            'title' => $this->offer->request->title,
            'merchant' => $this->offer->merchantProfile->business_name,
            'price' => $this->offer->price,
            'currency' => $this->offer->currency,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
