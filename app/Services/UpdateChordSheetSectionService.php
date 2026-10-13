<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateChordSheetSectionService
{
    public function __construct(private SyncChordSheetWithStructureService $syncSheet)
    {
    }

    /**
     * Saves the lines of one section of the chord sheet as the layout page shows it (split like the map's blocks):
     * the lyrics and where the chords fall can change, and a chord can be swapped for another one; none can be added
     * or removed (each chord has its beats in the map: that is done on the grid of bars). When the sheet has one
     * section per block of the map, that block's lyrics and chords change too, so the sheet keeps the map's chords
     * and the study app and the map keep the same lyrics. With `$same`, the copies of the section (the other sections with the same
     * name and the same lines, e.g. every CHORUS written alike) change the same way; a different one (a longer last
     * chorus) is left as it is. Refused (409) when the song changed since the page read it.
     */
    public function execute(Song $song, int $index, array $lines, string $readAt, bool $same = false): Song
    {
        if (! $song->updated_at || ! $song->updated_at->equalTo(Carbon::parse($readAt))) {
            throw new ConflictHttpException(__('The song was changed meanwhile. Load it again before saving.'));
        }

        $stored = $song->chord_sheet['sections'] ?? [];
        $sections = $stored ? $this->syncSheet->execute($stored, $song->structure) : [];
        $section = $sections[$index] ?? null;
        if (! $section || ($section['jump'] ?? null) === 'start') {
            throw ValidationException::withMessages(['index' => __('This part of the chord sheet no longer exists. Load the page again.')]);
        }

        $lines = array_values(array_map(fn ($line) => rtrim(str_replace("\r", '', (string) $line)), $lines));
        while ($lines !== [] && trim(end($lines)) === '') {
            array_pop($lines);
        }
        $newChords = self::chords($lines);
        $chordsChanged = $newChords !== self::chords($section['lines'] ?? []);
        if (count($newChords) !== count(self::chords($section['lines'] ?? []))) {
            throw ValidationException::withMessages(['lines' => __('Here a chord can be swapped for another one, but not added or removed: keep the same number of chords (add or remove bars on the grid).')]);
        }

        $oldLyrics = self::lyrics($section['lines'] ?? []);
        $targets = $same ? self::copies($sections, $index) : [$index];
        foreach ($targets as $target) {
            $sections[$target]['lines'] = $lines;
        }

        // The blocks of these sections get the same lyrics (one section per block, in order).
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $timedSections = array_keys(array_filter($sections, fn ($item) => ($item['jump'] ?? null) !== 'start'));
        $timedBlocks = array_keys(array_filter($structure, fn ($block) => is_array($block) && ! Song::isReturnMarker($block)));
        if ($chordsChanged && count($timedSections) !== count($timedBlocks)) {
            throw ValidationException::withMessages(['lines' => __('The chords can only change here when the chord sheet follows the map block by block.')]);
        }
        if (count($timedSections) === count($timedBlocks)) {
            $newLyrics = self::lyrics($lines);
            foreach ($targets as $target) {
                $blockIndex = $timedBlocks[array_search($target, $timedSections, true)];
                $block = $structure[$blockIndex];
                // An anchor that was the first lyric line follows it.
                if (($block['anchor'] ?? null) === null || $block['anchor'] === ($oldLyrics[0] ?? null)) {
                    $block['anchor'] = $newLyrics[0] ?? null;
                }
                $block['lyrics'] = $newLyrics === [] ? null : implode("\n", $newLyrics);
                if ($chordsChanged) {
                    $block['chords'] = self::swapChords($block['chords'] ?? [], $newChords);
                }
                $structure[$blockIndex] = $block;
            }
            $song->structure = $structure;
        }

        // Stored without the "back to the start" markers, which come from the map.
        $song->chord_sheet = array_merge($song->chord_sheet ?? [], [
            'sections' => array_values(array_filter($sections, fn ($item) => ($item['jump'] ?? null) !== 'start')),
        ]);
        $song->save();

        return $song;
    }

    /**
     * A block's chords with each one swapped, in order, for the new ones; its line breaks stay where they are.
     */
    private static function swapChords(array $chords, array $newChords): array
    {
        $next = 0;
        foreach ($chords as $i => $chord) {
            if ($chord !== UpdateSongLayoutService::LINE_BREAK) {
                $chords[$i] = $newChords[$next++] ?? $chord;
            }
        }

        return $chords;
    }

    /**
     * The section and its copies: the other sections with the same name and exactly the same lines.
     */
    private static function copies(array $sections, int $index): array
    {
        $name = $sections[$index]['section'] ?? null;

        return array_keys(array_filter($sections, fn ($item, $i) => $i === $index || ($name !== null
            && ($item['jump'] ?? null) !== 'start'
            && ($item['section'] ?? null) === $name
            && ($item['lines'] ?? []) === ($sections[$index]['lines'] ?? [])), ARRAY_FILTER_USE_BOTH));
    }

    private static function chords(array $lines): array
    {
        return UpdateSongLayoutService::chordSequence([['chords' => BuildStructureFromChordSheetService::chords($lines)]]);
    }

    /**
     * Lyric lines of chord sheet lines: without chords and notes, lines with chords only left out.
     */
    private static function lyrics(array $lines): array
    {
        $lyrics = [];
        foreach ($lines as $line) {
            $line = (string) $line;
            if (str_starts_with(ltrim($line), '{')) {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', preg_replace('/\[[^\]]+\]/u', '', $line)));
            if ($text !== '' && ! preg_match('/^[()|\s]+$/u', $text)) {
                $lyrics[] = $text;
            }
        }

        return $lyrics;
    }
}
