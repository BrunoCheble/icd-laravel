<?php

namespace App\Modules\Announcements\Http\Requests;

use App\Modules\Announcements\Support\AnnouncementCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id'   => ['required', 'exists:members,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'link'        => ['nullable', 'string', 'max:255'],
            'contact'     => ['required', 'string', 'max:255'],
            'type'        => ['required', 'string', Rule::in(AnnouncementCategories::keys())],
            // A résumé's PDF (the picture is the category's banner).
            'image_path'  => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'is_approved' => ['sometimes', 'boolean'],
            'expires_at'  => ['nullable', 'date'],
        ] + AnnouncementCategories::rules($this->input('type'));
    }

    public function attributes(): array
    {
        return AnnouncementCategories::attributes($this->input('type'));
    }
}
