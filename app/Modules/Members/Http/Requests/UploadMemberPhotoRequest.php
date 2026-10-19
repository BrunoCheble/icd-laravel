<?php

namespace App\Modules\Members\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMemberPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}
