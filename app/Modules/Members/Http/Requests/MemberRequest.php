<?php

namespace App\Modules\Members\Http\Requests;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // The spouse is a member or a name, never both: picking a member drops the name.
    public function prepareForValidation(): void
    {
        $name = trim((string) $this->spouse_name);
        $this->merge(['spouse_name' => $this->spouse_id || $name === '' ? null : $name]);
    }

    public function rules(): array
    {
        $memberId = $this->route('member')?->id;
        $relative = ['nullable', 'string', Rule::exists('members', 'id'), Rule::notIn(array_filter([(string) $memberId]))];

        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'document_number' => ['required', 'string', 'max:255', Rule::unique('members')->ignore($memberId)],
            'email' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'gender' => ['required', 'string', Rule::in(array_keys(Gender::options()))],
            'marital_status' => ['required', 'string', Rule::in(array_keys(MaritalStatus::options()))],
            'membership_status' => ['nullable', 'string', Rule::in(array_keys(MembershipStatus::options()))],
            'notes' => 'nullable|string',
            'baptism_date' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'date_joined' => 'nullable|date',
            'spouse_id' => $relative,
            'spouse_name' => 'nullable|string|max:255',
            'father_id' => $relative,
            'mother_id' => $relative,
        ];
    }
}
