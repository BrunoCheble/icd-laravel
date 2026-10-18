<?php

namespace Tests\Unit\Music;

use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\BuildStructureFromChordSheetService;
use App\Modules\Music\Services\CreateSongBarsService;
use App\Modules\Music\Services\ImportSongChordSheetService;
use App\Modules\Music\Services\ParseChordSheetService;
use PHPUnit\Framework\TestCase;

class ImportSongChordSheetServiceTest extends TestCase
{
    public function test_a_pre_registered_song_gets_the_chord_sheet_the_map_and_bars(): void
    {
        $song = new class extends Song {
            public int $saves = 0;

            public function save(array $options = []): bool
            {
                $this->saves++;

                return true;
            }
        };
        $song->title = 'Cristo';
        $song->bpm = 120;

        $sheet = "<pre>[Intro] <b>G</b> <b>D</b>\n\n[Refrão]\n<b>C</b>          <b>G</b>\nAleluia, aleluia ao Rei</pre>";
        $service = new ImportSongChordSheetService(new ParseChordSheetService(), new BuildStructureFromChordSheetService(), new CreateSongBarsService());
        $service->execute($song, $sheet, 'G', 'https://youtu.be/iWYcaIeo9E8');

        $this->assertNotEmpty($song->chord_sheet['sections']);
        $structure = json_decode(json_encode($song->structure), true);
        $this->assertSame(['G', 'D'], array_values(array_filter($structure[0]['chords'], fn ($chord) => $chord !== '|')));
        $this->assertSame([4, 4], $structure[0]['durations']);
        $this->assertTrue($song->hasBars());
        $this->assertSame('G', $song->musical_key);
        $this->assertSame('https://youtu.be/iWYcaIeo9E8', $song->youtube_url);
        $this->assertEquals(120, $song->bpm);
    }
}
