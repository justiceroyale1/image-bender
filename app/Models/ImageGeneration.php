<?php

namespace App\Models;

use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use Database\Factories\ImageGenerationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ImageGeneration extends Model
{
    /** @use HasFactory<ImageGenerationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'idempotency_key',
        'prompt',
        'quality',
        'size',
        'reference_images',
        'status',
        'image_url',
        'image_url_expires_at',
        'provider_id',
        'provider_cost',
        'provider_error',
        'provider_response',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'quality' => Quality::class,
            'size' => Size::class,
            'status' => Status::class,
            'reference_images' => 'array',
            'provider_error' => 'array',
            'provider_response' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($generation) {
            $generation->ulid ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function isPendingDispatch(): bool
    {
        return $this->dispatched_at !== null && $this->provider_id === null;
    }

    public function isInFlight(): bool
    {
        return $this->dispatched_at !== null
            && ! $this->status?->isTerminal();
    }
}
