<?php

namespace App\Modules\Gallery\Http\Requests;

use App\Modules\Gallery\Models\GalleryEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published'), 'kind' => $this->input('kind', GalleryEvent::EVENT)]);
    }

    /** An event needs its date; a space has none. */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in([GalleryEvent::EVENT, GalleryEvent::SPACE])],
            'name' => ['required', 'string', 'max:120'],
            'event_date' => ['nullable', 'required_if:kind,' . GalleryEvent::EVENT, 'date'],
            'is_published' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('name'), 'event_date' => __('date')];
    }
}
