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
    public function execute(Song $song, array $blocks, ?array $sheetSections = null): Song
    {
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
