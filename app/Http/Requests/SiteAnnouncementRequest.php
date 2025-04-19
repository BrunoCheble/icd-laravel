<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\AnnouncementType;
use App\Models\Member;

class SiteAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ou lógica de permissão, se precisar
    }

    public function rules(): array
    {
        return [
            'member_document' => ['required', 'string', 'exists:members,document_number'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string'],
            'link'            => ['nullable', 'string'],
            'contact'         => ['required', 'string'],
            'type'            => ['required', 'string', Rule::in(array_keys(AnnouncementType::options()))],
            'image_path'      => ['nullable', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * Retorna o membro relacionado ao document informado
     */
    public function member(): ?Member
    {
        return Member::where('document_number', $this->input('member_document'))->first();
    }
}
