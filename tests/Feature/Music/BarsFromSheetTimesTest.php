<?php

namespace Tests\Feature\Music;

use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\GenerateBarsFromSheetService;
use Illuminate\Validation\ValidationException;

class BarsFromSheetTimesTest extends MusicTestCase
{
    private function make(array $sections, ?float $bpm = 70.0): array
    {
        $song = Song::create(['title' => 'Teste', 'artist' => 'Teste', 'bpm' => $bpm, 'structure' => [], 'chord_sheet' => ['sections' => []]]);
        $saved = app(GenerateBarsFromSheetService::class)->execute($song, $sections, $song->updated_at->toIso8601String());

        return [$saved->fresh(), json_decode(json_encode($saved->fresh()->structure), true)];
    }

    private function section(string $name, array $chords, ?string $start = null, bool $timeOnly = false): array
    {
        $line = implode(' ', array_map(fn ($chord) => "[{$chord}]la", $chords));

        return array_filter(['section' => $name, 'lines' => [$line], 'start' => $start, 'time_only' => $timeOnly ?: null]);
    }

    public function test_the_bars_are_shared_among_all_the_chords(): void
    {
        // 30 s at 70 BPM: 35 beats, the nearest bar line is 9 bars away, 3 bars for each of the 3 chords.
        [, $structure] = $this->make([
            $this->section('VERSE_1', ['C', 'G', 'Am'], '0:10'),
            $this->section('CHORUS', ['F'], '0:40'),
        ]);

        $this->assertSame([12, 12, 12], $structure[0]['durations']);
        $this->assertSame(4, $structure[0]['beats_per_bar']);
        $this->assertSame('0:10', $structure[0]['start']);
        $this->assertArrayNotHasKey('free_timing', $structure[0]);
    }

    public function test_bars_that_do_not_divide_exactly_are_spread_not_left_to_the_last_chord(): void
    {
        // A Boa Parte's pre-chorus: 16 bars for 9 chords.
        $this->assertSame([8, 8, 4, 8, 8, 8, 4, 8, 8], GenerateBarsFromSheetService::distribute(9, 128, 4));
    }

    public function test_every_block_starts_on_a_bar_line(): void
    {
        // Marks that fall in the middle of bars at this BPM.
        [, $structure] = $this->make([
            $this->section('VERSE_1', ['C', 'G'], '0:10.3'),
            $this->section('CHORUS', ['F', 'G', 'C'], '0:27.1'),
            $this->section('VERSE_2', ['Am', 'F'], '0:51.9'),
            $this->section('BRIDGE', ['C', 'G', 'F', 'Am'], '1:03.4'),
            $this->section('OUTRO', ['C'], '1:30.7'),
        ]);

        $beat = 0;
        foreach ($structure as $block) {
            $this->assertSame(0, $beat % 4, "{$block['section']} starts at beat {$beat}");
            $beat += array_sum($block['durations']);
        }
    }

    public function test_the_last_block_and_a_block_without_times_get_one_bar_per_chord(): void
    {
        [, $structure] = $this->make([
            $this->section('INTRO', ['C'], '0:00'),
            $this->section('VERSE_1', ['C', 'G']),
            $this->section('CHORUS', ['F', 'G'], '0:20'),
            $this->section('OUTRO', ['F', 'G'], '0:30'),
        ]);

        $this->assertSame([4, 4], $structure[1]['durations']);
        $this->assertSame([4, 4], $structure[3]['durations']);
    }

    public function test_a_first_block_without_a_time_starts_at_the_start_not_after_a_delay(): void
    {
        // A Boa Parte: the first block unmarked, the second one at 0:26.52 (144 BPM: 16 bars).
        [, $structure] = $this->make([
            $this->section('VERSE_1', ['E']),
            $this->section('PRE_CHORUS', ['A'], '0:26.52'),
        ], 144.0);

        $this->assertSame('0:00', $structure[0]['start']);
        $this->assertSame([64], $structure[0]['durations']);
    }

