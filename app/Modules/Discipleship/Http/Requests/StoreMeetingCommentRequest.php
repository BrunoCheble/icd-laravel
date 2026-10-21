<?php

namespace App\Modules\Discipleship\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:5000']];
    }

    public function attributes(): array
    {
        return ['body' => __('comment')];
    }
}
