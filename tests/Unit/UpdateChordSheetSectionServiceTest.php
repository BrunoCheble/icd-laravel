<?php

namespace Tests\Unit;

use App\Services\UpdateChordSheetSectionService;
use Tests\TestCase;

class UpdateChordSheetSectionServiceTest extends TestCase
{
    public function test_an_added_chord_takes_the_second_half_of_the_chord_before_it(): void
    {
        $block = $this->rebuild(['chords' => ['G', 'D', '|', 'Em', 'C'], 'durations' => [4, 4, 4, 4]], ['G', 'D', 'D/F#', 'Em', 'C']);

        $this->assertSame(['G', 'D', 'D/F#', '|', 'Em', 'C'], $block['chords']);
        $this->assertSame([4, 2, 2, 4, 4], $block['durations']);
    }

    public function test_a_removed_chord_gives_its_beats_to_the_chord_before_it(): void
    {
        $block = $this->rebuild(['chords' => ['G', 'D', '|', 'Em', 'C'], 'durations' => [4, 2, 2, 4]], ['G', 'Em', 'C']);

        $this->assertSame(['G', '|', 'Em', 'C'], $block['chords']);
        $this->assertSame([6, 2, 4], $block['durations']);
    }

    public function test_the_first_chord_removed_gives_its_beats_to_the_next_one(): void
    {
        $block = $this->rebuild(['chords' => ['G', 'D', 'Em'], 'durations' => [4, 4, 4]], ['D', 'Em']);

        $this->assertSame(['D', 'Em'], $block['chords']);
        $this->assertSame([8, 4], $block['durations']);
    }

    public function test_swaps_and_additions_together_keep_the_block_length(): void
    {
        $block = $this->rebuild(['chords' => ['G', 'D', 'Em', 'C'], 'durations' => [4, 4, 3, 1], 'passing' => [3]], ['A', 'G', 'D', 'Em7', 'C']);

        $this->assertSame(['A', 'G', 'D', 'Em7', 'C'], $block['chords']);
        $this->assertSame(12.0, (float) array_sum($block['durations']));
        $this->assertSame([2, 2, 4, 3, 1], $block['durations']);
        $this->assertSame([4], $block['passing']);
    }

    public function test_an_added_chord_after_a_half_beat_chord_takes_from_the_next_one(): void
    {
        $block = $this->rebuild(['chords' => ['G', 'D'], 'durations' => [0.5, 4]], ['G', 'A', 'D']);

        $this->assertSame([0.5, 2, 2], $block['durations']);
    }

    public function test_many_chords_pasted_into_few_bars_share_the_block_beats(): void
    {
        // Two bars of "?", and nine chords pasted over the lyrics: the 8 beats shared, the block keeps its length.
        $block = $this->rebuild(['chords' => ['?', '?'], 'durations' => [4, 4], 'beats_per_bar' => 4], ['C', 'F', 'C', 'F', 'G', 'C', 'F', 'G', 'C']);

        $this->assertSame(['C', 'F', 'C', 'F', 'G', 'C', 'F', 'G', 'C'], $block['chords']);
        $this->assertSame(8.0, (float) array_sum($block['durations']));
        $this->assertSame([1, 1, 1, 1, 1, 1, 1, 0.5, 0.5], $block['durations']);
    }

    public function test_more_chords_than_half_beats_grow_the_block_by_whole_bars(): void
    {
        // Half a bar of "?" and five chords: two bars, a beat for each chord (the first ones a little longer).
        $block = $this->rebuild(['chords' => ['?'], 'durations' => [2], 'beats_per_bar' => 4], ['C', 'F', 'G', 'Am', 'D']);

        $this->assertSame(['C', 'F', 'G', 'Am', 'D'], $block['chords']);
        $this->assertSame(8.0, (float) array_sum($block['durations']));
        $this->assertSame([2, 1.5, 1.5, 1.5, 1.5], $block['durations']);
    }

    private function rebuild(array $block, array $chords): array
    {
        $method = new \ReflectionMethod(UpdateChordSheetSectionService::class, 'rebuildChords');

        return $method->invoke(null, $block, $chords);
    }
}
