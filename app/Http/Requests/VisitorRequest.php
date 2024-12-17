<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'gender' => 'required|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'group' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'invited_by' => 'nullable|string|max:255',
            'observation' => 'nullable|string',
            'created_by' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The name is required.',
            'gender.required' => 'The gender is required.',
            'created_by.required' => 'The creator is required.',
        ];
    }
}
