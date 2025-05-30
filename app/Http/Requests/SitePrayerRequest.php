<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SitePrayerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'request' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The name is required.',
        ];
    }
}
