<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /** The new password cannot be the temporary one. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (Hash::check((string) $this->input('password'), $this->user()->password)) {
                    $validator->errors()->add('password', __('Choose a password other than the temporary one.'));
                }
            },
        ];
    }
}
