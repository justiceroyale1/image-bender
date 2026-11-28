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

            logger()->info('AddToCreditWallet called', [
                'amount' => $amount,
                'wallet_exists' => (bool) $wallet,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6),
            ]);


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
