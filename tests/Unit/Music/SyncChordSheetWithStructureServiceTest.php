<?php

namespace Tests\Unit\Music;

use App\Modules\Music\Services\SyncChordSheetWithStructureService;
use PHPUnit\Framework\TestCase;

class SyncChordSheetWithStructureServiceTest extends TestCase
{
    public function test_lyrics_without_chords_go_with_the_chord_before_them(): void
    {
        $sections = (new SyncChordSheetWithStructureService())->execute([
            ['section' => 'VERSE', 'lines' => ['[G]Santo', 'santo', '[D]Digno']],
        ], [
            ['section' => 'VERSE', 'chords' => ['G'], 'durations' => [4]],
            ['section' => 'CHORUS', 'chords' => ['D'], 'durations' => [4]],
        ]);

        $this->assertSame(['[G]Santo', 'santo'], $sections[0]['lines']);
        $this->assertSame(['[D]Digno'], $sections[1]['lines']);
    }

    public function test_a_section_starting_without_a_chord_keeps_its_first_lines(): void
    {
        // The chord of "Teu nome é cura" was taken out on the grid: the line stays in the CHORUS, not in the VERSE.
        $sections = (new SyncChordSheetWithStructureService())->execute([
            ['section' => 'VERSE', 'lines' => ['[E]Jesus']],
            ['section' => 'CHORUS', 'lines' => ['Teu nome é cura', 'É [E]poderoso']],
        ], [
            ['section' => 'VERSE', 'chords' => ['E'], 'durations' => [4]],
            ['section' => 'CHORUS', 'chords' => ['E'], 'durations' => [4]],
        ]);

        $this->assertSame(['[E]Jesus'], $sections[0]['lines']);
        $this->assertSame(['Teu nome é cura', 'É [E]poderoso'], $sections[1]['lines']);
    }
}
