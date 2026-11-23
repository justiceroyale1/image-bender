<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ImageGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'idempotency_key',
        'prompt',
        'quality',
        'size',
        'reference_images',
        'status',
        'image_url',
        'provider_cost',
        'provider_error',
        'provider_response'
    ];

    protected function casts(){
        return [
            'reference_images' => 'array',
            'provider_error' => 'array',
            'provider_response' => 'array'
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
}
