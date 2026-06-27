<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? null,
            ],
        );

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
        $endpoint = $request->validate(['endpoint' => ['required', 'string']])['endpoint'];

        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $endpoint))
            ->delete();

        return response()->noContent();
    }
}
