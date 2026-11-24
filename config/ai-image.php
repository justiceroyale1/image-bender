<?php
return [
    'driver' => env('AI_IMAGE_DRIVER', 'mock'),
    'base_url' => env('AI_IMAGE_BASE_URL', 'https://pixelforge.promptedit.dev/v1'),
    'api_key' => env('AI_IMAGE_API_KEY'),
    'webhook_secret' => env('AI_IMAGE_WEBHOOK_SECRET'),
];
