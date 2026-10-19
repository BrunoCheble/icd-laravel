<?php

namespace App\Modules\Visitors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
