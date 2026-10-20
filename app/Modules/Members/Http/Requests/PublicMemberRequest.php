<?php

namespace App\Modules\Members\Http\Requests;

use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Someone becoming a member from the church's site (waiting for the team to activate them). Gender and marital
 * status by their codes (M / F; S, M, D, W), the birth date as dd/mm/yyyy.
 */
class PublicMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // The site is in Portuguese (the messages too).
        app()->setLocale('pt');
        $this->merge([
            'document_number' => preg_replace('/\D+/', '', (string) $this->input('document_number')),
            'phone_number' => preg_replace('/\s+/', '', (string) $this->input('phone_number')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name'       => ['required', 'string', 'max:150', 'regex:/\S+\s+\S+/'],
            'gender'          => ['required', Rule::in(['M', 'F'])],
            'birthdate'       => ['required', 'date_format:d/m/Y', 'before:today', 'after:1900-01-01'],
            'document_number' => ['required', 'digits:9', Rule::unique(Member::class, 'document_number')],
            'marital_status'  => ['required', Rule::in(['S', 'M', 'D', 'W'])],
            'phone_number'    => ['required', 'string', 'min:9', 'max:20'],
            'email'           => ['required', 'email', 'max:150'],
            'zip_code'        => ['required', 'regex:/^\d{4}-\d{3}$/'],
            'address'         => ['required', 'string', 'max:150'],
            'address_number'  => ['required', 'string', 'max:20'],
            'city'            => ['required', 'string', 'max:80'],
            // Left empty by people; bots fill every field.
            'website'         => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.regex' => __('Write your first and last name.'),
            'document_number.unique' => __('This NIF is already registered: you are already a member (or waiting for the team).'),
            'document_number.digits' => __('The NIF has 9 digits.'),
            'zip_code.regex' => __('The postal code is like 1234-567.'),
            'birthdate.*' => __('Write the date of birth as dd/mm/yyyy.'),
            '*.required' => __('Fill in this field.'),
            'email.email' => __('Check the email.'),
        ];
    }
}
