<?php

namespace App\Modules\Announcements\Http\Requests;

use App\Enums\AnnouncementType;
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
            'type'        => ['required', 'string', Rule::in(array_keys(AnnouncementType::options()))],
            'image_path'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'is_approved' => ['sometimes', 'boolean'],
            'expires_at'  => ['nullable', 'date'],
        ];
    }
}
