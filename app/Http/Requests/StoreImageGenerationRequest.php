<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Quality;
use App\Enums\Size;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImageGenerationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:4000'],
            'quality' => ['required', Rule::enum(Quality::class)],
            'size' => ['required', Rule::enum(Size::class)],
            'idempotency_key' => ['required', 'uuid:4'],
            'reference_images' => ['array', 'nullable'],
            'reference_images.*' => ['url'],
        ];
    }
}
