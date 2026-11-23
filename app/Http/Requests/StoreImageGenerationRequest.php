<?php

namespace App\Http\Requests;

use App\Enums\Quality;
use App\Enums\Size;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImageGenerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:4000'],
            'quality' => ['required', 'string', Rule::in(Quality::cases())],
            'size' => ['required', 'string', Rule::in(Size::cases())],
            'idempotency_key' => ['required', 'uuid:4'],
            'reference_images' => ['array', 'nullable'],
            'reference_images.*' => ['url']
        ];
    }
}
