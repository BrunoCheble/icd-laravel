<?php

namespace Tests\Unit;

use App\Services\UpdateChordSheetSectionService;
use PHPUnit\Framework\TestCase;

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

    private function rebuild(array $block, array $chords): array
    {
        $method = new \ReflectionMethod(UpdateChordSheetSectionService::class, 'rebuildChords');

        return $method->invoke(null, $block, $chords);
    }
}
