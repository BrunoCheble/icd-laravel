<?php

namespace App\Modules\Dashboard\Http\Requests;

use App\Modules\Dashboard\Models\SentMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SentMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys(SentMessage::RECIPIENTS))],
            'recipient_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
