<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Chord corrections from the public page: only existing chords, changed (to a chord name) or removed (to null).
     */
    public function rules(): array
    {
        return [
            'changes'        => ['required', 'array', 'min:1', 'max:300'],
            'changes.*.n'    => ['required', 'integer', 'min:0'],
            'changes.*.from' => ['required', 'string', 'max:30'],
            'changes.*.to'   => ['nullable', 'string', 'max:30', 'regex:/^[A-G][#b]?[^\s\[\]|]*$/'],
            'author'         => ['nullable', 'string', 'max:60'],
            'note'           => ['nullable', 'string', 'max:500'],
        ];
    }
}
