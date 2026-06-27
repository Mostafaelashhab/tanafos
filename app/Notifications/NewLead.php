<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewLead extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead)
    {
    }

    /**
     * Delivery channels: persisted to DB and pushed live over the user's private channel.
     *
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
