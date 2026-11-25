<?php

namespace App\Http\Controllers\Webhooks;

use App\Jobs\ProcessPixelforgeWebhook;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PixelforgeWebhookController
{
    public function __invoke(Request $request): Response
    {
        // Verify against the raw body, before any JSON decoding.
        $payload = $request->getContent();
        $signature = [];

        foreach (explode(',', (string) $request->header('Pixelforge-Signature')) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $signature[$key] = $value;
        }

        $timestamp = (int) ($signature['t'] ?? 0);
        $rawSecret = config('ai-image.webhook_secret');
        $secret = is_string($rawSecret) ? $rawSecret : '';
        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        if ($secret === '' || ! hash_equals($expected, $signature['v1'] ?? '') || abs(now()->timestamp - $timestamp) > 300) {
            abort(400, 'Invalid Pixelforge signature.');
        }

        /** @var array{id?: string, type?: string, data?: array{object?: array<string, mixed>}}|null $event */
        $event = json_decode($payload, true);

        if (! is_array($event) || empty($event['id']) || empty($event['type'])) {
            abort(400, 'Malformed Pixelforge event payload.');
        }

        // Ignore dashboard test pings immediately
        if ($event['type'] === 'ping') {
            return response()->noContent();
        }

        // Deduplicate by (provider, event_id)
        try {
            $webhookEvent = WebhookEvent::create([
                'provider' => 'pixelforge',
                'event_id' => $event['id'],
                'type' => $event['type'],
                'payload' => $event,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->noContent();
        }

        dispatch(new ProcessPixelforgeWebhook($webhookEvent));

        return response()->noContent();
    }
}
