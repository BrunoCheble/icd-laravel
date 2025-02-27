<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Helpers\DateHelper;
use App\Helpers\NameHelper;
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

    public function prepareForValidation()
    {
        $this->merge([
            'email' => strtolower($this->email),
            'date_of_birth' => DateHelper::formatStringToDate($this->birthdate),
            'address' => NameHelper::normalizeName($this->address) . ' ' . $this->address_number,
            'gender' => Gender::getIndexByValue($this->gender),
            'marital_status' => MaritalStatus::getIndexByValue($this->marital_status),
        ]);
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
            'birthdate' => 'required|string',
            'document_number' => [
                'required',
                'string',
                'lowercase',
                Rule::unique(Member::class)
            ],
			'email' => 'required|string',
			'phone_number' => 'required|string',
			'address' => 'required|string',
			'address_number' => 'required|string',
			'city' => 'required|string',
			'zip_code' => 'required|string',
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
