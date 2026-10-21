<?php

namespace App\Modules\Music\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReadScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // MusicXML: ".mxl" (zipped) or ".musicxml" / ".xml".
            'score' => ['required', 'file', 'max:10240', 'extensions:mxl,musicxml,xml'],
        ];
    }

    public function messages(): array
    {
        return ['score.extensions' => __('Choose a MusicXML score (.mxl, .musicxml or .xml).')];
    }
}
