<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinistryRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $ministry = $role?->ministry_id ?? $this->route('ministry')?->id;

        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('ministry_roles', 'name')->where('ministry_id', $ministry)->ignore($role)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => __('This ministry already has this role.')];
    }

    public function attributes(): array
    {
        return ['name' => __('role')];
    }
}
