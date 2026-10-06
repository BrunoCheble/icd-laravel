<?php

namespace App\Http\Requests;

use App\Services\ListSongsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSongsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reviewed'  => ['nullable', 'in:yes,no'],
            'times'     => ['nullable', 'in:yes,no'],
            'youtube'   => ['nullable', 'in:yes,no'],
            'search'    => ['nullable', 'string', 'max:100'],
            'sort'      => ['nullable', Rule::in(array_keys(ListSongsService::SORTS))],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
