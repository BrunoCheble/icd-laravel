<?php

namespace Tests\Feature\Music;

class PublicSetlistTest extends MusicTestCase
{
    public function test_the_setlist_page_shows_its_songs_and_lessons(): void
    {
        $song = $this->song('Holy');
        $song->lessons()->create(['instrument' => 'Bass', 'title' => 'Bass riff', 'type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $setlist = $this->setlist($song);

        $this->get("/repertoire/{$setlist->id}")->assertOk()->assertSee('Holy')->assertSee('Bass riff');
        $this->get('/repertoire/songs/search?q=Hol')->assertOk();
        $this->get('/repertoire/songs/catalog')->assertOk();
    }

    public function test_a_musician_sends_a_youtube_video_once(): void
    {
        $song = $this->song();
        $setlist = $this->setlist($song);
        $url = "/repertoire/{$setlist->id}/songs/{$song->id}/lessons";

        $this->postJson($url, ['instrument' => 'Drums', 'video_url' => 'https://youtu.be/9bZkp7q19f0'])
            ->assertCreated()
            ->assertJsonPath('contributed', true)
            ->assertJsonPath('type', 'youtube');
        $this->postJson($url, ['instrument' => 'Drums', 'video_url' => 'https://www.youtube.com/watch?v=9bZkp7q19f0'])->assertOk();
        $this->postJson($url, ['instrument' => 'Drums', 'video_url' => 'https://www.youtube.com/@channel'])->assertUnprocessable();

        $this->assertSame(1, $song->lessons()->count());
    }

    public function test_a_musician_cannot_change_or_remove_lessons(): void
    {
        $song = $this->song();
        $setlist = $this->setlist($song);
        $lesson = $song->lessons()->create(['instrument' => 'Bass', 'title' => 'Riff', 'type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'contributed' => true]);

        $this->putJson("/repertoire/{$setlist->id}/songs/{$song->id}/lessons/{$lesson->id}", ['title' => 'x'])->assertStatus(404);
        $this->deleteJson("/repertoire/{$setlist->id}/songs/{$song->id}/lessons/{$lesson->id}")->assertStatus(404);
        $this->deleteJson("/songs/{$song->id}/lessons/{$lesson->id}")->assertUnauthorized();
        $this->assertModelExists($lesson);
    }

    public function test_a_musician_saves_a_stretch_for_everyone_once(): void
    {
        $song = $this->song();
        $setlist = $this->setlist($song);
        $video = $song->lessons()->create(['instrument' => 'Bass', 'title' => 'Riff', 'type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $pdf = $song->lessons()->create(['instrument' => 'Bass', 'title' => 'Score', 'type' => 'pdf']);
        $url = fn ($lesson) => "/repertoire/{$setlist->id}/songs/{$song->id}/lessons/{$lesson->id}/loops";

        $this->postJson($url($video), ['start' => 62.3, 'end' => 70.1, 'label' => 'Solo'])
            ->assertOk()
            ->assertJsonPath('loops.0.label', 'Solo')
            ->assertJsonPath('loops.0.contributed', true);
        $this->postJson($url($video), ['start' => 62.3, 'end' => 70.1, 'label' => 'Solo again'])->assertOk();
        $this->postJson($url($video), ['start' => 9, 'end' => 3])->assertUnprocessable();
        $this->postJson($url($pdf), ['start' => 1, 'end' => 3])->assertNotFound();

        $this->assertCount(1, $video->fresh()->loops);
    }

    public function test_only_songs_of_the_setlist_take_contributions(): void
    {
        $song = $this->song();
        $other = $this->song('Not in the setlist');
        $setlist = $this->setlist($song);

        $this->postJson("/repertoire/{$setlist->id}/songs/{$other->id}/lessons", ['instrument' => 'Bass', 'video_url' => 'https://youtu.be/9bZkp7q19f0'])->assertNotFound();
    }

    public function test_the_band_tempo_and_key_are_saved_in_the_setlist(): void
    {
        $song = $this->song();
        $setlist = $this->setlist($song);
        $base = "/repertoire/{$setlist->id}/songs/{$song->id}";

        $this->patchJson("$base/bpm", ['bpm' => 76])->assertNoContent();
        $this->patchJson("$base/key", ['musical_key' => 'A'])->assertNoContent();
        $this->patchJson("$base/bpm", ['bpm' => 5])->assertUnprocessable();

        $pivot = $setlist->songs()->whereKey($song->id)->first()->pivot;
        $this->assertEquals(76, $pivot->bpm);
        $this->assertSame('A', $pivot->musical_key);
        $this->assertEquals(60, $song->fresh()->bpm);
    }

    public function test_after_its_day_a_setlist_is_only_seen_but_takes_chord_suggestions(): void
    {
        $song = $this->song();
        $other = $this->song('Outra');
        $setlist = $this->setlist($song);
        $setlist->update(['event_date' => '2026-10-20']);
        $base = "/repertoire/{$setlist->id}";

        // The day itself (Lisbon time) still takes changes
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-20 23:30', 'Europe/Lisbon'));
        $this->assertFalse($setlist->fresh()->isPast());
        $this->patchJson("$base/songs/{$song->id}/bpm", ['bpm' => 70])->assertNoContent();

        // The day after: nothing changes, only to be seen
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-21 00:10', 'Europe/Lisbon'));
        $this->get($base)->assertOk()->assertSee('only to be viewed');
        $this->assertTrue(app(\App\Modules\Music\Services\GetPublicSetlistService::class)->execute($setlist->fresh())['locked']);
        foreach ([
            fn () => $this->postJson("$base/songs", ['song_id' => $other->id]),
            fn () => $this->deleteJson("$base/songs/{$song->id}"),
            fn () => $this->patchJson("$base/songs/{$song->id}/key", ['musical_key' => 'A']),
            fn () => $this->patchJson("$base/songs/{$song->id}/bpm", ['bpm' => 80]),
            fn () => $this->putJson("$base/order", ['songs' => [$song->id]]),
            fn () => $this->postJson("$base/songs/{$song->id}/lessons", ['instrument' => 'Bass', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']),
        ] as $change) {
            $change()->assertForbidden()->assertJsonPath('message', 'This setlist already took place: it can only be viewed.');
        }
        $this->assertSame(1, $setlist->songs()->count());
        $this->assertEquals(70, $setlist->songs()->first()->pivot->bpm);

        // Chord suggestions are still taken
        $this->assertNotSame(403, $this->postJson("$base/songs/{$song->id}/suggestions", ['changes' => [['n' => 0, 'from' => 'G', 'to' => 'A']]])->status());
    }
}
