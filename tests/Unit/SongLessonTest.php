<?php

namespace Tests\Unit;

use App\Http\Requests\ContributeSongLessonRequest;
use App\Http\Requests\SongLessonRequest;
use App\Models\SongLesson;
use App\Services\ContributeLessonLoopService;
use App\Services\ContributeSongLessonService;
use App\Services\ListSongLessonsService;
use App\Services\SaveSongLessonLoopsService;
use App\Services\SaveSongLessonService;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SongLessonTest extends TestCase
{
    public function test_loops_are_cleaned_labelled_and_ordered(): void
    {
        $loops = SaveSongLessonLoopsService::clean([
            ['start' => 30.123, 'end' => 34.5, 'label' => '  Refrão  '],
            ['start' => 12, 'end' => 20, 'label' => ''],
            ['start' => 5, 'end' => 5.1],
            ['start' => 'x', 'end' => 3],
        ]);

        $this->assertSame([
            ['start' => 12.0, 'end' => 20.0, 'label' => '0:12–0:20'],
            ['start' => 30.12, 'end' => 34.5, 'label' => 'Refrão'],
        ], $loops);
    }

    public function test_the_payload_shows_only_what_the_type_has(): void
    {
        $lesson = new SongLesson(['instrument' => 'Baixo', 'title' => 'Riff', 'type' => 'pdf', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'loops' => [['start' => 1, 'end' => 2, 'label' => 'x']]]);

        $this->assertNull($lesson->payload()['video_url']);
        $this->assertSame([], $lesson->payload()['loops']);
        $this->assertTrue(SongLesson::isFileType('audio'));
        $this->assertFalse(SongLesson::isFileType('youtube'));
    }

    public function test_lessons_are_grouped_by_instrument_in_their_order(): void
    {
        $make = function (int $id, string $instrument, int $position): SongLesson {
            $lesson = new SongLesson(['instrument' => $instrument, 'title' => "Aula $id", 'type' => 'youtube', 'position' => $position]);
            $lesson->id = $id;

            return $lesson;
        };

        $groups = ListSongLessonsService::group([$make(1, 'Teclado', 2), $make(2, 'Baixo', 1), $make(3, 'Teclado', 1)]);

        $this->assertSame(['Baixo', 'Teclado'], array_column($groups, 'instrument'));
        $this->assertSame([3, 1], array_column($groups[1]['lessons'], 'id'));
        $this->assertSame([], $groups[0]['lessons'][0]['loops']);
    }

    public function test_only_youtube_links_are_accepted(): void
    {
        $rules = (new SongLessonRequest())->rules();
        $valid = fn (array $data) => Validator::make(['instrument' => 'Baixo', 'title' => 'Riff', 'type' => 'youtube', ...$data], $rules)->passes();

        $this->assertTrue($valid(['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']));
        $this->assertTrue($valid(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']));
        $this->assertFalse($valid(['video_url' => 'https://vimeo.com/123']));
        $this->assertFalse($valid(['title' => '', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']));
        // A YouTube lesson needs its link; another type does not.
        $this->assertFalse($valid([]));
        $this->assertTrue($valid(['type' => 'pdf']));
        $this->assertFalse($valid(['type' => 'video', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']));
    }

    public function test_the_youtube_video_id_of_a_link(): void
    {
        $this->assertSame('dQw4w9WgXcQ', ContributeSongLessonService::youtubeId('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=40'));
        $this->assertSame('dQw4w9WgXcQ', ContributeSongLessonService::youtubeId('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', ContributeSongLessonService::youtubeId('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
        $this->assertNull(ContributeSongLessonService::youtubeId('https://www.youtube.com/@canal'));
    }

    public function test_a_contribution_needs_a_youtube_video(): void
    {
        $rules = (new ContributeSongLessonRequest())->rules();
        $valid = fn (array $data) => Validator::make(['instrument' => 'Baixo', ...$data], $rules)->passes();

        $this->assertTrue($valid(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']));
        $this->assertFalse($valid(['video_url' => 'https://www.youtube.com/@canal']));
        $this->assertFalse($valid(['video_url' => 'https://vimeo.com/123']));
        $this->assertFalse($valid([]));
    }

    public function test_a_musician_stretch_is_added_for_everyone_once(): void
    {
        $lesson = new class extends SongLesson {
            public int $saves = 0;

            public function save(array $options = []): bool
            {
                $this->saves++;

                return true;
            }
        };
        $lesson->fill(['instrument' => 'Baixo', 'title' => 'Riff', 'type' => 'youtube', 'loops' => [['start' => 10, 'end' => 18, 'label' => 'Intro']]]);
        $service = new ContributeLessonLoopService();

        $service->execute($lesson, 42, 50.5, 'Solo');
        $service->execute($lesson, 42.01, 50.5, 'Solo de novo');

        $this->assertEquals([
            ['start' => 10.0, 'end' => 18.0, 'label' => 'Intro'],
            ['start' => 42.0, 'end' => 50.5, 'label' => 'Solo', 'contributed' => true],
        ], $lesson->loops);
        $this->assertSame(1, $lesson->saves);
        // The team saving every stretch keeps the musician's mark.
        $this->assertTrue(SaveSongLessonLoopsService::clean($lesson->loops)[1]['contributed']);
    }
}
