<?php

namespace Tests\Feature\Music;

use App\Models\User;
use App\Modules\Music\Models\Setlist;
use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\SaveSetlistService;
use App\Modules\Music\Services\SaveSongService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Feature tests of the music module, on the test database (icd_test, emptied on every run; see tests/TestCase.php).
 * Uploaded files go to a temporary public folder (removed after each test) that links to everything else of the real
 * one (scripts, built assets…), so the pages render as usual.
 */
abstract class MusicTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $real = public_path();
        $this->publicPath = sys_get_temp_dir() . '/icd-public-' . uniqid();
        File::ensureDirectoryExists($this->publicPath);
        foreach (File::glob("{$real}/*") as $entry) {
            if (! in_array(basename($entry), ['lessons', 'audio'], true)) {
                symlink($entry, "{$this->publicPath}/" . basename($entry));
            }
        }
        $this->app->usePublicPath($this->publicPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicPath);
        parent::tearDown();
    }

    protected function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * A song with bars: INTRO (G D) and CHORUS (C G), a bar per chord at 60 BPM, and its chord sheet.
     */
    protected function song(string $title = 'Test song'): Song
    {
        $structure = [
            ['section' => 'INTRO', 'chords' => ['G', 'D'], 'durations' => [4, 4], 'beats_per_bar' => 4, 'start' => '0:02'],
            ['section' => 'CHORUS', 'chords' => ['C', 'G'], 'durations' => [4, 4], 'beats_per_bar' => 4],
        ];
        $song = app(SaveSongService::class)->execute(['title' => $title, 'artist' => 'Artist', 'bpm' => 60, 'musical_key' => 'G', 'structure' => json_encode($structure)]);
        $song->chord_sheet = ['sections' => [['section' => 'INTRO', 'lines' => ['[G] [D]']], ['section' => 'CHORUS', 'lines' => ['[C]Santo [G]santo']]]];
        $song->save();

        return $song->fresh();
    }

    protected function setlist(Song ...$songs): Setlist
    {
        return app(SaveSetlistService::class)->execute([
            'title'      => 'Sunday',
            // Still to come: the public page changes it (see EnsureSetlistNotPast)
            'event_date' => now()->addWeek()->toDateString(),
            'songs'      => array_map(fn (Song $song) => ['song_id' => $song->id, 'musical_key' => 'G'], $songs),
        ]);
    }
}
