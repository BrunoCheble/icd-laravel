<?php

namespace App\Modules\Music\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroySongsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:songs,id'],
        ];
    }
}
