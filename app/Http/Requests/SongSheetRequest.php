<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'updated_at'         => ['required', 'date'],
            'sections'           => ['required', 'array', 'list', 'min:1'],
            'sections.*'         => ['array'],
            'sections.*.section' => ['nullable', 'string', 'max:100'],
            'sections.*.label'   => ['nullable', 'string', 'max:100'],
            'sections.*.lines'   => ['present', 'array', 'list'],
            'sections.*.lines.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