    public function test_a_first_block_without_a_time_keeps_the_start_of_bar_1_it_had(): void
    {
        $song = Song::create(['title' => 'Teste', 'artist' => 'Teste', 'bpm' => 144, 'structure' => [['section' => 'VERSE_1', 'chords' => ['E'], 'start' => '0:02.50']], 'chord_sheet' => ['sections' => []]]);
        $saved = app(GenerateBarsFromSheetService::class)->execute($song, [
            $this->section('VERSE_1', ['E']),
            $this->section('PRE_CHORUS', ['A'], '0:29.17'),
        ], $song->updated_at->toIso8601String());

        $this->assertSame('0:02.5', json_decode(json_encode($saved->fresh()->structure), true)[0]['start']);
    }

    public function test_rounding_does_not_add_up_from_block_to_block(): void
    {
        // Each block lasts a little more than 2 bars: the positions follow the marks, not the sum of the rounded lengths.
        $beat = 60 / 70;
        $sections = [];
        foreach (range(0, 8) as $i) {
            $sections[] = $this->section("PART_{$i}", ['C'], sprintf('0:%05.2f', $i * ($beat * 8 + 0.2)));
        }
        [, $structure] = $this->make($sections);

        $beats = array_sum(array_map(fn ($block) => array_sum($block['durations']), array_slice($structure, 0, 8)));
        $this->assertEqualsWithDelta(8 * ($beat * 8 + 0.2) / $beat, $beats, 2);
    }

    public function test_fewer_bars_than_chords_are_shared_in_beats(): void
    {
        $this->assertSame([2, 3, 2, 2.5], GenerateBarsFromSheetService::distribute(4, 19, 4));
        $this->assertSame([1, 1, 1, 1.5], GenerateBarsFromSheetService::distribute(4, 9, 4));
        $this->assertSame([0.5, 0.5, 0.5, 1], GenerateBarsFromSheetService::distribute(4, 5, 4));
    }

    public function test_a_time_too_short_for_its_chords_gets_one_bar_per_chord(): void
    {
        $this->assertNull(GenerateBarsFromSheetService::distribute(4, 3, 4));
        [, $structure] = $this->make([
            $this->section('VERSE_1', ['C', 'G', 'Am', 'F'], '0:10'),
            $this->section('CHORUS', ['F'], '0:10.5'),
        ]);
        $this->assertSame([4, 4, 4, 4], $structure[0]['durations']);
    }

    public function test_a_block_with_no_bars_is_marked_and_shared_in_beats(): void
    {
        // The nearest bar line is 36 beats away: shared in beats among the 3 chords.
        [$song, $structure] = $this->make([
            $this->section('INTRO', ['C', 'G', 'Am'], '0:10', true),
            $this->section('CHORUS', ['F'], '0:40'),
        ]);

        $this->assertTrue($structure[0]['time_only']);
        $this->assertSame([12, 12, 12], $structure[0]['durations']);
        $this->assertArrayNotHasKey('time_only', $structure[1]);
        $this->assertTrue($song->hasBars());
        $this->assertTrue($song->chord_sheet['sections'][0]['time_only']);
        $this->assertSame('0:10', $song->chord_sheet['sections'][0]['start']);
    }

    public function test_times_without_a_bpm_are_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->make([$this->section('VERSE_1', ['C'], '0:10'), $this->section('CHORUS', ['F'], '0:40')], null);
    }

    public function test_without_times_it_stays_one_bar_per_chord(): void
    {
        [, $structure] = $this->make([$this->section('VERSE_1', ['C', 'G']), $this->section('CHORUS', ['F'])], null);

        $this->assertSame([4, 4], $structure[0]['durations']);
        $this->assertSame([4], $structure[1]['durations']);
    }

    public function test_the_layout_page_keeps_a_block_with_no_bars_when_saved(): void
    {
        [$song, $structure] = $this->make([
            $this->section('INTRO', ['C'], '0:10', true),
            $this->section('CHORUS', ['F'], '0:40'),
        ]);

        $this->actingAs($this->admin())->putJson(route('songs.bars.update', $song), [
            'blocks' => $structure,
            'updated_at' => $song->updated_at->toIso8601String(),
            'bpm' => 70,
        ])->assertOk();

        $this->assertTrue(json_decode(json_encode($song->fresh()->structure), true)[0]['time_only']);
    }
}
