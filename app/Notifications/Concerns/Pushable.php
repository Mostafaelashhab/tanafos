<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\WebPushChannel;

trait Pushable
{
    /** Append the Web Push channel to the base channels when push is configured. */
    protected function withPush(array $channels): array
    {
        if (config('banha.push.enabled')) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }
}
