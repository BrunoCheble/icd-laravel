<?php

namespace App\Modules\Music\Http\Requests;

use App\Modules\Music\Services\CreateSongBarsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SongBarsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Bars created on the layout page: their time signature and the song's tempo (optional).
     */
    public function rules(): array
    {
        return [
            'meter' => ['nullable', Rule::in(array_keys(CreateSongBarsService::METERS))],
            'bpm'   => ['nullable', 'numeric', 'between:30,300'],
        ];
    }
}
