<?php

namespace App\Modules\Members\Http\Requests;

use App\Modules\Members\Models\Member;
use App\Modules\Members\Services\GetMemberPendingInformationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * What a pending member fills in on the "complete your registration" page: only the fields still missing are taken.
 */
class CompleteRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // The page's language: Portuguese, or English when asked ("lang=en", in the link or sent with the form).
    public const LOCALES = ['pt', 'en'];

    public static function locale(\Illuminate\Http\Request $request): string
    {
        return in_array($request->input('lang'), self::LOCALES, true) ? $request->input('lang') : 'pt';
    }

    public function prepareForValidation(): void
    {
        app()->setLocale(self::locale($this));
        $phone = preg_replace('/\D+/', '', (string) $this->phone_number);
        $phone = preg_replace('/^(00)?351(?=\d{9}$)/', '', $phone);
        $zip = preg_replace('/\D+/', '', (string) $this->zip_code);

        $this->merge([
            'phone_number' => $phone ?: null,
            'zip_code' => strlen($zip) === 7 ? substr($zip, 0, 4) . '-' . substr($zip, 4) : ($this->zip_code ?: null),
            'document_number' => preg_replace('/\D+/', '', (string) $this->document_number) ?: null,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'spouse' => ['spouse_name' => ['nullable', 'string', 'max:255']],
            'date_joined' => ['date_joined' => ['nullable', 'date', 'after:1900-01-01', 'before_or_equal:today']],
            'email' => ['email' => ['nullable', 'email', 'max:255']],
            'phone_number' => ['phone_number' => ['nullable', 'regex:/^9\d{8}$/']],
            'address' => [
                'address' => ['nullable', 'string', 'max:255'],
                'zip_code' => ['nullable', 'regex:/^\d{4}-\d{3}$/'],
                'city' => ['nullable', 'string', 'max:255'],
            ],
            'date_of_birth' => ['date_of_birth' => ['nullable', 'date', 'after:1900-01-01', 'before:today']],
            'document_number' => ['document_number' => ['nullable', 'digits:9', Rule::unique(Member::class, 'document_number')]],
        ];

        return collect($this->pendingFields())->flatMap(fn ($field) => $rules[$field])->all();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (collect($validator->getData())->only(array_keys($this->rules()))->filter()->isEmpty()) {
                $validator->errors()->add('form', __('Fill in at least one field.'));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'spouse_name' => __('Spouse'),
            'date_joined' => __('Wedding Date'),
            'email' => __('Email'),
            'phone_number' => __('Phone Number'),
            'address' => __('Address'),
            'zip_code' => __('Zip Code'),
            'city' => __('City'),
            'date_of_birth' => __('Date of Birth'),
            'document_number' => 'NIF',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => __('Write the 9 digits of the mobile number.'),
            'zip_code.regex' => __('Write the zip code as 1234-567.'),
        ];
    }

    /**
     * The member of the link (the "signed" middleware checked it before this request is built).
     */
    public function member(): Member
    {
        return Member::findOrFail($this->route('member'));
    }

    private function pendingFields(): array
    {
        return app(GetMemberPendingInformationService::class)->fields($this->member());
    }
}
