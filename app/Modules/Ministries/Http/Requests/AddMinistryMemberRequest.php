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
        $this->merge(['role' => trim((string) $this->input('role')) ?: null]);
    }

    public function rules(): array
    {
        $ministry = $this->route('ministry');

        return [
            'member_id' => ['required', 'integer', 'exists:members,id', Rule::unique('ministry_members', 'member_id')->where('ministry_id', $ministry->id)],
            'role' => ['nullable', 'string', 'max:80'],
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
