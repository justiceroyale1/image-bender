<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $provider
 * @property string $event_id
 * @property string $type
 * @property array<array-key, mixed>|null $payload
 * @property CarbonImmutable|null $processed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|WebhookEvent newModelQuery()
 * @method static Builder<static>|WebhookEvent newQuery()
 * @method static Builder<static>|WebhookEvent query()
 * @method static Builder<static>|WebhookEvent whereCreatedAt($value)
 * @method static Builder<static>|WebhookEvent whereEventId($value)
 * @method static Builder<static>|WebhookEvent whereId($value)
 * @method static Builder<static>|WebhookEvent wherePayload($value)
 * @method static Builder<static>|WebhookEvent whereProcessedAt($value)
 * @method static Builder<static>|WebhookEvent whereProvider($value)
 * @method static Builder<static>|WebhookEvent whereType($value)
 * @method static Builder<static>|WebhookEvent whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'provider',
    'event_id',
    'type',
    'payload',
    'processed_at',
])]
class WebhookEvent extends Model
{
    use MassPrunable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(30));
    }
}
