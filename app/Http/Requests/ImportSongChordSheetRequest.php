<?php

namespace App\Http\Requests;

use App\Enums\MusicalKey;
use Illuminate\Foundation\Http\FormRequest;

class ImportSongChordSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sheet'   => ['required', 'string', 'max:300000'],
            'key'     => ['nullable', 'string', 'max:20'],
            'youtube' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * The key only when it is one the songs use (sharps/flats as in the selector).
     */
    public function key(): ?string
    {
        $key = $this->validated('key');
        $enharmonic = ['A#' => 'Bb', 'D#' => 'Eb', 'G#' => 'Ab', 'C#' => 'Db', 'Gb' => 'F#', 'A#m' => 'Bbm', 'D#m' => 'Ebm', 'Dbm' => 'C#m', 'Gbm' => 'F#m', 'Abm' => 'G#m'];
        $key = $enharmonic[$key] ?? $key;

        return $key && in_array($key, [...MusicalKey::MAJOR, ...MusicalKey::MINOR], true) ? $key : null;
    }
}
