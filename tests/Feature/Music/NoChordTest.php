<?php

namespace Tests\Feature\Music;

/**
 * Silence (N.C.): in the map and in the chord sheet's data, but not shown on the chord sheet.
 */
class NoChordTest extends MusicTestCase
{
    public function test_the_song_page_shows_no_silence_on_the_chord_sheet(): void
    {
        $song = $this->song();
        $song->chord_sheet = ['sections' => [['section' => 'INTRO', 'lines' => ['[N.C.]', '[N.C.]Santo é o [G]Senhor']]]];
        $song->save();

        $this->actingAs($this->admin())->get(route('songs.show', $song))->assertOk()
            ->assertDontSee('N.C.')->assertSee('Santo é o');
    }
}
