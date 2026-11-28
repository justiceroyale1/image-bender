<?php

namespace App\Actions\Credits;

use App\Exceptions\Credits\DuplicateCreditPurchased;
use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCreditPurchase
{
    public function __construct(public AddToCreditWallet $addToCreditWallet) {}

    public function execute(string $referenceId, int $amount, User $buyer): CreditPurchase
    {
        return DB::transaction(function () use ($referenceId, $amount, $buyer) {
            $purchase = $buyer->creditPurchases()->firstOrCreate([
                'reference_id' => $referenceId,
            ], [
                'amount' => $amount,
            ]);

            if (! $purchase->wasRecentlyCreated) {
                throw new DuplicateCreditPurchased;
            }

            $this->addToCreditWallet->forUser($buyer, $amount);

            return $purchase;
        });
    }
}
