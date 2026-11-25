<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->filled('start_date') || ! is_string($value)) {
                        return;
                    }

                    $start = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('start_date'));
                    $end = CarbonImmutable::createFromFormat('Y-m-d', $value);

                    if ($start && $end && ($start->diffInDays($end) + 1) > 92) {
                        $fail('The date range cannot exceed 92 days.');
                    }
                },
            ],
        ];
    }

    public function startDate(): ?string
    {
        return $this->validated('start_date');
    }

    public function endDate(): ?string
    {
        return $this->validated('end_date');
    }

    /**
     * @return array{start_date?: string, end_date?: string}
     */
    public function filters(): array
    {
        return array_filter([
            'start_date' => $this->startDate(),
            'end_date' => $this->endDate(),
        ]);
    }
}
