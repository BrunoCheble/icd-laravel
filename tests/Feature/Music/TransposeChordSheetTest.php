<?php

namespace Tests\Feature\Music;

use App\Modules\Music\Models\Song;

/**
 * The chord sheet when the layout page saves the map: a new key moves every chord in its place, a chord edited in the
 * same key is followed by name.
 */
class TransposeChordSheetTest extends MusicTestCase
{
    public function test_a_transposition_keeps_every_chord_in_its_place_on_the_chord_sheet(): void
    {
        // C to D: the G of the old key is also a chord of the new one (F becomes G), which used to pile chords up.
        $line = fn (array $c) => "Eu {$c[0]}sou {$c[1]}Teu e {$c[2]}meu {$c[3]}Deus";
        $marks = fn (array $names) => array_map(fn ($name) => "[{$name}]", $names);
        $song = Song::create(['title' => 'Teste', 'artist' => 'Teste', 'bpm' => 170, 'musical_key' => 'C',
            'structure' => [['section' => 'CHORUS', 'chords' => ['F', 'C', 'G', 'Am7'], 'durations' => [4, 4, 4, 4], 'beats_per_bar' => 4, 'start' => '0:00']],
            'chord_sheet' => ['sections' => [['section' => 'CHORUS', 'lines' => [$line($marks(['F', 'C', 'G', 'Am7']))]]]]]);

        $this->actingAs($this->admin())->putJson(route('songs.bars.update', $song), [
            'blocks' => [['section' => 'CHORUS', 'chords' => ['G', 'D', 'A', 'Bm7'], 'durations' => [4, 4, 4, 4], 'beats_per_bar' => 4, 'start' => '0:00']],
            'updated_at' => $song->updated_at->toIso8601String(),
            'bpm' => 170,
            'musical_key' => 'D',
        ])->assertOk();

        $this->assertSame($line($marks(['G', 'D', 'A', 'Bm7'])), $song->fresh()->chord_sheet['sections'][0]['lines'][0]);
    }

    public function test_changing_a_chord_without_a_new_key_still_follows_it_by_name(): void
    {
        $song = Song::create(['title' => 'Teste', 'artist' => 'Teste', 'bpm' => 170, 'musical_key' => 'C',
            'structure' => [['section' => 'CHORUS', 'chords' => ['F', 'C', 'G'], 'durations' => [4, 4, 4], 'beats_per_bar' => 4, 'start' => '0:00']],
            'chord_sheet' => ['sections' => [['section' => 'CHORUS', 'lines' => ['[F]um [C]dois [G]tres']]]]]);

        $this->actingAs($this->admin())->putJson(route('songs.bars.update', $song), [
            'blocks' => [['section' => 'CHORUS', 'chords' => ['F', 'G'], 'durations' => [4, 8], 'beats_per_bar' => 4, 'start' => '0:00']],
            'updated_at' => $song->updated_at->toIso8601String(),
            'bpm' => 170,
            'musical_key' => 'C',
        ])->assertOk();

        $this->assertSame('[F]um dois [G]tres', $song->fresh()->chord_sheet['sections'][0]['lines'][0]);
    }
}
