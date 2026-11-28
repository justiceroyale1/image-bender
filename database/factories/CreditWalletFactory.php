<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreditWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditWallet>
 */
class CreditWalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<model-property<CreditWallet>, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => fake()->randomFloat(2, 0, 1000), // Random balance between 0 and 1000
        ];
    }
}
