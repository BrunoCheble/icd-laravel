<?php

namespace App\Modules\Gallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'event_date' => ['required', 'date'],
            'is_published' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('name'), 'event_date' => __('date')];
    }
}
