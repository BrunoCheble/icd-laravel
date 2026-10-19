<?php

namespace App\Modules\Visitors\Http\Requests;

use App\Enums\Gender;
use App\Enums\VisitorGroup;
use App\Helpers\NameHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A visitor, from the admin form or the site's sign-up form (SiteController::registerVisitor).
 */
class VisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => NameHelper::normalizeName($this->name),
            'phone_number' => str_replace(' ', '', (string) $this->phone_number) ?: null,
            'city' => NameHelper::normalizeName($this->city),
            'created_by' => NameHelper::normalizeName($this->created_by),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(array_keys(Gender::options()))],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'group' => ['nullable', Rule::in(array_keys(VisitorGroup::options()))],
            'city' => ['nullable', 'string', 'max:255'],
            'invited_by' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:5000'],
            'created_by' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name is required.',
            'gender.required' => 'The gender is required.',
            'created_by.required' => 'The creator is required.',
        ];
    }
}
