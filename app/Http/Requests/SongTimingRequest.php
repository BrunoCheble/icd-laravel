<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongTimingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts'   => ['nullable', 'array'],
            'starts.*' => ['nullable', 'string', 'max:12', 'regex:/^\d+(:\d{1,2}){0,2}(\.\d+)?$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'starts.*' => __('Start'),
        ];
    }

    public function messages(): array
    {
        return [
            'starts.*.regex' => __('Use the m:ss format, e.g. 1:05.'),
        ];
    }
}
