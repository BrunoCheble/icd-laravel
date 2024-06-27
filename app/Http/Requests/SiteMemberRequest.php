<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SiteMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'full_name' => 'required|string',
            'birthdate' => 'required|nullable|date|before:today',
            'document_number' => [
                'required',
                'string',
                'lowercase',
                Rule::unique(Member::class)
            ],
			'email' => 'nullable|string',
			'phone_number' => 'nullable|string',
			'address' => 'nullable|string',
			'address_number' => 'nullable|string',
			'city' => 'nullable|string',
			'zip_code' => 'nullable|string',
			'gender' => 'required|string',
			'marital_status' => 'required|string',
			//'membership_status' => 'nullable|string',
            //'baptism_date' => 'nullable|date',
            //'date_of_birth' => 'nullable|date',
            //'date_joined' => 'nullable|date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'birthdate.before:today' => 'The birthdate cannot be a future date.',
        ];
    }
}
