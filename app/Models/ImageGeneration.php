<?php

namespace App\Models;

use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use Carbon\CarbonImmutable;
use Database\Factories\ImageGenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string $idempotency_key
 * @property string $prompt
 * @property Quality $quality
 * @property Size $size
 * @property array<array-key, mixed>|null $reference_images
 * @property string $estimated_cost
 * @property Status $status
 * @property string|null $image_url
 * @property CarbonImmutable|null $image_url_expires_at
 * @property string|null $provider_id
 * @property string|null $provider_cost
 * @property string|null $user_cost
 * @property array<array-key, mixed>|null $provider_error
 * @property array<array-key, mixed>|null $provider_response
 * @property CarbonImmutable|null $dispatched_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read string $prompt_title
 * @property-read User $user
 *
 * @method static \Database\Factories\ImageGenerationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereDispatchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereEstimatedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereIdempotencyKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereImageUrlExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration wherePrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereProviderCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereProviderError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereProviderResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereQuality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereReferenceImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereUserCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'user_id',
    'idempotency_key',
    'prompt',
    'quality',
    'size',
    'estimated_cost',
    'reference_images',
    'status',
    'image_url',
    'image_url_expires_at',
    'provider_id',
    'provider_cost',
    'user_cost',
    'provider_error',
    'provider_response',
    'dispatched_at',
])]
#[RouteKey('ulid')]
class ImageGeneration extends Model
{
    /** @use HasFactory<ImageGenerationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quality' => Quality::class,
            'size' => Size::class,
            'status' => Status::class,
            'reference_images' => 'array',
            'provider_error' => 'array',
            'provider_response' => 'array',
            'image_url_expires_at' => 'datetime',
            'dispatched_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($generation): void {
            $generation->ulid ??= (string) Str::ulid();
        });
    }

    public function isPendingDispatch(): bool
    {
        return $this->dispatched_at !== null && $this->provider_id === null;
    }

    public function isInFlight(): bool
    {
        return $this->dispatched_at !== null
            && ! $this->status->isTerminal();
    }

    /**
     * Get a clean, single-line truncated title from the prompt.
     *
     * @return Attribute<string, never>
     */
    protected function promptTitle(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $clean = trim(preg_replace('/\s+/', ' ', (string) $this->prompt));

                return $clean !== ''
                    ? Str::limit($clean, 50, '...')
                    : 'Untitled generation';
            },
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
