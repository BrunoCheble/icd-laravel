<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongSheetSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * One section of the chord sheet (as shown on the layout page) with its lines edited: lyrics with the chords in
     * brackets ("Esp[G]írito Santo").
     */
    public function rules(): array
    {
        return [
            'index'      => ['required', 'integer', 'min:0'],
            'lines'      => ['present', 'array', 'list', 'max:200'],
            'lines.*'    => ['nullable', 'string', 'max:500'],
            'updated_at' => ['required', 'string'],
        ];
    }
}
