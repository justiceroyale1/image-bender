
<?php

use App\Jobs\ProcessPixelforgeWebhook;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ai-image.webhook_secret' => 'test-webhook-secret',
    ]);

    Queue::fake();
});

/**
 * Generate a valid Pixelforge webhook signature.
 */
function pixelforgeSignature(
    string $payload,
    string $secret,
    int $timestamp
): string {
    $signature = hash_hmac(
        'sha256',
        "{$timestamp}.{$payload}",
        $secret
    );

    return "t={$timestamp},v1={$signature}";
}

/**
 * Send a signed webhook request.
 */
function sendPixelforgeWebhook(
    array $event,
    ?string $secret = 'test-webhook-secret',
    ?int $timestamp = null,
    bool $includeSignature = true,
    mixed $test = null
): \Illuminate\Testing\TestResponse {
    $payload = json_encode($event, JSON_THROW_ON_ERROR);

    $headers = [
        'Content-Type' => 'application/json',
    ];

    if ($includeSignature) {
        $headers['Pixelforge-Signature'] = pixelforgeSignature(
            $payload,
            $secret ?? '',
            $timestamp ?? now()->timestamp
        );
    }

    $test = $test ?? test();
    return $test->withHeaders($headers)
        ->postJson('/webhooks/pixelforge', $event);
}

function pixelforgeEvent(
    string $type = 'generation.succeeded',
    string $id = 'evt_test_123',
): array {
    return [
        'id' => $id,
        'type' => $type,
        'data' => [
            'object' => [
                'id' => 'gen_test_123',
                'status' => 'succeeded',
            ],
        ],
    ];
}

// -----------------------------------------------------------------------------
// Signature verification
// -----------------------------------------------------------------------------

describe('signature verification', function () {
    test('accepts a valid signature without a CSRF token', function () {
        $event = pixelforgeEvent();

        $response = sendPixelforgeWebhook($event);

        $response->assertNoContent();

        expect(WebhookEvent::count())->toBe(1);

        Queue::assertPushed(ProcessPixelforgeWebhook::class);
    });

    test('rejects requests without a signature', function () {
        $response = sendPixelforgeWebhook(
            pixelforgeEvent(),
            includeSignature: false
        );

        $response->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });

    test('rejects an invalid signature', function () {
        $event = pixelforgeEvent();

        $payload = json_encode($event, JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            '/webhooks/pixelforge',
            [],
            [],
            [],
            [
                'Content-Type' => 'application/json',
                'Pixelforge-Signature' => 't='.time().',v1=invalid-signature',
            ],
            $payload
        );

        $response->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });

    test('rejects requests when the webhook secret is missing', function () {
        config(['ai-image.webhook_secret' => null]);

        $response = sendPixelforgeWebhook(
            pixelforgeEvent(),
            secret: 'test-webhook-secret'
        );

        $response->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });

    test('rejects requests with an expired timestamp', function () {
        $event = pixelforgeEvent();

        $response = sendPixelforgeWebhook(
            $event,
            timestamp: time() - 301
        );

        $response->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });

    test('rejects requests with a timestamp too far in the future', function () {
        $response = sendPixelforgeWebhook(
            pixelforgeEvent(),
            timestamp: time() + 301
        );

        $response->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });

    test('accepts timestamps within the allowed tolerance', function () {
        // $this->withoutExceptionHandling();
        $this->freezeSecond();
        $now = now();

        $this->travelTo($now);

        $response = sendPixelforgeWebhook(
            pixelforgeEvent(),
            timestamp: $now->timestamp - 300,
            test: $this
        );

        $response->assertNoContent();

        expect(WebhookEvent::count())->toBe(1);
    });

    test('calculates the signature using the raw request body', function (): void {
        $data = [
            'id' => 'evt_raw',
            'type' => 'generation.succeeded',
            'data' => [],
        ];

        $payload = json_encode($data, JSON_THROW_ON_ERROR);

        $signature = pixelforgeSignature(
            $payload,
            'test-webhook-secret',
            now()->timestamp
        );

        $response = $this->withHeaders([
            'Pixelforge-Signature' => $signature,
        ])->postJson('/webhooks/pixelforge', $data);

        $response->assertNoContent();

        expect(WebhookEvent::where('event_id', 'evt_raw')->exists())->toBeTrue();
    });

});

// -----------------------------------------------------------------------------
// Event persistence and job dispatching
// -----------------------------------------------------------------------------

describe('event processing', function () {
    test('persists and dispatches a generation.succeeded event', function () {
        $event = pixelforgeEvent('generation.succeeded');

        $response = sendPixelforgeWebhook($event);

        $response->assertNoContent();

        $webhookEvent = WebhookEvent::where(
            'event_id',
            $event['id']
        )->first();

        expect($webhookEvent)->not->toBeNull()
            ->and($webhookEvent->provider)->toBe('pixelforge')
            ->and($webhookEvent->type)->toBe('generation.succeeded')
            ->and($webhookEvent->payload)->toEqual($event);

        Queue::assertPushed(
            ProcessPixelforgeWebhook::class,
            fn (ProcessPixelforgeWebhook $job) =>
                $job->webhookEvent->is($webhookEvent)
        );
    });

    test('persists and dispatches a generation.processing event', function () {
        $event = pixelforgeEvent('generation.processing');

        $response = sendPixelforgeWebhook($event);

        $response->assertNoContent();

        expect(WebhookEvent::where('event_id', $event['id'])->exists())
            ->toBeTrue();

        Queue::assertPushed(ProcessPixelforgeWebhook::class);
    });
});

// -----------------------------------------------------------------------------
// Ping handling
// -----------------------------------------------------------------------------

describe('ping events', function () {
    test('returns 204 without persisting or dispatching a job', function () {
        $event = pixelforgeEvent('ping');

        $response = sendPixelforgeWebhook($event);

        $response->assertNoContent();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });
});

// -----------------------------------------------------------------------------
// Deduplication
// -----------------------------------------------------------------------------

describe('event deduplication', function () {
    test('processes an event only once when the same event ID is received twice', function () {
        $event = pixelforgeEvent();

        $firstResponse = sendPixelforgeWebhook($event);
        $secondResponse = sendPixelforgeWebhook($event);

        $firstResponse->assertNoContent();
        $secondResponse->assertNoContent();

        expect(
            WebhookEvent::where('event_id', $event['id'])->count()
        )->toBe(1);

        Queue::assertPushed(ProcessPixelforgeWebhook::class, 1);
    });
});

// -----------------------------------------------------------------------------
// Malformed payloads
// -----------------------------------------------------------------------------

describe('payload validation', function () {
    test('rejects malformed JSON', function () {
        $payload = '{"id":';

        $response = $this->call(
            'POST',
            '/webhooks/pixelforge',
            [],
            [],
            [],
            [
                'Content-Type' => 'application/json',
                'Pixelforge-Signature' => pixelforgeSignature(
                    $payload,
                    'test-webhook-secret',
                    now()->timestamp
                ),
            ],
            $payload
        );

        $response->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });

    test('rejects events without an ID', function () {
        $event = [
            'type' => 'generation.succeeded',
            'data' => [],
        ];

        sendPixelforgeWebhook($event)->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });

    test('rejects events without a type', function () {
        $event = [
            'id' => 'evt_missing_type',
            'data' => [],
        ];

        sendPixelforgeWebhook($event)->assertBadRequest();

        expect(WebhookEvent::count())->toBe(0);

        Queue::assertNothingPushed();
    });
});
