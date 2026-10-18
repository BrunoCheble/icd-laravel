<?php

namespace App\Modules\Music\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContributeLessonLoopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A stretch of a lesson's video or audio (seconds), with a name, saved by a musician on the public page.
     */
    public function rules(): array
    {
        return [
            'start' => ['required', 'numeric', 'min:0', 'max:36000'],
            'end'   => ['required', 'numeric', 'max:36000', 'gte:start'],
            'label' => ['nullable', 'string', 'max:60'],
        ];
    }
}
