<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|float $amount
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'reference_id',
    'user_id',
    'amount',
])]
class CreditPurchase extends Model
{
    const SCALE = 1_000_000;

    /**
     * Get and set the amount value.
     *
     * @return Attribute<float, int>
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): float => $value / self::SCALE,
            set: fn (mixed $value): int => (int) round($value * self::SCALE)
        );
    }
}
