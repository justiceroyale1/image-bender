<?php

use App\Http\Requests\Credits\StoreCreditPurchaseRequest;
use App\Models\User;

final readonly class NewCreditPurcahseData
{
    public function __construct(
        public int $amount,
        public User $buyer,
    ) {}

    public static function fromRequest(StoreCreditPurchaseRequest $request): self
    {
        return new self($request->amount(), $request->user());
    }
}
