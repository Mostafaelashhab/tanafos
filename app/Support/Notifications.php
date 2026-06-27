<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Presentation helpers for stored database notifications (NewLead, NewOffer).
 */
class Notifications
{
    private static function shortName(?DatabaseNotification $note): string
    {
        return class_basename($note?->type ?? '');
    }

    public static function title(?DatabaseNotification $note): string
    {
        $data = $note?->data ?? [];

        return match (self::shortName($note)) {
            'NewLead' => __('New lead: :title', ['title' => $data['title'] ?? '']),
            'NewOffer' => __('New offer on :title', ['title' => $data['title'] ?? '']),
            'OfferAccepted' => __('You won the deal: :title', ['title' => $data['title'] ?? '']),
            'NewMessage' => __('New message from :sender', ['sender' => $data['sender'] ?? '']),
            default => __('Notification'),
        };
    }

    public static function icon(?DatabaseNotification $note): string
    {
        return match (self::shortName($note)) {
            'NewLead' => 'inbox',
            'NewOffer' => 'shopping-bag',
            'OfferAccepted' => 'trophy',
            'NewMessage' => 'chat',
            default => 'bell',
        };
    }

    public static function url(?DatabaseNotification $note): string
    {
        $data = $note?->data ?? [];

        return match (self::shortName($note)) {
            'NewLead' => isset($data['lead_id'])
                ? route('merchant.leads.show', $data['lead_id'])
                : route('merchant.leads.index'),
            'NewOffer' => isset($data['request_id'])
                ? route('requests.show', $data['request_id'])
                : route('dashboard'),
            'OfferAccepted' => isset($data['request_id'])
                ? route('requests.show', $data['request_id'])
                : route('dashboard'),
            'NewMessage' => isset($data['conversation_id'])
                ? route('conversations.show', $data['conversation_id'])
                : route('dashboard'),
            default => route('dashboard'),
        };
    }
}
