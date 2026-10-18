<?php

namespace Tests\Unit\Music;

use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\CreateSongBarsService;
use PHPUnit\Framework\TestCase;

class CreateSongBarsServiceTest extends TestCase
{
    public function test_blocks_without_bars_get_one_bar_of_four_beats_per_chord_by_default(): void
    {
        $song = $this->song([
            ['section' => 'INTRO', 'chords' => ['G', 'D']],
            ['section' => 'VERSE_1', 'chords' => ['G', '|', 'C', 'D']],
        ]);

        $this->assertSame(2, (new CreateSongBarsService())->missingBlocks($song));
        $structure = $this->structure((new CreateSongBarsService())->execute($song));

        $this->assertSame([4, 4], $structure[0]['durations']);
        $this->assertSame([4, 4, 4], $structure[1]['durations']);
        $this->assertSame(4, $structure[1]['beats_per_bar']);
        $this->assertEquals(70, $song->bpm);
        $this->assertTrue($song->saved);
    }

    public function test_blocks_with_bars_keep_them_and_new_ones_follow_their_time_signature(): void
    {
        $song = $this->song([
            ['section' => 'INTRO', 'chords' => ['G', 'D'], 'durations' => [3, 6], 'beats_per_bar' => 3],
            ['section' => 'VERSE_1', 'chords' => ['C', 'D']],
            ['section' => 'VERSE_1', 'jump' => 'start'],
            ['section' => 'FINAL', 'chords' => []],
        ]);

        $structure = $this->structure((new CreateSongBarsService())->execute($song));

        $this->assertSame([3, 6], $structure[0]['durations']);
        $this->assertSame([3, 3], $structure[1]['durations']);
        $this->assertArrayNotHasKey('durations', $structure[2]);
        $this->assertArrayNotHasKey('durations', $structure[3]);
    }

    public function test_a_song_whose_blocks_all_have_bars_is_not_saved(): void
    {
        $song = $this->song([['section' => 'INTRO', 'chords' => ['G'], 'durations' => [4], 'beats_per_bar' => 4]]);
        $song->bpm = 120;

        $this->assertSame(0, (new CreateSongBarsService())->missingBlocks($song));
        (new CreateSongBarsService())->execute($song, 6);

        $this->assertFalse($song->saved);
        $this->assertEquals(120, $song->bpm);
    }

    public function test_a_song_with_bars_and_no_tempo_gets_the_default_one(): void
    {
        $song = $this->song([['section' => 'INTRO', 'chords' => ['G'], 'durations' => [4], 'beats_per_bar' => 4]]);

        (new CreateSongBarsService())->execute($song);

        $this->assertEquals(70, $song->bpm);
        $this->assertTrue($song->saved);
    }

    public function test_a_new_time_signature_keeps_every_chord_on_its_beat(): void
    {
        $song = $this->song([
            ['section' => 'VERSE_1', 'chords' => ['C', 'D'], 'durations' => [3, 3], 'beats_per_bar' => 3],
            ['section' => 'VERSE_1', 'jump' => 'start'],
        ]);

        $structure = $this->structure((new CreateSongBarsService())->changeMeter($song, 4));

        $this->assertSame([3, 3], $structure[0]['durations']);
        $this->assertSame(4, $structure[0]['beats_per_bar']);
        $this->assertArrayNotHasKey('beats_per_bar', $structure[1]);
        $this->assertSame('4/4', CreateSongBarsService::meterOf($song->structure));
    }

    private function song(array $structure): Song
    {
        // Saving is only recorded: no database here.
        $song = new class extends Song {
            public bool $saved = false;

            public function save(array $options = []): bool
            {
                return $this->saved = true;
            }
        };
        $song->structure = $structure;

        return $song;
    }

    private function structure(Song $song): array
    {
        return json_decode(json_encode($song->structure), true);
    }
}
