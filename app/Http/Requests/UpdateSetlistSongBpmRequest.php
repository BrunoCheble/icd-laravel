<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSetlistSongBpmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // null: back to the song's own BPM.
            'bpm' => ['present', 'nullable', 'numeric', 'between:30,300'],
        ];
    }

    public function attributes(): array
    {
        return ['bpm' => 'BPM'];
    }
}
