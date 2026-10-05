<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChordSheetToStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Current structure, so the section times already marked are kept.
            'structure'   => ['nullable', 'string'],
            'chord_sheet' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                $decoded = json_decode($value, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail(__('The :attribute field contains invalid JSON: :error', ['error' => json_last_error_msg()]));
                } elseif (! is_array($decoded['sections'] ?? null) || collect($decoded['sections'])->contains(fn ($section) => ! is_array($section['lines'] ?? null))) {
                    $fail(__('The :attribute must have a "sections" list, each with its "lines".'));
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return [
            'chord_sheet' => __('Chord sheet'),
        ];
    }

    public function sections(): array
    {
        return json_decode($this->validated('chord_sheet'), true)['sections'];
    }
}
