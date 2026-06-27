<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    /**
     * Deliver the notification as a Web Push message to every stored
     * subscription for the notifiable. Expired endpoints are pruned.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('banha.push.enabled') || ! method_exists($notification, 'toWebPush')) {
            return;
        }

        $subscriptions = $notifiable->pushSubscriptions ?? collect();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode($notification->toWebPush($notifiable));

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('banha.push.subject'),
            'publicKey' => config('banha.push.public_key'),
            'privateKey' => config('banha.push.private_key'),
        ]]);

        $byEndpoint = [];
        foreach ($subscriptions as $sub) {
            $byEndpoint[$sub->endpoint] = $sub;
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
                ]),
                $payload,
            );
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            if (! $report->isSuccess() && $report->isSubscriptionExpired() && isset($byEndpoint[$endpoint])) {
                $byEndpoint[$endpoint]->delete();
            }
        }
    }
}
