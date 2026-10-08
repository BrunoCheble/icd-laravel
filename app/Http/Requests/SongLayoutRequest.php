<?php

namespace App\Http\Requests;

use App\Services\BuildStructureFromChordSheetService;
use App\Services\UpdateSongLayoutService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SongLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'structure'   => ['required', 'string', 'json'],
            // Sent only when blocks were removed from the chord sheet too.
            'chord_sheet' => ['nullable', 'string', 'json'],
        ];
    }

    /**
     * The layout page moves line breaks and block boundaries, removes, repeats and edits blocks and chords: each
     * chord must be a chord name, and a chord sheet sent along must have exactly the map's chords.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $blocks = $this->blocks();
                $valid = array_is_list($blocks) && collect($blocks)->every(fn ($block) => is_array($block)
                    && is_string($block['section'] ?? null) && trim($block['section']) !== ''
                    && is_array($block['chords'] ?? null) && collect($block['chords'])->every(fn ($chord) => is_string($chord)));

                if (! $valid) {
                    $validator->errors()->add('structure', __('The layout is invalid.'));
                    return;
                }

                $chords = UpdateSongLayoutService::chordSequence($blocks);
                $invalid = collect($chords)->first(fn (string $chord) => trim($chord) === '' || preg_match('/[\s\[\]|]/u', $chord));
                if ($invalid !== null) {
                    $validator->errors()->add('structure', __('The layout is invalid.'));
                    return;
                }

                if ($this->filled('chord_sheet')) {
                    $sections = $this->sheetSections();
                    $validSheet = is_array($sections) && array_is_list($sections) && collect($sections)->every(
                        fn ($section) => is_array($section) && is_array($section['lines'] ?? null)
                            && collect($section['lines'])->every(fn ($line) => is_string($line)),
                    );

                    if (! $validSheet || self::sheetChords($sections) !== $chords) {
                        $validator->errors()->add('structure', __('The chords of the song changed meanwhile. Reload the page and try again.'));
                    }
                }
            },
        ];
    }

    public function blocks(): array
    {
        return json_decode((string) $this->input('structure'), true) ?? [];
    }

    /**
     * Chord sheet sections sent along, or null when the chord sheet was not changed.
     */
    public function sheetSections(): ?array
    {
        if (! $this->filled('chord_sheet')) {
            return null;
        }

        return json_decode((string) $this->input('chord_sheet'), true)['sections'] ?? null;
    }

    private static function sheetChords(array $sections): array
    {
        return UpdateSongLayoutService::chordSequence(array_map(
            fn ($section) => ['chords' => BuildStructureFromChordSheetService::chords($section['lines'] ?? [])],
            $sections,
        ));
    }

    public function attributes(): array
    {
        return [
            'structure' => __('Structure'),
        ];
    }
}
