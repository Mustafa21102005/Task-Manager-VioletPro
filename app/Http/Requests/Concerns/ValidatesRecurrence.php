<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Recurrence;
use App\Enums\RecurrenceUnit;
use Illuminate\Validation\Rule;

trait ValidatesRecurrence
{
    protected function prepareForValidation(): void
    {
        $isCustom = $this->input('recurrence') === Recurrence::Custom->value;
        $isWeekly = $this->input('recurrence_unit') === RecurrenceUnit::Week->value;

        // Keep only the custom details that apply, so old values never linger
        $this->merge([
            'recurrence_interval' => $isCustom ? $this->input('recurrence_interval') : null,
            'recurrence_unit' => $isCustom ? $this->input('recurrence_unit') : null,
            'recurrence_days' => $isCustom && $isWeekly ? $this->input('recurrence_days') : null,
        ]);
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function recurrenceRules(): array
    {
        return [
            'recurrence' => ['nullable', Rule::enum(Recurrence::class)],
            'recurrence_interval' => ['nullable', 'required_if:recurrence,custom', 'integer', 'min:1', 'max:365'],
            'recurrence_unit' => ['nullable', 'required_if:recurrence,custom', Rule::enum(RecurrenceUnit::class)],
            'recurrence_days' => ['nullable', 'array', 'max:7'],
            'recurrence_days.*' => ['integer', 'between:1,7', 'distinct'],
        ];
    }
}
