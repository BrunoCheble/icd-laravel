<?php

namespace App\Modules\Visitors\Http\Requests;

use App\Enums\Gender;
use App\Enums\VisitorGroup;
use App\Enums\VisitorStatus;
use App\Modules\Visitors\Services\ListVisitorsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListVisitorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(VisitorStatus::options()))],
            'gender' => ['nullable', Rule::in(array_keys(Gender::options()))],
            'group' => ['nullable', Rule::in(array_keys(VisitorGroup::options()))],
            'sort' => ['nullable', Rule::in(array_keys(ListVisitorsService::SORTS))],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
