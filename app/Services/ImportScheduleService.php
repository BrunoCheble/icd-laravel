<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportScheduleService
{
    public function __construct(
        private ParseScheduleService $parser,
        private FindSongBySourceService $sources,
        private SaveSongService $songs,
        private SaveSetlistService $setlists,
    ) {
    }

    /**
     * A schedule shared as text becomes a setlist: each song found here (by its chord sheet address or its title) is
     * used as it is; the others are pre-registered without a chord sheet (title, artist, BPM, video and the chord
     * sheet address, to be imported later with "Send to ICD"). The setlist of the same title and day is updated
     * instead of a new one, with the songs in order and the key of each. Returns the setlist and what was done.
     */
    public function execute(string $text): array
    {
        $schedule = $this->parser->execute($text);
        if ($schedule['songs'] === []) {
            throw ValidationException::withMessages(['text' => __('No songs were found in the text ("1. Title — Artist").')]);
        }

        return DB::transaction(function () use ($schedule) {
            $titles = $this->sources->titles();
            $created = [];
            $existing = [];
            $links = [];

            foreach ($schedule['songs'] as $item) {
                $song = ($item['chord_sheet_url'] ? $this->sources->execute($item['chord_sheet_url']) : null)
                    ?? (isset($titles[FindSongBySourceService::plainTitle($item['title'])]) ? Song::find($titles[FindSongBySourceService::plainTitle($item['title'])]['id']) : null);
                if ($song) {
                    $existing[] = $song;
                } else {
                    $song = $this->songs->execute([
                        'title'       => $item['title'],
                        'artist'      => $item['artist'],
                        'bpm'         => $item['bpm'],
                        'youtube_url' => $item['video'],
                        'source_url'  => $item['chord_sheet_url'],
                        'structure'   => '[]',
                    ]);
                    $created[] = $song;
                    $titles[FindSongBySourceService::plainTitle($song->title)] = ['id' => $song->id];
                }
                $links[$song->id] = ['song_id' => $song->id, 'musical_key' => $item['key'] ?? $song->musical_key];
            }

            $setlist = Setlist::query()->where('title', $schedule['title'])->whereDate('event_date', $schedule['event_date'])->first();
            // Ministers already set in that setlist stay.
            $ministers = $setlist ? $setlist->songs->pluck('pivot.minister_name', 'id')->all() : [];
            $setlist = $this->setlists->execute([
                'title'      => $schedule['title'] ?: __('Setlist'),
                'event_date' => $schedule['event_date'],
                'songs'      => array_map(fn ($link) => $link + ['minister_name' => $ministers[$link['song_id']] ?? null], array_values($links)),
            ], $setlist);

            return [
                'setlist'  => $setlist,
                'created'  => $created,
                'existing' => $existing,
                // Pre-registered songs (or found ones) still without a chord sheet.
                'pending'  => collect([...$created, ...$existing])->filter(fn (Song $song) => empty($song->chord_sheet['sections'] ?? null))->values()->all(),
            ];
        });
    }
}
