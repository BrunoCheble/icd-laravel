<?php

namespace Tests\Feature\Music;

use App\Modules\Music\Models\Song;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * On a local computer, a song's YouTube link is sent to icd-chords (POST /api/links) to download its audio: when the
 * song is created, and when its link changes or it has no audio yet. Never in production.
 */
class SongAudioDownloadTest extends MusicTestCase
{
    private const HELPER = 'http://host.docker.internal:8765';

    protected function setUp(): void
    {
        parent::setUp();
        // These tests switch to the local environment, where the CSRF check is not skipped as in tests.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config(['services.chords_helper.internal_url' => self::HELPER]);
        Http::fake([self::HELPER . '/*' => Http::response(['id' => 'download-1'], 202)]);
    }

    private function form(array $data = []): array
    {
        return $data + [
            'title' => 'ZZ Teste Download', 'artist' => 'Artist', 'musical_key' => 'G', 'bpm' => 80,
            'youtube_url' => 'https://www.youtube.com/watch?v=Mpn73TBHfJw',
            'structure' => json_encode([['section' => 'INTRO', 'chords' => ['G'], 'durations' => [4], 'beats_per_bar' => 4]]),
        ];
    }

    public function test_a_new_song_s_link_is_sent_on_a_local_computer(): void
    {
        $this->app['env'] = 'local';
        $this->actingAs($this->admin())->post('/songs', $this->form())->assertRedirect('/songs')
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'icd-chords'));

        $song = Song::where('title', 'ZZ Teste Download')->firstOrFail();
        Http::assertSent(fn (Request $request) => $request->url() === self::HELPER . '/api/links'
            && $request['id'] === $song->id && $request['link'] === 'https://www.youtube.com/watch?v=Mpn73TBHfJw');
    }

    public function test_never_in_production_nor_without_a_link(): void
    {
        $this->app['env'] = 'production';
        $this->actingAs($this->admin())->post('/songs', $this->form())->assertRedirect('/songs');

        $this->app['env'] = 'local';
        $this->actingAs($this->admin())->post('/songs', $this->form(['title' => 'ZZ Teste Sem link', 'youtube_url' => null]))->assertRedirect('/songs');

        config(['services.chords_helper.internal_url' => null]);
        $this->actingAs($this->admin())->post('/songs', $this->form(['title' => 'ZZ Teste Sem helper']))->assertRedirect('/songs');

        Http::assertNothingSent();
    }

    public function test_an_edit_sends_a_new_link_or_a_song_without_audio_only(): void
    {
        $this->app['env'] = 'local';
        $song = $this->song();
        $admin = $this->admin();
        $form = fn (array $data = []) => $this->form(['title' => $song->title, 'source_url' => null] + $data);

        // No audio yet: sent
        $this->actingAs($admin)->put("/songs/{$song->id}", $form())->assertRedirect('/songs');
        Http::assertSentCount(1);

        // Same link and the song has audio: not sent again
        $song->forceFill(['audio_path' => 'zz.mp3'])->save();
        $this->actingAs($admin)->put("/songs/{$song->id}", $form())->assertRedirect('/songs');
        Http::assertSentCount(1);

        // A new link: sent
        $this->actingAs($admin)->put("/songs/{$song->id}", $form(['youtube_url' => 'https://youtu.be/dQw4w9WgXcQ']))->assertRedirect('/songs');
        Http::assertSentCount(2);

        // The audio just removed: not sent
        $this->actingAs($admin)->put("/songs/{$song->id}", $form(['youtube_url' => 'https://youtu.be/aaaaaaaaaaa', 'remove_audio' => '1']))->assertRedirect('/songs');
        Http::assertSentCount(2);
    }

    public function test_a_helper_that_is_not_running_does_not_stop_the_save(): void
    {
        $this->app['env'] = 'local';
        Http::fake([self::HELPER . '/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('refused')]);

        $this->actingAs($this->admin())->post('/songs', $this->form())->assertRedirect('/songs')
            ->assertSessionHas('success', fn ($message) => ! str_contains($message, 'icd-chords'));
        $this->assertTrue(Song::where('title', 'ZZ Teste Download')->exists());
    }

    public function test_a_local_computer_plays_production_s_audio_file_when_it_is_not_here(): void
    {
        $song = $this->song();
        $song->forceFill(['audio_path' => 'zz teste.mp3'])->save();
        config(['services.music.audio_fallback_url' => 'https://admin.igrejaicd.pt/']);

        // Production (or any environment but local): only its own files
        $this->assertNull($song->audioUrl());

        $this->app['env'] = 'local';
        $this->assertSame('https://admin.igrejaicd.pt/audio/songs/zz%20teste.mp3', $song->audioUrl());

        // The file here: this one
        \Illuminate\Support\Facades\File::ensureDirectoryExists(public_path('audio/songs'));
        file_put_contents(public_path('audio/songs/zz teste.mp3'), 'x');
        $this->assertStringStartsWith(url('/'), $song->audioUrl());
    }
}
