<?php

namespace App\Modules\Prayers\Http\Requests;

use App\Modules\Prayers\Services\ListPrayersService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPrayersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'period' => ['nullable', Rule::in(ListPrayersService::PERIODS)],
        ];
    }
}
