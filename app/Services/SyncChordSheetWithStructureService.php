<?php

namespace App\Services;

use App\Models\Song;

/**
 * Splits the chord sheet into the blocks of the chord map (structure) when both have the same chords, so a block
 * split, joined or renamed on the layout page shows the same way in the chord sheet (and gets its section time).
 * Each line goes to the block of its first chord; notes ("{c: ...}") go with the chord after them and lyrics without
 * chords with the chord before them. A section that did not change keeps its name in the chord sheet.
 * "Back to the start" markers of the map show as sections without lines, after the blocks before them.
 * Same rules as the layout page (resources/views/songs/layout.blade.php, sheetByBlocks).
 */
class SyncChordSheetWithStructureService
{
    public function execute(array $sections, mixed $structure): array
    {
        $blocks = array_values(array_filter(
            json_decode(json_encode($structure ?? []), true) ?? [],
            fn ($block) => is_array($block),
        ));
        $sheetChords = UpdateSongLayoutService::chordSequence(array_map(
            fn ($section) => ['chords' => BuildStructureFromChordSheetService::chords($section['lines'] ?? [])],
            $sections,
        ));

        if ($blocks === [] || $sheetChords === [] || $sheetChords !== UpdateSongLayoutService::chordSequence($blocks)) {
            return $sections;
        }

        // Number of the first chord after each block.
        $ends = [];
        $total = 0;
        foreach ($blocks as $block) {
            $total += count(UpdateSongLayoutService::chordSequence([$block]));
            $ends[] = $total;
        }
        $blockOf = function (int $chord) use ($ends): int {
            foreach ($ends as $index => $end) {
                if ($chord < $end) {
                    return $index;
                }
            }
            return count($ends) - 1;
        };

        $groups = [];
        $chordNumber = 0;
        $lastChord = 0;
        foreach ($sections as $section) {
            foreach (array_values($section['lines'] ?? []) as $i => $line) {
                $text = (string) $line;
                if (str_starts_with(ltrim($text), '{')) {
                    $ref = $chordNumber;
                } else {
                    $count = preg_match_all('/\[[^\]]+\]/u', $text);
                    $ref = $count ? $chordNumber : $lastChord;
                    if ($count) {
                        $lastChord = $chordNumber + $count - 1;
                    }
                    $chordNumber += $count;
                }

                $block = $blockOf($ref);
                $last = array_key_last($groups);
                if ($last !== null && $groups[$last]['block'] === $block) {
                    $groups[$last]['lines'][] = $line;
                } else {
                    $groups[] = ['block' => $block, 'lines' => [$line], 'from' => $i === 0 ? $section : null];
                }
            }
        }

        $sections = array_map(function (array $group) use ($blocks) {
            $name = (string) ($blocks[$group['block']]['section'] ?? '');
            $original = $group['from'];
            $unchanged = $original !== null
                && self::key($original['section'] ?? '') === self::key($name)
                && count($original['lines'] ?? []) === count($group['lines']);

            return $unchanged
                ? array_merge($original, ['lines' => $group['lines']])
                : ['section' => self::key($name), 'label' => str_replace('_', ' ', $name), 'lines' => $group['lines']];
        }, $groups);

        $markers = array_keys(array_filter($blocks, fn ($block) => Song::isReturnMarker($block)));
        if ($markers === []) {
            return $sections;
        }
        $marker = fn (int $index) => [
            'section' => self::key((string) ($blocks[$index]['section'] ?? '')),
            'label'   => str_replace('_', ' ', (string) ($blocks[$index]['section'] ?? '')),
            'lines'   => [],
            'jump'    => 'start',
        ];
        $result = [];
        foreach ($sections as $index => $section) {
            while ($markers !== [] && $markers[0] < $groups[$index]['block']) {
                $result[] = $marker(array_shift($markers));
            }
            $result[] = $section;
        }
        foreach ($markers as $index) {
            $result[] = $marker($index);
        }

        return $result;
    }

    private static function key(string $name): string
    {
        return preg_replace('/\s+/u', '_', mb_strtoupper(trim($name)));
    }
}
