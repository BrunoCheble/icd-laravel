<?php

namespace Tests\Unit\Music;

use App\Modules\Music\Http\Requests\StudySongMapRequest;
use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\UpdateSongLayoutService;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FreeTimingLayoutTest extends TestCase
{
    private function blocks(): array
    {
        return [
            ['section' => 'INTRO', 'chords' => ['G'], 'durations' => [8], 'beats_per_bar' => 4, 'start' => '0:02', 'free_timing' => true],
            ['section' => 'VERSE_1', 'chords' => ['C'], 'durations' => [8], 'beats_per_bar' => 4, 'start' => '0:11.5', 'free_timing' => true, 'free_start' => '0:11.537'],
            ['section' => 'VERSE_2', 'chords' => ['D'], 'durations' => [8], 'beats_per_bar' => 4, 'start' => '0:19.5', 'free_timing' => true, 'free_start' => ''],
        ];
    }

    public function test_free_timing_and_marks_pass_validation(): void
    {
        $validated = Validator::make(['updated_at' => '2026-10-15T09:00:00Z', 'blocks' => $this->blocks()], (new StudySongMapRequest())->rules())->validated();

        $this->assertTrue($validated['blocks'][0]['free_timing']);
        $this->assertSame('0:11.537', $validated['blocks'][1]['free_start']);
    }

    public function test_marks_are_kept_in_hundredths_and_empty_ones_dropped(): void
    {
        $song = new class extends Song {
            public function save(array $options = []): bool
            {
                return true;
            }
        };

        (new UpdateSongLayoutService())->execute($song, $this->blocks());
        $structure = json_decode(json_encode($song->structure), true);

        $this->assertTrue($structure[0]['free_timing']);
        $this->assertArrayNotHasKey('free_start', $structure[0]);
        $this->assertSame('0:11.54', $structure[1]['free_start']);
        $this->assertSame('0:11.5', $structure[1]['start']);
        $this->assertArrayNotHasKey('free_start', $structure[2]);
    }

    public function test_following_the_bars_stores_no_flag(): void
    {
        $song = new class extends Song {
            public function save(array $options = []): bool
            {
                return true;
            }
        };
        $blocks = array_map(fn ($block) => array_merge($block, ['free_timing' => false]), $this->blocks());

        (new UpdateSongLayoutService())->execute($song, $blocks);

        $this->assertArrayNotHasKey('free_timing', json_decode(json_encode($song->structure), true)[0]);
    }

    public function test_format_with_hundredths(): void
    {
        $this->assertSame('1:05.3', UpdateSongLayoutService::format(65.34));
        $this->assertSame('1:05.34', UpdateSongLayoutService::format(65.34, 2));
        $this->assertSame('1:05.3', UpdateSongLayoutService::format(65.3, 2));
        $this->assertSame('1:05', UpdateSongLayoutService::format(65.001, 2));
        $this->assertSame('0:59.99', UpdateSongLayoutService::format(59.99, 2));
    }
}
