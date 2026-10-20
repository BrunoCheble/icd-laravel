<?php

namespace App\Modules\Announcements\Http\Requests;

use App\Modules\Members\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A comment about an announcement, sent from the site by a member (found by the NIF). It waits for approval.
 */
class PublicCommentRequest extends FormRequest
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
            'body'            => ['required', 'string', 'min:3', 'max:600'],
            // Left empty by people; bots fill every field.
            'website'         => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_document.required' => __('Write your NIF.'),
            'member_document.exists' => __('Only members can comment. If you are one, check the NIF or talk to us.'),
            'body.required' => __('Write your comment.'),
            'body.min' => __('The comment is too short.'),
            'body.max' => __('The comment can have up to 600 characters.'),
        ];
    }

    public function attributes(): array
    {
        return ['member_document' => 'NIF', 'body' => mb_strtolower(__('Comment'))];
    }

    public function member(): ?Member
    {
        return Member::where('document_number', $this->input('member_document'))->first();
    }
}
