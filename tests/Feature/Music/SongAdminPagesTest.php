<?php

namespace Tests\Feature\Music;

class SongAdminPagesTest extends MusicTestCase
{
    public function test_the_music_pages_need_a_login(): void
    {
        $song = $this->song();

        foreach (['/songs', "/songs/{$song->id}", "/songs/{$song->id}/layout", "/songs/{$song->id}/lessons", '/setlists', '/song-suggestions'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_every_music_page_opens(): void
    {
        $song = $this->song();
        $setlist = $this->setlist($song);
        $this->actingAs($this->admin());

        foreach ([
            '/songs', '/songs/create', "/songs/{$song->id}", "/songs/{$song->id}/edit", "/songs/{$song->id}/layout", "/songs/{$song->id}/sheet",
            "/songs/{$song->id}/lessons", "/songs/{$song->id}/practice", "/songs/{$song->id}/history", '/songs/bookmarklet', '/song-suggestions',
            '/setlists', '/setlists/create', "/setlists/{$setlist->id}/edit", '/setlists/import',
            "/setlists/{$setlist->id}/practice/{$song->id}",
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_the_songs_list_counts_and_sorts_by_lessons(): void
    {
        $few = $this->song('Few lessons');
        $many = $this->song('Many lessons');
        foreach ([$many, $many, $few] as $song) {
            $song->lessons()->create(['instrument' => 'Bass', 'title' => 'Riff', 'type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        }

        $this->actingAs($this->admin())
            ->get('/songs?sort=lessons&direction=desc')
            ->assertOk()
            ->assertSeeInOrder(['Many lessons', 'Few lessons']);
    }

    public function test_the_bars_keep_free_timing_and_marked_starts(): void
    {
        $song = $this->song();
        $blocks = [
            ['section' => 'INTRO', 'chords' => ['G', 'D'], 'durations' => [4, 4], 'beats_per_bar' => 4, 'start' => '0:02', 'free_timing' => true],
            ['section' => 'CHORUS', 'chords' => ['C', 'G'], 'durations' => [4, 4], 'beats_per_bar' => 4, 'start' => '0:11.5', 'free_timing' => true, 'free_start' => '0:11.537'],
        ];

        $this->actingAs($this->admin())
            ->putJson("/songs/{$song->id}/bars", ['blocks' => $blocks, 'updated_at' => $song->updated_at->toIso8601String()])
            ->assertOk();

        $structure = json_decode(json_encode($song->fresh()->structure), true);
        $this->assertTrue($structure[0]['free_timing']);
        $this->assertSame('0:11.54', $structure[1]['free_start']);
    }

    public function test_a_block_of_unknown_chords_is_saved(): void
    {
        $song = $this->song();
        $blocks = [
            ['section' => 'INTRO', 'chords' => ['G', 'D'], 'durations' => [4, 4], 'beats_per_bar' => 4, 'start' => '0:02'],
            ['section' => 'BRIDGE', 'chords' => ['?', '?', '?'], 'durations' => [4, 4, 4], 'beats_per_bar' => 4],
        ];

        $this->actingAs($this->admin())
            ->putJson("/songs/{$song->id}/bars", ['blocks' => $blocks, 'updated_at' => $song->updated_at->toIso8601String()])
            ->assertOk();

        $structure = json_decode(json_encode($song->fresh()->structure), true);
        $this->assertSame(['?', '?', '?'], $structure[1]['chords']);
        $this->assertSame([4, 4, 4], $structure[1]['durations']);
    }

    public function test_the_song_picker_offers_the_next_setlist_on_the_same_tab(): void
    {
        $song = $this->song();
        $other = $this->song('Other song');
        $this->setlist($other);

        $page = $this->actingAs($this->admin())
            ->get("/songs/{$song->id}/layout")
            ->assertOk()
            ->assertSee('song-switcher', false)
            ->assertSee('Sunday')
            ->assertSee('Other song');

        // The other songs open on the same tab (the address is escaped for JavaScript: "\/").
        $this->assertMatchesRegularExpression('#songs[\\\\/]+__SONG__[\\\\/]+layout#', $page->getContent());
    }
}
