<?php

namespace App\Modules\Discipleship\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferDiscipleshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discipleship = $this->route('discipleship');

        return [
            'mentor_id' => ['required', 'integer', 'exists:members,id', 'not_in:' . $discipleship->mentor_id . ',' . $discipleship->disciple_id],
        ];
    }

    public function messages(): array
    {
        return [
            'mentor_id.required' => __('Choose the new mentor.'),
            'mentor_id.not_in' => __('Choose another member as the new mentor.'),
        ];
    }
}
