<?php

namespace App\Modules\Users\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => trim((string) $this->input('email')) ?: null,
            'phone_number' => User::normalizePhone($this->input('phone_number')),
            'member_id' => $this->input('member_id') ?: null,
            'is_admin' => $this->boolean('is_admin'),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            // Signing in with either.
            'email' => ['nullable', 'required_without:phone_number', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone_number' => ['nullable', 'required_without:email', 'digits_between:9,15', Rule::unique('users', 'phone_number')->ignore($user)],
            'member_id' => ['nullable', 'integer', 'exists:members,id', Rule::unique('users', 'member_id')->ignore($user)],
            'is_admin' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => __('Fill in the email or the mobile number (to sign in with).'),
            'phone_number.required_without' => __('Fill in the email or the mobile number (to sign in with).'),
            'phone_number.digits_between' => __('The mobile number is not valid.'),
            'member_id.unique' => __('This member already has a user.'),
        ];
    }

    public function attributes(): array
    {
        return ['phone_number' => __('mobile number'), 'name' => __('name'), 'email' => __('email')];
    }
}
