<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:20000'],
        ];
    }
}
