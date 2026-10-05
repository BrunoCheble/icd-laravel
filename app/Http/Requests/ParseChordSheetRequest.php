<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParseChordSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:500000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'source' => __('Chord sheet'),
        ];
    }
}
