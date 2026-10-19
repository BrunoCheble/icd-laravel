<?php

namespace App\Modules\Members\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAnniversariesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['nullable', 'integer', 'between:1,12'],
        ];
    }
}
