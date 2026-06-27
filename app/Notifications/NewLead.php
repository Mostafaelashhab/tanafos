<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Notifications\Concerns\Pushable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewLead extends Notification
{
    use Pushable, Queueable;

    public function __construct(public Lead $lead)
    {
    }

    /**
     * Delivery channels: persisted to DB, broadcast live, and Web Push (if configured).
     *
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
            'title' => __('New lead: :title', ['title' => $this->lead->request->title]),
            'body' => __('A buyer is looking for what you offer.'),
            'url' => route('merchant.leads.show', $this->lead->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $request = $this->lead->request;

        return [
            'lead_id' => $this->lead->id,
            'request_id' => $request->id,
            'title' => $request->title,
            'category' => $request->category?->label(),
            'quality_score' => $this->lead->quality_score,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
