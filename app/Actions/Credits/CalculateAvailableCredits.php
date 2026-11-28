<?php

namespace App\Actions\Credits;

use App\Models\CreditWallet;
use App\Models\User;

class CalculateAvailableCredits
{
    public function forUser(User $user): float
    {
        $wallet = CreditWallet::where('user_id', $user->id)
            ->first();

        if (! $wallet) {
            return 0.0;
        }

        return $wallet->balance;
    }
}
