<?php

namespace App\Services;

use App\Models\Song;
use App\Models\SongVersion;
use Illuminate\Validation\ValidationException;

class CreateSongVersionService
{
    /**
     * A version of the song's chord map for an instrument: a copy of the map as it is now (blocks, times and chords),
     * in the song's key; from then on it changes on its own. A bass version starts with the bass line: each chord
     * becomes the note the bass plays (see bassLine).
     */
    public function execute(Song $song, string $instrument): SongVersion
    {
        $instrument = trim(preg_replace('/\s+/u', ' ', $instrument));
        if ($song->versions()->where('instrument', $instrument)->exists()) {
            throw ValidationException::withMessages(['instrument' => __('This song already has a version for :instrument.', ['instrument' => $instrument])]);
        }

        return $song->versions()->create([
            'instrument'  => $instrument,
            'musical_key' => $song->musical_key,
            'structure'   => self::isBass($instrument)
                ? self::bassLine(json_decode(json_encode($song->structure ?? []), true) ?? [])
                : json_decode(json_encode($song->structure ?? []), true) ?? [],
        ]);
    }

    public static function isBass(string $instrument): bool
    {
        return (bool) preg_match('/baixo|bass/iu', $instrument);
    }

    /**
     * The note the bass plays for a chord: the note after the slash when it has one (G6/B -> B), otherwise its root,
     * without kind or tensions (Em7(9) -> E). Anything else stays as it is.
     */
    public static function bassNote(string $chord): string
    {
        if (! preg_match('/^([A-G][#b]?)[^\/]*(?:\/([A-G][#b]?))?$/u', trim($chord), $match)) {
            return $chord;
        }

        return ($match[2] ?? '') !== '' ? $match[2] : $match[1];
    }

    /**
     * The map with each chord turned into its bass note; the same note again right after it (on the same line) joins
     * it, with both durations. Passing chords keep their mark when they stay on their own.
     */
    public static function bassLine(array $structure): array
    {
        return array_map(function ($block) {
            if (! is_array($block) || ! is_array($block['chords'] ?? null) || ($block['chords'] ?? []) === []) {
                return $block;
            }
            $durations = is_array($block['durations'] ?? null) ? array_values($block['durations']) : null;
            $passing = is_array($block['passing'] ?? null) ? $block['passing'] : [];
            $chords = [];
            $newDurations = [];
            $newPassing = [];
            $position = -1;
            $count = -1;
            $afterBreak = true;
            foreach ($block['chords'] as $chord) {
                if ($chord === UpdateSongLayoutService::LINE_BREAK) {
                    $chords[] = $chord;
                    $afterBreak = true;
                    continue;
                }
                $position++;
                $note = self::bassNote((string) $chord);
                if (! $afterBreak && end($chords) === $note) {
                    if ($durations !== null) {
                        $newDurations[$count] += $durations[$position] ?? 0;
                    }
                    continue;
                }
                $chords[] = $note;
                $count++;
                $afterBreak = false;
                if ($durations !== null) {
                    $newDurations[] = $durations[$position] ?? 0;
                }
                if (in_array($position, $passing, true)) {
                    $newPassing[] = $count;
                }
            }
            $block['chords'] = $chords;
            if ($durations !== null) {
                $block['durations'] = $newDurations;
            }
            if ($passing !== []) {
                $block['passing'] = $newPassing;
            }

            return $block;
        }, $structure);
    }
}
