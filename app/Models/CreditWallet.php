<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CreditWalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|float $balance
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Database\Factories\CreditWalletFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditWallet whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'user_id',
    'balance',
])]
class CreditWallet extends Model
{
    /** @use HasFactory<CreditWalletFactory> */
    use HasFactory;

    const SCALE = 1_000_000;

    /**
     * Get and set the balance value.
     *
     * @return Attribute<float, float>
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): int|float => $value / self::SCALE,
            set: fn (mixed $value): int => (int) round($value * self::SCALE)
        );
    }
}
