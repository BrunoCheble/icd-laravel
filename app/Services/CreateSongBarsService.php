<?php

namespace App\Services;

use App\Models\Song;

class CreateSongBarsService
{
    /**
     * Gives the chord map durations, so it can be edited as a grid of bars: each chord without a duration gets one
     * bar of `$beatsPerBar` beats, to be adjusted afterwards. Blocks that already have durations keep them.
     */
    public function execute(Song $song, int $beatsPerBar = 4): Song
    {
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];

        $song->structure = array_map(function ($block) use ($beatsPerBar) {
            if (! is_array($block) || ($block['jump'] ?? null) === 'start') {
                return $block;
            }
            $chords = UpdateSongLayoutService::chordSequence([$block]);
            if (is_array($block['durations'] ?? null) && count($block['durations']) === count($chords)) {
                return $block;
            }
            $block['durations'] = array_fill(0, count($chords), $beatsPerBar);
            $block['beats_per_bar'] = $beatsPerBar;

            return $block;
        }, $structure);
        $song->save();

        return $song;
    }
}
