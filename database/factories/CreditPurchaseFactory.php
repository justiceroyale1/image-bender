<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditPurchase>
 */
class CreditPurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<model-property<CreditPurchase>, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_id' => fake()->uuid(),
            'user_id' => User::factory(),
            'amount' => fake()->numberBetween(5, 1000),
        ];
    }
}
