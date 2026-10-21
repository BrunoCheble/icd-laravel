<?php

namespace App\Modules\Discipleship\Http\Requests;

use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([DiscipleshipMeeting::SCHEDULED, DiscipleshipMeeting::HELD, DiscipleshipMeeting::NOT_HELD])],
            'reason' => ['nullable', 'required_if:status,' . DiscipleshipMeeting::NOT_HELD, Rule::in(array_keys(DiscipleshipMeeting::REASONS))],
            'reason_note' => ['nullable', 'string', 'max:255'],
            'attended' => ['nullable', 'array'],
            'attended.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return ['reason.required_if' => __('Say why the meeting did not take place.')];
    }
}
