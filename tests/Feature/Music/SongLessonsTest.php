<?php

namespace Tests\Feature\Music;

use App\Modules\Music\Models\SongLesson;
use Illuminate\Http\UploadedFile;

class SongLessonsTest extends MusicTestCase
{
    private function lessonFile(SongLesson $lesson): string
    {
        return public_path('lessons/' . $lesson->file_path);
    }

    public function test_a_youtube_lesson_is_saved_and_needs_its_link(): void
    {
        $song = $this->song();
        $this->actingAs($this->admin());

        $this->post("/songs/{$song->id}/lessons", ['type' => 'youtube', 'instrument' => 'Bass', 'title' => 'Riff'])->assertSessionHasErrors('video_url');
        $this->post("/songs/{$song->id}/lessons", ['type' => 'youtube', 'instrument' => 'Bass', 'title' => 'Riff', 'video_url' => 'https://www.youtube.com/@channel'])->assertSessionHasErrors('video_url');
        $this->post("/songs/{$song->id}/lessons", ['type' => 'youtube', 'instrument' => 'Bass', 'title' => 'Riff', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertRedirect();

        $lesson = $song->lessons()->sole();
        $this->assertSame(['youtube', 'Bass', 'https://youtu.be/dQw4w9WgXcQ', false], [$lesson->type, $lesson->instrument, $lesson->video_url, $lesson->contributed]);
    }

    public function test_a_file_lesson_needs_a_file_of_its_type(): void
    {
        $song = $this->song();
        $this->actingAs($this->admin());

        $this->post("/songs/{$song->id}/lessons", ['type' => 'audio', 'instrument' => 'Keys', 'title' => 'Pad'])->assertSessionHasErrors('file');
        $this->post("/songs/{$song->id}/lessons", ['type' => 'audio', 'instrument' => 'Keys', 'title' => 'Pad', 'file' => UploadedFile::fake()->create('score.pdf', 10)])->assertSessionHasErrors('file');
        $this->post("/songs/{$song->id}/lessons", ['type' => 'audio', 'instrument' => 'Keys', 'title' => 'Pad', 'file' => UploadedFile::fake()->create('pad.mp3', 10)])->assertRedirect();

        $lesson = $song->lessons()->sole();
        $this->assertSame('pad.mp3', $lesson->file_name);
        $this->assertFileExists($this->lessonFile($lesson));
    }

    public function test_editing_keeps_the_file_and_changing_the_type_removes_it(): void
    {
        $song = $this->song();
        $this->actingAs($this->admin());
        $this->post("/songs/{$song->id}/lessons", ['type' => 'audio', 'instrument' => 'Keys', 'title' => 'Pad', 'file' => UploadedFile::fake()->create('pad.mp3', 10)]);
        $lesson = $song->lessons()->sole();
        $file = $this->lessonFile($lesson);
        $this->putJson("/songs/{$song->id}/lessons/{$lesson->id}/loops", ['loops' => [['start' => 1, 'end' => 3, 'label' => 'Intro']]])->assertOk();

        $this->put("/songs/{$song->id}/lessons/{$lesson->id}", ['type' => 'audio', 'instrument' => 'Keys', 'title' => 'Pad 2'])->assertRedirect();
        $this->assertFileExists($file);
        $this->assertSame('Pad 2', $lesson->fresh()->title);

        $this->put("/songs/{$song->id}/lessons/{$lesson->id}", ['type' => 'youtube', 'instrument' => 'Keys', 'title' => 'Pad 2', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertRedirect();
        $this->assertFileDoesNotExist($file);
        $this->assertNull($lesson->fresh()->file_path);
        $this->assertNull($lesson->fresh()->loops);
    }

    public function test_deleting_a_lesson_removes_its_file(): void
    {
        $song = $this->song();
        $this->actingAs($this->admin());
        $this->post("/songs/{$song->id}/lessons", ['type' => 'pdf', 'instrument' => 'Bass', 'title' => 'Score', 'file' => UploadedFile::fake()->create('score.pdf', 10)]);
        $lesson = $song->lessons()->sole();
        $file = $this->lessonFile($lesson);

        $this->delete("/songs/{$song->id}/lessons/{$lesson->id}")->assertRedirect();

        $this->assertModelMissing($lesson);
        $this->assertFileDoesNotExist($file);
    }

    public function test_the_team_saves_the_stretches_of_a_video_or_audio_only(): void
    {
        $song = $this->song();
        $video = $song->lessons()->create(['instrument' => 'Bass', 'title' => 'Riff', 'type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $pdf = $song->lessons()->create(['instrument' => 'Bass', 'title' => 'Score', 'type' => 'pdf']);
        $this->actingAs($this->admin());

        $this->putJson("/songs/{$song->id}/lessons/{$video->id}/loops", ['loops' => [['start' => 40, 'end' => 44.5, 'label' => 'Fill'], ['start' => 10, 'end' => 18]]])
            ->assertOk()
            ->assertJsonPath('loops.0.label', '0:10–0:18')
            ->assertJsonPath('loops.1.label', 'Fill');
        $this->putJson("/songs/{$song->id}/lessons/{$pdf->id}/loops", ['loops' => []])->assertNotFound();
    }

    public function test_a_lesson_of_another_song_is_not_found(): void
    {
        $song = $this->song();
        $other = $this->song('Other');
        $lesson = $other->lessons()->create(['instrument' => 'Bass', 'title' => 'Riff', 'type' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->actingAs($this->admin())->delete("/songs/{$song->id}/lessons/{$lesson->id}")->assertNotFound();
        $this->assertModelExists($lesson);
    }
}
