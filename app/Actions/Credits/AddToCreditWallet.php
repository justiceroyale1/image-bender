<?php

namespace App\Actions\Credits;

use App\Models\CreditWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AddToCreditWallet
{
    public function forUser(User $user, int $amount): CreditWallet
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount) {
            $wallet = CreditWallet::lockForUpdate()
                ->where('user_id', $user->id)
                ->first();


            if (! $wallet) {
                return $user->creditWallet()
                    ->create([
                        'balance' => $amount,
                    ]);
            }

            $wallet->balance += $amount;
            $wallet->save();

            return $wallet;
        });
    }
}
