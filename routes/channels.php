<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Per-conversation private channel — only the buyer and the merchant participant.
Broadcast::channel('conversations.{conversation}', function (User $user, Conversation $conversation) {
    return $conversation->includes($user);
});
