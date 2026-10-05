<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSongToSetlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'song_id' => ['required', 'integer', 'exists:songs,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'song_id' => __('Song'),
        ];
    }
}
