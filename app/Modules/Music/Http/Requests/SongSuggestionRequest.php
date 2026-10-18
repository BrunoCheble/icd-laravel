<?php

namespace App\Modules\Music\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Chord corrections from the public page: existing chords changed (to a chord name) or removed (to null), and
     * chords inserted ("insert": `offset` half beats after the start of chord n, where no chord started).
     */
    public function rules(): array
    {
        return [
            'changes'        => ['required', 'array', 'min:1', 'max:300'],
            'changes.*.type'   => ['nullable', 'in:change,insert'],
            'changes.*.n'      => ['required', 'integer', 'min:0'],
            'changes.*.offset' => ['required_if:changes.*.type,insert', 'nullable', 'integer', 'min:1', 'max:4096'],
            'changes.*.from' => ['required', 'string', 'max:30'],
            'changes.*.to'     => ['required_if:changes.*.type,insert', 'nullable', 'string', 'max:30', 'regex:/^[A-G][#b]?[^\s\[\]|]*$/'],
            'author'         => ['nullable', 'string', 'max:60'],
            'note'           => ['nullable', 'string', 'max:500'],
        ];
    }
}
