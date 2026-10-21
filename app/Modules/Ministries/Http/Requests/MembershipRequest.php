<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ministry_role_id' => $this->input('ministry_role_id') ?: null,
            'active' => $this->boolean('active'),
            'sort_order' => $this->input('sort_order', 0) ?: 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'ministry_role_id' => ['nullable', 'integer', Rule::exists('ministry_roles', 'id')->where('ministry_id', $this->route('membership')->ministry_id)],
            'active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
            // The biography's JSON (see MinistryBio), from the editor.
            'bio' => ['nullable', 'string', 'max:100000', 'json'],
        ];
    }

    public function attributes(): array
    {
        return ['sort_order' => __('order'), 'bio' => __('biography')];
    }
}
