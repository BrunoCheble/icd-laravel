<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SaveStudySongMapService
{
    public function __construct(
        private UpdateSongLayoutService $layout,
        private UpdateChordSheetChordsService $sheet,
    ) {
    }

    /**
     * Saves a chord map edited as bars (in the study app or on the layout page's grid). Refused (409) when the song changed since the app read it
     * (`$readAt`, the `updated_at` it got). The chord sheet gets the same chord changes when it had the map's chords;
     * otherwise only the map changes; the tempo (`$bpm`) is saved too when sent. Returns whether the chord sheet was
     * updated.
     */
    public function execute(Song $song, array $blocks, string $readAt, ?float $bpm = null, ?string $key = null, ?array $sheetSections = null, bool $keepSheet = false): bool
    {
        if (! $song->updated_at || ! $song->updated_at->equalTo(Carbon::parse($readAt))) {
            throw new ConflictHttpException(__('The song was changed meanwhile. Load it again before saving.'));
        }

        $oldChords = UpdateSongLayoutService::chordSequence(json_decode(json_encode($song->structure ?? []), true) ?? []);
        $newChords = UpdateSongLayoutService::chordSequence($blocks);
        $sections = $song->chord_sheet['sections'] ?? null;
        // A chord sheet sent along (rewritten to follow the map) replaces the one stored; otherwise the stored one
        // gets the same chord changes as the map, when it had the map's chords.
        // With `$keepSheet`, the chord sheet is not touched at all.
        $sheet = $keepSheet ? null : ($sheetSections ?? (is_array($sections) && $sections !== [] ? $this->sheet->execute($sections, $oldChords, $newChords) : null));

        if ($bpm !== null) {
            $song->bpm = round($bpm, 1);
        }
        $this->layout->execute($song, $blocks, $sheet, $key);

        return $sheet !== null && ($sheetSections !== null || $oldChords !== $newChords);
    }
}
