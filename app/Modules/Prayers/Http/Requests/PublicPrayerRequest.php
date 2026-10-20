<?php

namespace App\Modules\Prayers\Http\Requests;

use App\Helpers\NameHelper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A prayer request sent from the public page: the name, the request and, optionally, a mobile number.
 */
class PublicPrayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        app()->setLocale(\App\Modules\Prayers\Http\Controllers\PublicPrayerController::locale($this));
        // "+351 912 345 678", "00351912345678", "912-345-678" -> "912345678"; other countries keep their code.
        // input(): "request" is also the name of the form's data on a request object.
        $phone = preg_replace('/\D+/', '', (string) $this->input('phone_number'));
        $phone = preg_replace('/^00/', '', $phone);
        $phone = preg_replace('/^351(?=9\d{8}$)/', '', $phone);

        $this->merge([
            'name' => NameHelper::normalizeName((string) $this->input('name')),
            'request' => trim((string) $this->input('request')) ?: null,
            'phone_number' => $phone ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'request' => ['nullable', 'string', 'max:2000'],
            'phone_number' => ['nullable', 'regex:/^\d{9,15}$/'],
            // Left empty by people; bots fill every field.
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => __('Write the mobile number with its 9 digits (or with the country code).'),
        ];
    }
}
