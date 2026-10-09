<?php

namespace App\Http\Requests;

use App\Enums\MusicalKey;
use App\Services\FindSongBySourceService;
use App\Services\SaveSongAudioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SongRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'artist'      => ['required', 'string', 'max:255'],
            'musical_key' => ['nullable', 'string', 'max:20', Rule::in($this->allowedKeys())],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            // Audio file played instead of the YouTube video (and kept for offline use): up to 30 MB.
            'audio'        => ['nullable', 'file', 'mimes:mp3,mpga,m4a,mp4,aac,ogg,oga,wav', 'max:' . SaveSongAudioService::MAX_MEGABYTES * 1024],
            'remove_audio' => ['nullable', 'boolean'],
            'source_url'  => ['nullable', 'url', 'max:500', $this->uniqueSourceRule()],

            'video_lesson'              => ['nullable', 'array'],
            'video_lesson.*'            => ['required', 'array'],
            'video_lesson.*.instrument' => ['required', 'string', 'max:50'],
            'video_lesson.*.title'      => ['required', 'string', 'max:255'],
            'video_lesson.*.link'       => ['required', 'url', 'max:500'],

            'chord_sheet' => ['nullable', 'string', $this->chordSheetRule()],

            'structure' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                $decoded = json_decode($value);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail(__('The :attribute field contains invalid JSON: :error', ['error' => json_last_error_msg()]));
                } elseif ($decoded === null) {
                    $fail(__('The :attribute field cannot be null.'));
                }
            }],
        ];
    }

    /**
     * The same chord sheet address cannot be used by two songs (see FindSongBySourceService for how they compare).
     */
    private function uniqueSourceRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $existing = app(FindSongBySourceService::class)->execute($value, $this->route('song')?->id);

            if ($existing) {
                $fail(__('There is already a song with this source: ":title" (:artist).', ['title' => $existing->title, 'artist' => $existing->artist]));
            }
        };
    }

    /**
     * Keys from the selector, plus the song's current key so legacy values are not rejected on update.
     */
    private function allowedKeys(): array
    {
        $currentKey = $this->route('song')?->musical_key;

        return array_values(array_filter([...MusicalKey::values(), $currentKey]));
    }

    /**
     * Optional chord sheet: JSON text with a "sections" list, each with its "lines".
     */
    private function chordSheetRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $fail(__('The :attribute field contains invalid JSON: :error', ['error' => json_last_error_msg()]));
            } elseif (! is_array($decoded['sections'] ?? null) || collect($decoded['sections'])->contains(fn ($section) => ! is_array($section['lines'] ?? null))) {
                $fail(__('The :attribute must have a "sections" list, each with its "lines".'));
            }
        };
    }

    public function attributes(): array
    {
        return [
            'title'                     => __('Title'),
            'artist'                    => __('Artist'),
            'musical_key'               => __('Key'),
            'youtube_url'               => __('YouTube'),
            'source_url'                => __('Source'),
            'video_lesson'              => __('Video Lessons'),
            'video_lesson.*.instrument' => __('Instrument'),
            'video_lesson.*.title'      => __('Title'),
            'video_lesson.*.link'       => __('Link'),
            'structure'                 => __('Structure'),
            'chord_sheet'               => __('Chord sheet'),
        ];
    }
}
