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
     * the lyrics and where the chords fall can change, the chords themselves cannot (the sheet keeps the map's
     * chords). When the sheet has one section per block of the map, that block's lyrics change too, so the study
     * app and the map keep the same lyrics. Refused (409) when the song changed since the page read it.
     */
    public function execute(Song $song, int $index, array $lines, string $readAt): Song
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
        if (self::chords($lines) !== self::chords($section['lines'] ?? [])) {
            throw ValidationException::withMessages(['lines' => __('Only the lyrics and the place of the chords can change here: keep the same chords, in the same order.')]);
        }

        $oldLyrics = self::lyrics($section['lines'] ?? []);
        $sections[$index]['lines'] = $lines;

        // The block of this section gets the same lyrics (one section per block, in order).
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $timedSections = array_keys(array_filter($sections, fn ($item) => ($item['jump'] ?? null) !== 'start'));
        $timedBlocks = array_keys(array_filter($structure, fn ($block) => is_array($block) && ! Song::isReturnMarker($block)));
        if (count($timedSections) === count($timedBlocks)) {
            $blockIndex = $timedBlocks[array_search($index, $timedSections, true)];
            $block = $structure[$blockIndex];
            $newLyrics = self::lyrics($lines);
            // An anchor that was the first lyric line follows it.
            if (($block['anchor'] ?? null) === null || $block['anchor'] === ($oldLyrics[0] ?? null)) {
                $block['anchor'] = $newLyrics[0] ?? null;
            }
            $block['lyrics'] = $newLyrics === [] ? null : implode("\n", $newLyrics);
            $structure[$blockIndex] = $block;
            $song->structure = $structure;
        }

        // Stored without the "back to the start" markers, which come from the map.
        $song->chord_sheet = array_merge($song->chord_sheet ?? [], [
            'sections' => array_values(array_filter($sections, fn ($item) => ($item['jump'] ?? null) !== 'start')),
        ]);
        $song->save();

        return $song;
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
