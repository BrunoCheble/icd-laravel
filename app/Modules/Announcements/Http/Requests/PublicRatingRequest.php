<?php

namespace App\Modules\Announcements\Http\Requests;

use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A member's stars (1 to 5) for an announcement, sent from the site with the NIF; one per member.
 */
class PublicRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // The site is in Portuguese (the messages too).
        app()->setLocale('pt');
        $this->merge(['member_document' => preg_replace('/\D+/', '', (string) $this->input('member_document'))]);
    }

    public function rules(): array
    {
        return [
            'member_document' => ['required', 'string', 'exists:members,document_number'],
            'stars'           => ['required', 'integer', 'between:1,5'],
            // Left empty by people; bots fill every field.
            'website'         => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_document.required' => __('Write your NIF.'),
            'member_document.exists' => __('Only members can rate. If you are one, check the NIF or talk to us.'),
            'stars.*' => __('Choose from 1 to 5 stars.'),
        ];
    }

    public function member(): ?Member
    {
        return Member::where('document_number', $this->input('member_document'))->first();
    }
}
