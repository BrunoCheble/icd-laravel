<?php

namespace Tests\Feature\Music;

use App\Modules\Music\Models\Song;
use Illuminate\Http\UploadedFile;

/**
 * A MusicXML score read as the song's grid of bars (preview), then saved like the grid.
 */
class ScoreImportTest extends MusicTestCase
{
    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/zz-score.musicxml'));
    }

    /** An ".mxl": the score zipped (deflated), with META-INF/container.xml pointing to it. */
    private function mxl(string $xml): string
    {
        $files = ['META-INF/container.xml' => '<container><rootfiles><rootfile full-path="score.xml"/></rootfiles></container>', 'score.xml' => $xml];
        $zip = '';
        $central = '';
        foreach ($files as $name => $data) {
            $packed = gzdeflate($data);
            $local = strlen($zip);
            $zip .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, 0, 0, crc32($data), strlen($packed), strlen($data), strlen($name), 0) . $name . $packed;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, 0, 0, crc32($data), strlen($packed), strlen($data), strlen($name), 0, 0, 0, 0, 0, $local) . $name;
        }

        return $zip . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), strlen($zip), 0);
    }

    private function read(Song $song, string $content, string $name = 'zz.musicxml')
    {
        return $this->actingAs($this->admin())->post(route('songs.score.read', $song), [
            'score' => UploadedFile::fake()->createWithContent($name, $content),
        ], ['Accept' => 'application/json']);
    }

    public function test_a_score_is_read_as_blocks_of_bars_with_repeats_and_an_anacrusis(): void
    {
        $response = $this->read($this->song(), $this->fixture())->assertOk();

        $response->assertJson(['title' => 'ZZ Teste Partitura', 'bpm' => 80, 'meter' => '4/4', 'bars' => 7]);
        [$intro, $part] = $response->json('blocks');
        $this->assertCount(2, $response->json('blocks'));
        // "Can-te-" is sung after the rest at the end of bar 2: it belongs to the next block.
        $this->assertSame(['INTRO', 2, ['C', 'G4', 'Am7'], [2, 2, 4]], [$intro['section'], $intro['bars'], $intro['chords'], $intro['durations']]);
        // The repeat played twice, then the last bar (no lyrics of its own) with it.
        $this->assertSame('VERSE_1', $part['section']);
        $this->assertSame(5, $part['bars']);
        $this->assertSame('3–4, 3–5', $part['measures']);
        $this->assertSame(['F', 'G7', 'F', 'G7', 'C'], array_values(array_filter($part['chords'], fn ($chord) => $chord !== '|')));
        $this->assertSame(20, array_sum($part['durations']));
        $this->assertStringStartsWith('Cantemos a Deus Aleluia', $part['lyrics']);
    }

    public function test_the_blocks_take_the_name_and_lyrics_of_the_songs_blocks(): void
    {
        $song = $this->song();
        $song->structure = [['section' => 'VERSE_1', 'chords' => ['F'], 'durations' => [4], 'beats_per_bar' => 4, 'lyrics' => "Cantemos a Deus,\nAleluia!", 'anchor' => 'Cantemos a Deus']];
        $song->save();

        $response = $this->read($song, $this->mxl($this->fixture()), 'zz.mxl')->assertOk();

        $this->assertSame(['INTRO', 'VERSE_1'], array_column($response->json('blocks'), 'section'));
        $this->assertSame("Cantemos a Deus,\nAleluia!", $response->json('blocks.1.lyrics'));
        $this->assertTrue($response->json('blocks.1.known'));
    }

    public function test_the_preview_is_saved_like_the_grid_and_the_map_before_stays_in_the_history(): void
    {
        $song = $this->song();
        $revisions = $song->revisions()->count();
        $blocks = collect($this->read($song, $this->fixture())->json('blocks'))
            ->map(fn ($block) => array_intersect_key($block, array_flip(['section', 'chords', 'durations', 'beats_per_bar', 'lyrics', 'anchor'])))
            ->all();
        $blocks[0]['start'] = '0:02';

        $this->actingAs($this->admin())->putJson(route('songs.bars.update', $song), [
            'blocks' => $blocks, 'updated_at' => $song->updated_at->toIso8601String(), 'bpm' => 80, 'musical_key' => 'G',
        ])->assertOk();

        $song->refresh();
        $structure = json_decode(json_encode($song->structure), true);
        $this->assertSame(80.0, (float) $song->bpm);
        $this->assertSame(['INTRO', 'VERSE_1'], array_column($structure, 'section'));
        $this->assertEquals([2, 2, 4], $structure[0]['durations']);
        $this->assertSame('0:02', $structure[0]['start']);
        $this->assertSame($revisions + 1, $song->revisions()->count());
    }

    public function test_what_is_not_a_score_with_chords_is_refused(): void
    {
        $song = $this->song();
        $this->read($song, '<html>not a score</html>')->assertStatus(422)->assertJsonPath('message', fn ($message) => str_contains($message, 'MusicXML'));
        $this->read($song, 'x', 'zz.pdf')->assertStatus(422)->assertJsonValidationErrors('score');

        $noChords = preg_replace('#<harmony>.*?</harmony>#s', '', $this->fixture());
        $this->read($song, $noChords)->assertStatus(422);
    }
}
