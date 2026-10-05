<?php

namespace App\Http\Requests;

use App\Enums\MusicalKey;
use App\Models\Song;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:255'],
            'event_date'           => ['nullable', 'date'],
            'songs'                => ['nullable', 'array'],
            'songs.*'              => ['required', 'array'],
            'songs.*.song_id'      => ['required', 'integer', 'distinct', 'exists:songs,id'],
            'songs.*.minister_name' => ['nullable', 'string', 'max:255'],
            'songs.*.musical_key'  => ['nullable', 'string', 'max:20', Rule::in($this->allowedKeys())],
        ];
    }

    /**
     * Keys from the selector, plus keys already stored for these songs so legacy values are not rejected.
     */
    private function allowedKeys(): array
    {
        $songIds = collect($this->input('songs', []))->pluck('song_id')->filter()->all();
        $setlist = $this->route('setlist');

        return array_values(array_filter(array_unique([
            ...MusicalKey::values(),
            ...Song::whereIn('id', $songIds)->pluck('musical_key')->all(),
            ...($setlist ? $setlist->songs()->pluck('setlist_songs.musical_key')->all() : []),
        ])));
    }

    public function attributes(): array
    {
        return [
            'title'                 => __('Title'),
            'event_date'            => __('Event Date'),
            'songs'                 => __('Songs'),
            'songs.*.song_id'       => __('Song'),
            'songs.*.minister_name' => __('Minister'),
            'songs.*.musical_key'   => __('Key'),
        ];
    }
}
