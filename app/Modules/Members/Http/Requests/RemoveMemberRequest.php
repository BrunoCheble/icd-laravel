<?php

namespace App\Modules\Members\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveMemberRequest extends FormRequest
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
