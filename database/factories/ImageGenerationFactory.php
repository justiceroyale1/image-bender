<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use App\Models\ImageGeneration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ImageGeneration>
 */
class ImageGenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'idempotency_key' => (string) Str::uuid(),
            'prompt' => fake()->sentence(),
            'quality' => Quality::Medium,
            'size' => Size::Square,
            'status' => Status::Queued,
        ];
    }
}
