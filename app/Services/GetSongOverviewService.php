<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;

class GetSongOverviewService
{
    /**
     * What the song page shows: what is done and what is missing (review, section times, video, chord sheet,
     * lines set by hand, video lessons), the setlists with it and its chord map blocks for a preview.
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
                'reviewed_at'   => $song->reviewed_at,
                'timed'         => $timed,
                'blocks'        => $total,
                'youtube'       => (bool) $song->youtube_url,
                'sheetSections' => count($song->chord_sheet['sections'] ?? []),
                'manualBlocks'  => count(array_filter($blocks, fn ($block) => in_array(UpdateSongLayoutService::LINE_BREAK, $block['chords'] ?? [], true))),
                'lessons'       => count($song->video_lesson ?? []),
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
            'blocks' => array_map(fn (array $block) => [
                'name'   => str_replace('_', ' ', (string) ($block['section'] ?? '')),
                'start'  => $block['start'] ?? null,
                'anchor' => $block['anchor'] ?? null,
                'chords' => array_map('strval', $block['chords'] ?? []),
                'passing' => array_values(array_filter($block['passing'] ?? [], 'is_int')),
            ], $blocks),
        ];
    }
}
