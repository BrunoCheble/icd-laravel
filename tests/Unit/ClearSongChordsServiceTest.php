<?php

namespace Tests\Unit;

use App\Models\Song;
use App\Services\ClearSongChordsService;
use App\Services\SyncChordSheetWithStructureService;
use PHPUnit\Framework\TestCase;

class ClearSongChordsServiceTest extends TestCase
{
    public function test_every_bar_gets_no_chord_and_the_blocks_stay(): void
    {
        $song = $this->song([
            ['section' => 'INTRO', 'chords' => ['G', 'D'], 'durations' => [2, 4], 'beats_per_bar' => 4, 'start' => '0:02', 'lead_beats' => 2, 'lyrics' => null],
            ['section' => 'VERSE_1', 'chords' => ['C', '|', 'G', 'Em'], 'durations' => [4, 2, 6], 'beats_per_bar' => 4, 'passing' => [1], 'lyrics' => "Cristo\nbeleza"],
            ['section' => 'VERSE_1', 'jump' => 'start', 'chords' => []],
        ], ['sections' => [
            ['section' => 'INTRO', 'lines' => ['[G] [D]']],
            ['section' => 'VERSE_1', 'lines' => ['{c: 2x}', '[C]Cristo, be[G]leza de [Em]Deus', '   ']],
        ]]);

        (new ClearSongChordsService(new SyncChordSheetWithStructureService()))->execute($song);
        $structure = json_decode(json_encode($song->structure), true);

        // INTRO: 2 empty beats, then 6 beats -> 2 to the bar line, then 4.
        $this->assertSame(['?', '?'], $structure[0]['chords']);
        $this->assertSame([2, 4], $structure[0]['durations']);
        $this->assertSame(2, $structure[0]['lead_beats']);
        $this->assertSame('0:02', $structure[0]['start']);
        // VERSE: 12 beats, starting on a bar line -> 3 bars.
        $this->assertSame(['?', '?', '?'], $structure[1]['chords']);
        $this->assertSame([4, 4, 4], $structure[1]['durations']);
        $this->assertArrayNotHasKey('passing', $structure[1]);
        $this->assertSame("Cristo\nbeleza", $structure[1]['lyrics']);
        $this->assertSame('start', $structure[2]['jump']);
        // The chord sheet: its lyrics with a "?" per bar of each block (the same blocks as the map).
        $this->assertSame(['[?] [?]'], $song->chord_sheet['sections'][0]['lines']);
        $this->assertSame(['{c: 2x}', '[?]Cristo, [?]beleza [?]de Deus'], $song->chord_sheet['sections'][1]['lines']);
        $this->assertSame(['?', '?', '?', '?', '?'], \App\Services\UpdateSongLayoutService::chordSequence(array_map(
            fn ($section) => ['chords' => \App\Services\BuildStructureFromChordSheetService::chords($section['lines'])], $song->chord_sheet['sections'])));
        $this->assertTrue($song->saved);
    }

    private function song(array $structure, array $sheet): Song
    {
        $song = new class extends Song {
            public bool $saved = false;

            public function save(array $options = []): bool
            {
                return $this->saved = true;
            }
        };
        $song->structure = $structure;
        $song->chord_sheet = $sheet;

        return $song;
    }
}
