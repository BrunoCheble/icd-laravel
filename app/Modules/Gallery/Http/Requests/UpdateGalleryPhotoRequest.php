<?php

namespace App\Modules\Gallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGalleryPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'caption' => ['sometimes', 'nullable', 'string', 'max:160'],
            'is_hidden' => ['sometimes', 'boolean'],
            'is_cover' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['caption' => __('caption')];
    }
}
