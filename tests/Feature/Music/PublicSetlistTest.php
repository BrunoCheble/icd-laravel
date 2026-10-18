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
}
