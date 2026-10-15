<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class GenerateBarsFromSheetService
{
    public function __construct(
        private BuildStructureFromChordSheetService $structure,
        private CreateSongBarsService $bars,
    ) {
    }

    /**
     * Saves the chord sheet and makes the chord map again from it, from scratch: one block per section, one bar per
     * chord (in the song's time signature), so the bars are then fixed on the grid. A section without chords yet gets
     * a "?" per lyric line (a bar each, to be written by ear). The start of bar 1 marked before is kept; the map before
     * stays in the song's history.
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

        $song->chord_sheet = array_merge($song->chord_sheet ?? [], ['sections' => $sections]);
        $song->structure = $structure;
        $song->save();

        return $this->bars->execute($song->fresh(), CreateSongBarsService::METERS[$meter]);
    }
}
