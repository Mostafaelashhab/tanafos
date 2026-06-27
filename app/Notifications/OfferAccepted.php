<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Notifications\Concerns\Pushable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OfferAccepted extends Notification
{
    use Pushable, Queueable;

    public function __construct(public Offer $offer)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->withPush(['database', 'broadcast']);
    }

    /** @return array<string, mixed> */
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => __('You won the deal: :title', ['title' => $this->offer->request->title]),
            'body' => __('The buyer selected your offer. Reach out to finalise.'),
            'url' => route('requests.show', $this->offer->request_id),
        ];
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
            'price' => $this->offer->price,
            'currency' => $this->offer->currency,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
