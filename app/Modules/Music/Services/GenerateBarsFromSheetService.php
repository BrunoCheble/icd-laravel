<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Song;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class GenerateBarsFromSheetService
{
    public function __construct(
        private BuildStructureFromChordSheetService $structure,
        private CreateSongBarsService $bars,
    ) {
    }

    /**
     * Saves the chord sheet and makes the chord map again from it, from scratch: one block per section, so the bars
     * are then fixed on the grid. A block with its start and the next block's start marked gets the bars of that time,
     * shared among its chords (see distribute); any other block gets one bar per chord (in the song's time signature).
     * A section without chords yet gets a "?" per lyric line (a bar each, to be written by ear). The start of bar 1
     * marked before is kept unless a new one was marked; the map before stays in the song's history.
     */
    public function execute(Song $song, array $sections, string $readAt): Song
    {
        if (! $song->updated_at || ! $song->updated_at->equalTo(Carbon::parse($readAt))) {
            throw new ConflictHttpException(__('The song was changed meanwhile. Load it again before saving.'));
        }
        $old = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $meter = CreateSongBarsService::meterOf($song->structure);
        $firstStart = collect($old)->first(fn ($block) => is_array($block) && ! Song::isReturnMarker($block))['start'] ?? null;

        $sections = SaveSongChordSheetService::clean($sections);
        foreach ($sections as &$section) {
            if (BuildStructureFromChordSheetService::chords($section['lines']) === []) {
                // No chords yet: "?" at the start of each lyric line.
                $section['lines'] = array_map(fn ($line) => str_starts_with(ltrim($line), '{') || trim($line) === '' ? $line : '[?]' . $line, $section['lines']);
                if (BuildStructureFromChordSheetService::chords($section['lines']) === []) {
                    $section['lines'][] = '[?]';
                }
            }
        }
        unset($section);

        $structure = array_map(function (array $block) {
            unset($block['order']);

            return array_filter($block, fn ($value) => $value !== null);
        }, $this->structure->execute($sections));
        if ($firstStart !== null && isset($structure[0])) {
            $structure[0]['start'] = $firstStart;
        }
        $structure = $this->applyTimes($structure, $sections, $song->bpm ? (float) $song->bpm : null, CreateSongBarsService::METERS[$meter], $firstStart);

        $song->chord_sheet = array_merge($song->chord_sheet ?? [], ['sections' => $sections]);
        $song->structure = $structure;
        $song->save();

        return $this->bars->execute($song->fresh(), CreateSongBarsService::METERS[$meter]);
    }

    /**
     * The blocks with the times marked on the chord sheet. Every block starts on a bar line: its end is the bar line
     * nearest to the next block's mark. The positions are counted from the song's start, so that rounding does not add
     * up from block to block. Nothing here turns on free timing: the song stays on its BPM. A first block without a time
     * starts where bar 1 started before (the very start of the recording when it had none): it is never worked out
     * backwards from a later block, which would make the music before it a delay.
     */
    private function applyTimes(array $structure, array $sections, ?float $bpm, int $beatsPerBar, mixed $firstStart = null): array
    {
        $starts = array_map(fn (array $section) => UpdateSongLayoutService::toSeconds($section['start'] ?? null), $sections);
        if ($starts !== [] && $starts[0] === null && array_filter($starts, fn ($start) => $start !== null) !== []) {
            $starts[0] = UpdateSongLayoutService::toSeconds($firstStart) ?? 0.0;
        }
        $timed = fn (int $i) => $starts[$i] !== null && ($starts[$i + 1] ?? null) !== null && $starts[$i + 1] > $starts[$i];
        $hasTimes = collect(array_keys($starts))->contains(fn (int $i) => $timed($i));
        if ($hasTimes && ! $bpm) {
            throw ValidationException::withMessages(['sections' => __('Set the song\'s BPM to make the bars from the times.')]);
        }

        $half = $bpm ? 30 / $bpm : null;
        $tick = 0;
        $origin = null;
        $chained = false;
        foreach ($structure as $i => &$block) {
            if (($sections[$i]['time_only'] ?? false) === true) {
                $block['time_only'] = true;
            }
            if (! $half) {
                continue;
            }
            $chords = count(UpdateSongLayoutService::chordSequence([$block]));
            // Counted from here when the block before it did not end at this block's time.
            if ($starts[$i] !== null && ! $chained) {
                $origin = $starts[$i] - $tick * $half;
            }
            $barTicks = $beatsPerBar * 2;
            $ticks = $timed($i) ? (int) round(($starts[$i + 1] - $origin) / $half / $barTicks) * $barTicks - $tick : null;
            $durations = $ticks === null ? null : self::distribute($chords, $ticks, $beatsPerBar, isset($block['time_only']));
            $chained = $durations !== null;
            $durations ??= array_fill(0, $chords, $beatsPerBar);
            $block['durations'] = $durations;
            $block['beats_per_bar'] = $beatsPerBar;
            if ($origin !== null && $i > 0) {
                $block['start'] = UpdateSongLayoutService::format(max(0, $origin + $tick * $half), 2);
            }
            $tick += (int) round(array_sum($durations) * 2);
        }
        unset($block);
        if ($origin !== null && isset($structure[0])) {
            $structure[0]['start'] = UpdateSongLayoutService::format(max(0, $origin), 2);
        }

        return $structure;
    }

    /**
     * A block's time, in half beats, shared evenly among its chords, in beats: whole bars each, some chords one bar more
     * than others when they do not divide exactly; with fewer bars than chords (or a block with no bars), whole beats
     * each in the same way. Any part of a bar or beat left over stays in the last chord. Null when the time does not
     * even give each chord half a beat.
     */
    public static function distribute(int $chords, int $ticks, int $beatsPerBar, bool $timeOnly = false): ?array
    {
        if ($chords < 1 || $ticks < $chords) {
            return null;
        }
        $bars = intdiv($ticks, $beatsPerBar * 2);
        $beats = intdiv($ticks, 2);
        if (! $timeOnly && $bars >= $chords) {
            [$units, $unit] = [$bars, $beatsPerBar];
        } elseif ($beats >= $chords) {
            [$units, $unit] = [$beats, 1];
        } else {
            [$units, $unit] = [$chords, 0.5];
        }
        $durations = [];
        $previous = 0;
        for ($i = 1; $i <= $chords; $i++) {
            $end = (int) round($i * $units / $chords);
            $durations[] = ($end - $previous) * $unit;
            $previous = $end;
        }
        $durations[$chords - 1] += ($ticks / 2) - $units * $unit;

        return array_map(fn ($length) => floor($length) == $length ? (int) $length : (float) $length, $durations);
    }
}
