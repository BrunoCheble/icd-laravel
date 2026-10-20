<?php

namespace App\Modules\Announcements\Http\Requests;

use App\Enums\AnnouncementType;
use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An announcement sent by a member from the public page (it waits for approval). The member is found by the NIF.
 */
class PublicAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'member_document' => preg_replace('/\D+/', '', (string) $this->input('member_document')),
            'contact' => preg_replace('/\s+/', '', (string) $this->input('contact')),
        ]);
    }

    public function rules(): array
    {
        return [
            'member_document' => ['required', 'string', 'exists:members,document_number'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string', 'max:5000'],
            'link'            => ['nullable', 'string', 'max:255'],
            'contact'         => ['required', 'string', 'max:30'],
            'type'            => ['required', 'string', Rule::in(array_keys(AnnouncementType::options()))],
            'image_path'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            // Left empty by people; bots fill every field.
            'website'         => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_document.exists' => __('You need to be a member to announce. If you are, check the NIF or talk to us.'),
        ];
    }

    /**
     * The member of the NIF given.
     */
    public function member(): ?Member
    {
        return Member::where('document_number', $this->input('member_document'))->first();
    }
}
