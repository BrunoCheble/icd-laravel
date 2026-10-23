<?php

namespace App\Modules\Gallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** One photo sent to an event (the page sends them one by one, already made smaller by the browser). */
class GalleryPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:20480'],
        ];
    }

    public function attributes(): array
    {
        return ['photo' => __('photo')];
    }
}
