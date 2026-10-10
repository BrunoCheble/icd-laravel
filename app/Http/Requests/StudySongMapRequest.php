<?php

namespace App\Http\Requests;

use App\Services\UpdateChordSheetChordsService;
use App\Services\UpdateSongLayoutService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Chord map sent by the study app: the blocks of the structure (as the layout page saves them) and the
 * `updated_at` of the song when it was read.
 */
class StudySongMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'updated_at'         => ['required', 'date'],
            'bpm'                => ['nullable', 'numeric', 'between:30,300'],
            'musical_key'        => ['nullable', 'string', 'max:20'],
            // Only the map: the chord sheet with lyrics is left as it is (automatic saves of the study app).
            'keep_sheet'             => ['nullable', 'boolean'],
            // Chord sheet rewritten by the study app to follow the map (same lyrics, the map's chords).
            'chord_sheet'            => ['nullable', 'array', 'list'],
            'chord_sheet.*'          => ['array'],
            'chord_sheet.*.section'  => ['nullable', 'string', 'max:100'],
            'chord_sheet.*.label'    => ['nullable', 'string', 'max:100'],
            'chord_sheet.*.lines'    => ['present', 'array', 'list'],
            'chord_sheet.*.lines.*'  => ['nullable', 'string'],
            'blocks'             => ['required', 'array', 'list'],
            'blocks.*'           => ['required', 'array'],
            'blocks.*.section'   => ['required', 'string', 'max:100'],
            'blocks.*.chords'    => ['present', 'array', 'list'],
            'blocks.*.chords.*'  => ['string', 'max:30'],
            'blocks.*.start'     => ['nullable'],
            'blocks.*.lyrics'    => ['nullable', 'string'],
            'blocks.*.anchor'    => ['nullable', 'string', 'max:255'],
            'blocks.*.passing'   => ['nullable', 'array'],
            'blocks.*.passing.*' => ['integer', 'min:0'],
            'blocks.*.jump'      => ['nullable', 'in:start'],
            // Duration of each chord in beats (multiples of half a beat), and beats per bar.
            'blocks.*.durations'     => ['nullable', 'array', 'list'],
            'blocks.*.durations.*'   => ['numeric', 'gt:0', 'multiple_of:' . UpdateSongLayoutService::DURATION_STEP],
            'blocks.*.beats_per_bar' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    /**
     * Each chord must be a chord name (line breaks "|" apart), as on the layout page, with one duration each when
     * durations are sent.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $invalid = collect(UpdateSongLayoutService::chordSequence($this->input('blocks')))
                    ->first(fn (string $chord) => trim($chord) === '' || preg_match('/[\s\[\]|]/u', $chord));
                if ($invalid !== null) {
                    $validator->errors()->add('blocks', __('The layout is invalid.'));
                }
                // A chord sheet sent along has exactly the map's chords.
                if (is_array($this->input('chord_sheet'))
                    && UpdateChordSheetChordsService::sheetChords($this->input('chord_sheet')) !== UpdateSongLayoutService::chordSequence($this->input('blocks'))) {
                    $validator->errors()->add('chord_sheet', __('The chord sheet must have exactly the chords of the map.'));
                }
                // One duration per chord.
                foreach ($this->input('blocks') as $index => $block) {
                    $durations = $block['durations'] ?? null;
                    if (is_array($durations) && count($durations) !== count(UpdateSongLayoutService::chordSequence([$block]))) {
                        $validator->errors()->add("blocks.{$index}.durations", __('Each chord needs one duration.'));
                    }
                }
            },
        ];
    }
}
