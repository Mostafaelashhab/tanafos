<?php

namespace App\Notifications;

use App\Models\Message;
use App\Notifications\Concerns\Pushable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewMessage extends Notification
{
    use Pushable, Queueable;

    public function __construct(public Message $message)
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
            'title' => __('New message from :sender', ['sender' => $this->message->sender?->name]),
            'body' => \Illuminate\Support\Str::limit($this->message->body, 80),
            'url' => route('conversations.show', $this->message->conversation_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'sender' => $this->message->sender?->name,
            'preview' => \Illuminate\Support\Str::limit($this->message->body, 60),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
