<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string $idempotency_key
 * @property string $prompt
 * @property \App\Enums\Quality $quality
 * @property \App\Enums\Size $size
 * @property array<array-key, mixed>|null $reference_images
 * @property \App\Enums\Status $status
 * @property string|null $image_url
 * @property \Carbon\CarbonImmutable|null $image_url_expires_at
 * @property string|null $provider_id
 * @property string|null $provider_cost
 * @property array<array-key, mixed>|null $provider_error
 * @property array<array-key, mixed>|null $provider_response
 * @property \Carbon\CarbonImmutable|null $dispatched_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read string $prompt_title
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\ImageGenerationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereDispatchedAt($value)
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImageGeneration whereUserId($value)
 */
	class ImageGeneration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ImageGeneration> $generations
 * @property-read int|null $generations_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

