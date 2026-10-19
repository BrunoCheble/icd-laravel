<?php

namespace App\Modules\Members\Http\Requests;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Modules\Members\Services\ListMembersService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'membership_status' => ['nullable', Rule::in(array_keys(MembershipStatus::options()))],
            'gender' => ['nullable', Rule::in(array_keys(Gender::options()))],
            'marital_status' => ['nullable', Rule::in(array_keys(MaritalStatus::options()))],
            'sort' => ['nullable', Rule::in(array_keys(ListMembersService::SORTS))],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
