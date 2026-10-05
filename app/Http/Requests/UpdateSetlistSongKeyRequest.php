<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSetlistSongKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'musical_key' => ['present', 'nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'musical_key' => __('Key'),
        ];
    }
}
