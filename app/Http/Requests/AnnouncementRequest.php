<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\AnnouncementType;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ou adicione sua lógica de permissão aqui
    }

    public function rules(): array
    {
        return [
            'member_id'   => ['required', 'exists:members,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'link'        => ['nullable', 'string'],
            'contact'     => ['required', 'string'],
            'type'        => ['required', 'string', Rule::in(array_keys(AnnouncementType::options()))],
            'image_path'  => ['nullable', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'is_approved' => ['sometimes', 'boolean'],
            'expires_at'  => ['nullable', 'date'],
        ];
    }
}
