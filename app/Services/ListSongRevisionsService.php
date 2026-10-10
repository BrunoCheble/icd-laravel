<?php

namespace App\Services;

use App\Models\Song;

class ListSongRevisionsService
{
    // Where a change came from, by the route that saved it.
    private const SOURCES = [
        'songs.update'              => 'Song form',
        'songs.layout.update'       => 'Layout',
        'songs.bars.update'         => 'Layout (bars)',
        'songs.bars.create'         => 'Bars created',
        'songs.sheet-section.update' => 'Lyrics of the chord sheet',
        'study.songs.map'           => 'Study app (icd-chords)',
        'songs.history.restore'     => 'Restored from the history',
    ];

    /**
     * The song's revisions, newest first, each with what the change saved after it altered (map, chord sheet, key,
     * tempo), a summary of what it holds and where the change came from.
     */
    public function execute(Song $song): array
    {
        $revisions = $song->revisions()->with('user')->get();
        $current = [
            'structure'   => json_decode(json_encode($song->structure ?? []), true),
            'chord_sheet' => $song->chord_sheet,
            'musical_key' => $song->musical_key,
            'bpm'         => $song->bpm,
        ];

        $after = $current;
        return $revisions->map(function ($revision) use (&$after) {
            $state = [
                'structure'   => $revision->structure,
                'chord_sheet' => $revision->chord_sheet,
                'musical_key' => $revision->musical_key,
                'bpm'         => $revision->bpm,
            ];
            $changed = array_keys(array_filter($state, fn ($value, $field) => $value != $after[$field], ARRAY_FILTER_USE_BOTH));
            $after = $state;
            $blocks = array_values(array_filter($state['structure'] ?? [], 'is_array'));

            return [
                'revision' => $revision,
                'source'   => __(self::SOURCES[$revision->source] ?? 'Other'),
                'changed'  => $changed,
                'blocks'   => $blocks,
                'chords'   => count(UpdateSongLayoutService::chordSequence($blocks)),
                'sections' => $state['chord_sheet']['sections'] ?? [],
            ];
        })->all();
    }
}
