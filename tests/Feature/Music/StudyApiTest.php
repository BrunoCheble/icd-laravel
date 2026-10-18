<?php

namespace Tests\Feature\Music;

/**
 * The API of the study app (icd-chords), behind the STUDY_API_TOKEN token.
 */
class StudyApiTest extends MusicTestCase
{
    private function token(): array
    {
        config(['services.study.token' => 'test-token']);

        return ['Authorization' => 'Bearer test-token'];
    }

    public function test_the_api_is_closed_without_a_valid_token(): void
    {
        $song = $this->song();

        config(['services.study.token' => '']);
        $this->getJson('/api/study/songs')->assertStatus(503);

        config(['services.study.token' => 'test-token']);
        $this->getJson('/api/study/songs')->assertUnauthorized();
        $this->getJson("/api/study/songs/{$song->id}", ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
    }

    public function test_the_songs_and_a_song_map_are_read(): void
    {
        $song = $this->song('Holy');
        $headers = $this->token();

        $this->getJson('/api/study/songs', $headers)->assertOk()->assertJsonFragment(['title' => 'Holy']);
        $this->getJson("/api/study/songs/{$song->id}", $headers)
            ->assertOk()
            ->assertJsonPath('structure.0.section', 'INTRO')
            ->assertJsonPath('structure.1.chords', ['C', 'G']);
    }

    public function test_a_map_saved_by_the_study_app_keeps_free_timing(): void
    {
        $song = $this->song();
        $headers = $this->token();
        $blocks = [
            ['section' => 'INTRO', 'chords' => ['G', 'D'], 'durations' => [4, 4], 'beats_per_bar' => 4, 'start' => '0:02', 'free_timing' => true],
            ['section' => 'CHORUS', 'chords' => ['C', 'G'], 'durations' => [4, 4], 'beats_per_bar' => 4, 'start' => '0:12', 'free_timing' => true, 'free_start' => '0:12.25'],
        ];

        $this->putJson("/api/study/songs/{$song->id}/map", ['blocks' => $blocks, 'updated_at' => $song->updated_at->toIso8601String(), 'keep_sheet' => true], $headers)->assertOk();

        $structure = json_decode(json_encode($song->fresh()->structure), true);
        $this->assertTrue($structure[1]['free_timing']);
        $this->assertSame('0:12.25', $structure[1]['free_start']);
    }
}
