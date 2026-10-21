<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => trim((string) $this->input('role')) ?: null,
            'active' => $this->boolean('active'),
            'sort_order' => $this->input('sort_order', 0) ?: 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'role' => ['nullable', 'string', 'max:80'],
            'active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
            // The biography's JSON (see MinistryBio), from the editor.
            'bio' => ['nullable', 'string', 'max:100000', 'json'],
        ];
    }

    public function attributes(): array
    {
        return ['role' => __('role'), 'sort_order' => __('order'), 'bio' => __('biography')];
    }
}
