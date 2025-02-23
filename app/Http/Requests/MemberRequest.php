<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
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
        $memberId = $this->route('member')?->id;
        return [
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'document_number' => [
                'required',
                'string',
                Rule::unique('members')->ignore($memberId),
            ],
            'email' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'gender' => 'required|string',
            'marital_status' => 'required|string',
            'membership_status' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|string',
            'baptism_date' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'date_joined' => 'nullable|date',
            'spouse_id' => 'nullable|string',
            'father_id' => 'nullable|string',
            'mother_id' => 'nullable|string',
        ];
    }
}
