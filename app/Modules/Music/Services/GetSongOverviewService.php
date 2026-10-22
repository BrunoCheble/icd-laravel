<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Setlist;
use App\Modules\Music\Models\Song;
use App\Modules\Music\Support\NoChord;

class GetSongOverviewService
{
    public function __construct(private SyncChordSheetWithStructureService $syncSheet)
    {
    }

    /**
     * What the song page shows: what is done and what is missing (review, section times, video, chord sheet,
     * lines set by hand, lessons), the setlists with it and its chord sheet (split like the map's blocks, as on the
     * layout page).
     */
    public function execute(Song $song): array
    {
        $blocks = array_values(array_filter(
            json_decode(json_encode(is_array($song->structure) ? $song->structure : []), true) ?? [],
            fn ($block) => is_array($block),
        ));
        [$timed, $total] = $song->timedSections();

        return [
            'status' => [
                'reviewed'      => $song->isReviewed(),
                'reviewedAt'    => $song->reviewed_at?->format('d/m/Y'),
                'timed'         => $timed,
                'blocks'        => $total,
                'youtube'       => (bool) $song->youtube_url,
                'sheetSections' => count($song->chord_sheet['sections'] ?? []),
                'manualBlocks'  => count(array_filter($blocks, fn ($block) => in_array(UpdateSongLayoutService::LINE_BREAK, $block['chords'] ?? [], true))),
                'lessons'       => $song->lessons()->count(),
            ],
            'setlists' => $song->setlists()
                ->orderByDesc('event_date')
                ->get()
                ->map(fn (Setlist $setlist) => [
                    'id'       => $setlist->id,
                    'title'    => $setlist->title,
                    'date'     => $setlist->event_date?->format('d/m/Y'),
                    'key'      => $setlist->pivot->musical_key ?? $song->musical_key,
                    'minister' => $setlist->pivot->minister_name,
                    'position' => $setlist->pivot->position,
                ])
                ->all(),
            'sheet' => self::sheet($song->chord_sheet['sections'] ?? [] ? $this->syncSheet->execute($song->chord_sheet['sections'], $song->structure) : []),
        ];
    }

    /**
     * Chord sheet sections to show: each line as a note ("{c: 2x}") or as pieces of a chord and the lyrics after it
     * ("[G]Santo [D]é" -> [G, "Santo "], [D, "é"]). Silence (N.C.) shows no chord; a line with nothing else is left out.
     */
    private static function sheet(array $sections): array
    {
        return array_map(fn (array $section) => [
            'label'  => (string) ($section['label'] ?? str_replace('_', ' ', (string) ($section['section'] ?? ''))),
            'marker' => ($section['jump'] ?? null) === 'start',
            'lines'  => array_values(array_filter(array_map(function ($line) {
                $text = (string) $line;
                if (preg_match('/^\s*\{\s*(?:c|comment)?\s*:?\s*(.*?)\s*\}\s*$/iu', $text, $note)) {
                    return ['comment' => $note[1]];
                }
                $parts = preg_split('/\[([^\]]+)\]/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                $units = $parts[0] !== '' ? [[null, $parts[0]]] : [];
                for ($i = 1; $i < count($parts); $i += 2) {
                    $units[] = [NoChord::is($parts[$i]) ? null : $parts[$i], $parts[$i + 1] ?? ''];
                }

                return ['units' => $units];
            }, array_values($section['lines'] ?? [])), fn (array $line) => isset($line['comment'])
                || collect($line['units'])->contains(fn ($unit) => $unit[0] !== null || trim($unit[1]) !== ''))),
        ], $sections);
    }
}
