<?php

namespace App\Modules\Music\Http\Requests;

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
            // Where the block starts in the recording, and a block with no bars, followed by its time alone.
            'sections.*.start'     => ['nullable', 'string', 'max:20'],
            'sections.*.time_only' => ['nullable', 'boolean'],
        ];
    }
}
