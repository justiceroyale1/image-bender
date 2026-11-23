<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
