<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMinistryMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ministry_role_id' => $this->input('ministry_role_id') ?: null]);
    }

    public function rules(): array
    {
        $ministry = $this->route('ministry');

        return [
            'member_id' => ['required', 'integer', 'exists:members,id', Rule::unique('ministry_members', 'member_id')->where('ministry_id', $ministry->id)],
            'ministry_role_id' => ['nullable', 'integer', Rule::exists('ministry_roles', 'id')->where('ministry_id', $ministry->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => __('Choose the member.'),
            'member_id.unique' => __('This member is already in this ministry.'),
        ];
    }
}
