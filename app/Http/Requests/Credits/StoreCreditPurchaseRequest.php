<?php

namespace App\Http\Requests\Credits;

use App\Actions\Credits\CalculateAvailableCredits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCreditPurchaseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CalculateAvailableCredits $action): array
    {
        $availableCredits = $action->forUser(request()->user());
        $max = (int) floor(1000000 - $availableCredits);

        return [
            'amount' => [
                'required',
                'integer',
                'min:5',
                'max:'.$max,
            ],
            'reference_id' => [
                'required',
                'uuid:4',
                'unique:credit_purchases,reference_id',
            ],
        ];
    }

    public function amount(): int
    {
        return $this->validated('amount');
    }

    public function referenceId(): string
    {
        return $this->validated('reference_id');
    }
}
