<?php

namespace App\Services;

use App\Models\Song;

class UpdateSongLayoutService
{
    // Line break marked by hand inside a section's chords.
    public const LINE_BREAK = '|';

    /**
     * Saves the chord map (structure) with the blocks and line breaks set on the layout page, and the chord sheet
     * when blocks were removed from it too. Each block keeps its other fields; "order" follows the new position.
     */
    public function execute(Song $song, array $blocks, ?array $sheetSections = null, ?string $key = null): Song
    {
        // Key changed on the layout page (the chords come already transposed to it).
        if ($key !== null && $key !== '') {
            $song->musical_key = $key;
        }

        if ($sheetSections !== null) {
            // Chords spelled E#, B#, Cb, Fb or F## get their simple name, as in the chord map below.
            $sheetSections = array_map(fn ($section) => array_merge($section, ['lines' => array_map(
                fn ($line) => is_string($line) && ! str_starts_with(ltrim($line), '{') ? NormalizeChordSpellingService::line($line) : $line,
                $section['lines'] ?? [],
            )]), array_values($sheetSections));
            $song->chord_sheet = array_merge($song->chord_sheet ?? [], ['sections' => $sheetSections]);
        }

        $song->structure = array_map(function (array $block, int $index) {
            $lyrics = is_string($block['lyrics'] ?? null) ? trim(str_replace("\r\n", "\n", $block['lyrics'])) : '';

            $block['order'] = $index + 1;
            $block['section'] = self::sectionKey($block['section']);
            $block['chords'] = array_map(
                fn ($chord) => $chord === self::LINE_BREAK ? $chord : NormalizeChordSpellingService::chord(trim($chord)),
                self::cleanBreaks($block['chords']),
            );
            // Passing chords: valid positions only (line breaks not counted); none means no key at all.
            $count = count(self::chordSequence([$block]));
            $passing = array_values(array_unique(array_filter(
                is_array($block['passing'] ?? null) ? $block['passing'] : [],
                fn ($position) => is_int($position) && $position >= 0 && $position < $count,
            )));
            sort($passing);
            if ($passing) {
                $block['passing'] = $passing;
            } else {
                unset($block['passing']);
            }
            $block = self::cleanDurations($block, $count);
            // Start time set on the page: stored as "m:ss"; empty means none (other values are kept to be fixed).
            $start = self::toSeconds($block['start'] ?? null);
            if ($start !== null) {
                $block['start'] = self::format($start);
            } elseif (trim((string) ($block['start'] ?? '')) === '') {
                unset($block['start']);
            }
            $block['lyrics'] = $lyrics === '' ? null : $lyrics;
            // A new block gets the first line of its lyrics as anchor.
            if (($block['anchor'] ?? null) === null || $block['anchor'] === '') {
                $block['anchor'] = $lyrics === '' ? null : strtok($lyrics, "\n");
            }

            return $block;
        }, array_values($blocks), array_keys(array_values($blocks)));

        $song->save();

        return $song;
    }

    /**
     * All chords of the song, in order, without line breaks.
     */
    public static function chordSequence(array $blocks): array
    {
        return collect($blocks)
            ->flatMap(fn ($block) => is_array($block['chords'] ?? null) ? $block['chords'] : [])
            ->reject(fn ($chord) => $chord === self::LINE_BREAK)
            ->values()
            ->all();
    }

    // Shortest chord duration, in beats: a chord can change on a beat or halfway through it.
    public const DURATION_STEP = 0.5;

    /**
     * Duration of each chord in beats ("durations", next to "chords", line breaks not counted) and beats per bar,
     * as the study app saves them. Kept only when there is one valid duration (a multiple of half a beat) per chord;
     * otherwise the block goes back to having no durations, like the maps made on the layout page.
     */
    private static function cleanDurations(array $block, int $count): array
    {
        $durations = $block['durations'] ?? null;
        $valid = is_array($durations) && count($durations) === $count && $count > 0
            && collect($durations)->every(fn ($beats) => is_numeric($beats) && $beats > 0
                && fmod((float) $beats, self::DURATION_STEP) == 0.0);
        if ($valid) {
            $block['durations'] = array_map(fn ($beats) => floor($beats) == $beats ? (int) $beats : (float) $beats, array_values($durations));
        } else {
            unset($block['durations']);
        }

        $perBar = $block['beats_per_bar'] ?? null;
        if (is_int($perBar) && $perBar >= 1 && $perBar <= 12) {
            $block['beats_per_bar'] = $perBar;
        } else {
            unset($block['beats_per_bar']);
        }

        return $block;
    }

    /**
     * "1:05" / "0:01:05" / "65" / 65 -> 65; null when empty or invalid.
     */
    public static function toSeconds(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return $value >= 0 ? (float) $value : null;
        }

        $value = trim((string) $value);

        if (! preg_match('/^\d+(:\d{1,2}){0,2}(\.\d+)?$/', $value)) {
            return null;
        }

        return array_reduce(explode(':', $value), fn (float $total, string $part) => $total * 60 + (float) $part, 0.0);
    }

    /**
     * 65 -> "1:05"; 65.34 -> "1:05.3" (tenths kept, for starts taken from the beats of the audio).
     */
    public static function format(float $seconds): string
    {
        $tenths = (int) round($seconds * 10);
        $whole = intdiv($tenths, 10);
        $text = intdiv($whole, 60) . ':' . str_pad((string) ($whole % 60), 2, '0', STR_PAD_LEFT);

        return $tenths % 10 === 0 ? $text : $text . '.' . ($tenths % 10);
    }

    /**
     * Line breaks only between chords: none at the start, at the end or repeated.
     */
    private static function cleanBreaks(array $chords): array
    {
        $clean = [];

        foreach ($chords as $chord) {
            if ($chord === self::LINE_BREAK && ($clean === [] || end($clean) === self::LINE_BREAK)) {
                continue;
            }
            $clean[] = $chord;
        }

        if (end($clean) === self::LINE_BREAK) {
            array_pop($clean);
        }

        return $clean;
    }

    /**
     * "Refrão final" -> "REFRÃO_FINAL", the format used by the structure.
     */
    private static function sectionKey(string $name): string
    {
        return trim(preg_replace('/\s+/u', '_', mb_strtoupper(trim($name))), '_');
    }
}
