<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinistryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_pastoral' => $this->boolean('is_pastoral')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ministries', 'name')->ignore($this->route('ministry'))],
            'is_pastoral' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('name')];
    }
}
