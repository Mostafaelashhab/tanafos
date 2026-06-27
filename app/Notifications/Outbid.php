<?php

namespace App\Notifications;

use App\Models\Auction;
use App\Notifications\Concerns\Pushable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class Outbid extends Notification
{
    use Pushable, Queueable;

    public function __construct(public Auction $auction)
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
            'auction_id' => $this->auction->id,
            'title' => $this->auction->title,
            'price' => $this->auction->current_price,
            'currency' => $this->auction->currency,
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
            'title' => __('You have been outbid'),
            'body' => __(':title is now :n :c', [
                'title' => $this->auction->title,
                'n' => $this->auction->current_price,
                'c' => $this->auction->currency,
            ]),
            'url' => route('auctions.show', $this->auction->id),
        ];
    }
}
