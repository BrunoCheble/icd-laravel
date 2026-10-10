<?php

namespace App\Services;

use App\Models\Song;
use App\Models\SongRevision;

class RecordSongRevisionService
{
    // What a revision keeps: a change to any of these keeps the earlier values.
    public const FIELDS = ['structure', 'chord_sheet', 'musical_key', 'bpm'];

    /**
     * Keeps the song's chord map, chord sheet, key and tempo as they were before the change being saved, with where
     * the change came from (the route) and who made it; only the last SongRevision::KEEP stay.
     */
    public function execute(Song $song): void
    {
        if (! $song->exists || ! $song->isDirty(self::FIELDS)) {
            return;
        }

        $raw = fn (string $field) => json_decode((string) $song->getRawOriginal($field), true);
        // Only the key changed (every chord moved by the same interval, nothing else): nothing to keep, the earlier
        // version is the same song in another key.
        if (! $song->isDirty('bpm') && self::onlyTransposed(
            [$raw('structure'), $raw('chord_sheet')],
            [json_decode(json_encode($song->structure), true), json_decode(json_encode($song->chord_sheet), true)],
        )) {
            return;
        }
        $song->revisions()->create([
            'user_id'     => auth()->id(),
            'source'      => request()?->route()?->getName(),
            'structure'   => $raw('structure'),
            'chord_sheet' => $raw('chord_sheet'),
            'musical_key' => $song->getRawOriginal('musical_key'),
            'bpm'         => $song->getRawOriginal('bpm'),
        ]);

        $keep = $song->revisions()->latest('id')->limit(SongRevision::KEEP)->pluck('id');
        $song->revisions()->whereNotIn('id', $keep)->delete();
    }

    private const PITCHES = ['C' => 0, 'D' => 2, 'E' => 4, 'F' => 5, 'G' => 7, 'A' => 9, 'B' => 11];

    /**
     * Whether `$after` is `$before` with every chord moved by the same number of semitones (same kind of chord, same
     * everything else: blocks, durations, lyrics and where the chords are).
     */
    public static function onlyTransposed(mixed $before, mixed $after): bool
    {
        $chords = [[], []];
        // Everything with the chords taken out: the chords of each block's "chords" list and the [chord] marks of the
        // chord sheet's lines.
        $skeleton = function (mixed $value, int $side, mixed $key = null) use (&$skeleton, &$chords) {
            if (is_array($value)) {
                if ($key === 'chords') {
                    return array_map(function ($chord) use (&$chords, $side) {
                        if (! is_string($chord) || $chord === UpdateSongLayoutService::LINE_BREAK) {
                            return $chord;
                        }
                        $chords[$side][] = $chord;

                        return '§';
                    }, $value);
                }
                $out = [];
                foreach ($value as $name => $item) {
                    $out[$name] = $skeleton($item, $side, $name);
                }

                return $out;
            }
            if (! is_string($value)) {
                return $value;
            }

            return preg_replace_callback('/\[([^\]]+)\]/u', function ($match) use (&$chords, $side) {
                $chords[$side][] = $match[1];

                return '[§]';
            }, $value);
        };
        if ($skeleton($before, 0) !== $skeleton($after, 1) || count($chords[0]) !== count($chords[1]) || $chords[0] === []) {
            return false;
        }

        $parse = function (string $chord): ?array {
            if (! preg_match('/^([A-G])([#b]?)(.*?)(?:\/([A-G])([#b]?))?$/u', $chord, $m)) {
                return null;
            }
            $pitch = fn ($letter, $accidental) => (self::PITCHES[$letter] + ($accidental === '#' ? 1 : ($accidental === 'b' ? -1 : 0)) + 12) % 12;

            return [$pitch($m[1], $m[2]), $m[3], isset($m[4]) && $m[4] !== '' ? $pitch($m[4], $m[5] ?? '') : null];
        };
        $interval = null;
        foreach ($chords[0] as $index => $chord) {
            [$old, $new] = [$parse($chord), $parse($chords[1][$index])];
            if (! $old || ! $new || $old[1] !== $new[1] || ($old[2] === null) !== ($new[2] === null)) {
                return false;
            }
            $steps = ($new[0] - $old[0] + 12) % 12;
            if (($interval ??= $steps) !== $steps || ($old[2] !== null && ($new[2] - $old[2] + 12) % 12 !== $steps)) {
                return false;
            }
        }

        return $interval !== 0;
    }
}
