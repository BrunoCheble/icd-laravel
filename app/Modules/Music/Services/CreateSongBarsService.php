<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Song;

class CreateSongBarsService
{
    // Time signatures offered and the beats counted in each bar (in 6/8 the eighth notes are counted).
    public const METERS = ['4/4' => 4, '3/4' => 3, '6/8' => 6];

    // Tempo given to a song with bars that has none yet.
    public const DEFAULT_BPM = 70;

    /**
     * The time signature of a song's map, from the beats of its bars ("4/4" when it has none yet).
     */
    public static function meterOf(mixed $structure): string
    {
        foreach (json_decode(json_encode($structure ?? []), true) ?? [] as $block) {
            $beats = is_array($block) ? (int) ($block['beats_per_bar'] ?? 0) : 0;
            if ($beats > 0) {
                return array_search($beats, self::METERS, true) ?: '4/4';
            }
        }

        return '4/4';
    }

    /**
     * Gives the chord map durations, so it can be edited as a grid of bars: the chords of each block, in order, one
     * bar each (of `$beatsPerBar` beats), to be adjusted afterwards. Blocks that already have durations keep them.
     * A song with bars and no tempo gets DEFAULT_BPM. Does nothing (nor saves) when there is nothing to change.
     */
    public function execute(Song $song, ?int $beatsPerBar = null): Song
    {
        $beatsPerBar ??= self::METERS[self::meterOf($song->structure)];
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $changed = false;

        $structure = array_map(function ($block) use ($beatsPerBar, &$changed) {
            if (! $this->needsBars($block)) {
                return $block;
            }
            $block['durations'] = array_fill(0, count(UpdateSongLayoutService::chordSequence([$block])), $beatsPerBar);
            $block['beats_per_bar'] = $beatsPerBar;
            $changed = true;

            return $block;
        }, $structure);
        if ($changed) {
            $song->structure = $structure;
        }
        $hasBars = collect($structure)->contains(fn ($block) => is_array($block) && is_array($block['durations'] ?? null));
        if ($hasBars && ! $song->bpm) {
            $song->bpm = self::DEFAULT_BPM;
            $changed = true;
        }
        if ($changed) {
            $song->save();
        }

        return $song;
    }

    /**
     * Sets the time signature of every block with bars. The durations (in beats) stay as they are, so each chord keeps
     * the beat it falls on and only the bar lines move: 3/4 "C / / | D / /" becomes 4/4 "C / / D | / /".
     */
    public function changeMeter(Song $song, int $beatsPerBar): Song
    {
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $changed = false;

        $structure = array_map(function ($block) use ($beatsPerBar, &$changed) {
            if (! is_array($block) || ! is_array($block['durations'] ?? null) || ($block['beats_per_bar'] ?? null) === $beatsPerBar) {
                return $block;
            }
            $block['beats_per_bar'] = $beatsPerBar;
            $changed = true;

            return $block;
        }, $structure);
        if ($changed) {
            $song->structure = $structure;
            $song->save();
        }

        return $song;
    }

    /**
     * How many blocks of the map would get bars (blocks with chords and no durations matching them).
     */
    public function missingBlocks(Song $song): int
    {
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];

        return count(array_filter($structure, fn ($block) => $this->needsBars($block)));
    }

    private function needsBars(mixed $block): bool
    {
        if (! is_array($block) || ($block['jump'] ?? null) === 'start') {
            return false;
        }
        $chords = UpdateSongLayoutService::chordSequence([$block]);

        return $chords !== [] && ! (is_array($block['durations'] ?? null) && count($block['durations']) === count($chords));
    }
}
