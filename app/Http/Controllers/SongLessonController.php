<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongLessonRequest;
use App\Models\Song;
use App\Models\SongLesson;
use App\Models\SongVersion;
use App\Services\DeleteSongLessonService;
use App\Services\ListSongLessonsService;
use App\Services\SaveSongAudioService;
use App\Services\SaveSongLessonLoopsService;
use App\Services\SaveSongLessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SongLessonController extends Controller
{
    /**
     * The lessons tab of a song: its lessons grouped by instrument, each one's video or audio with stretches to repeat.
     */
    public function index(Song $song, ListSongLessonsService $list): View
    {
        $groups = $list->execute($song);
        $instruments = SongVersion::instrumentNames();
        $maxMegabytes = SaveSongAudioService::maxUploadMegabytes();

        return view('songs.lessons', compact('song', 'groups', 'instruments', 'maxMegabytes'));
    }

    public function store(SongLessonRequest $request, Song $song, SaveSongLessonService $service): RedirectResponse
    {
        $lesson = $service->execute($song, $request->validated(), $request->file('file'));

        return Redirect::to(route('songs.lessons.index', $song) . '#lesson-' . $lesson->id)->with('success', __('Lesson saved.'));
    }

    public function update(SongLessonRequest $request, Song $song, SongLesson $lesson, SaveSongLessonService $service): RedirectResponse
    {
        abort_unless($lesson->song_id === $song->id, 404);
        $service->execute($song, $request->validated(), $request->file('file'), $lesson);

        return Redirect::to(route('songs.lessons.index', $song) . '#lesson-' . $lesson->id)->with('success', __('Lesson saved.'));
    }

    public function destroy(Song $song, SongLesson $lesson, DeleteSongLessonService $service): RedirectResponse
    {
        abort_unless($lesson->song_id === $song->id, 404);
        $service->execute($lesson);

        return Redirect::route('songs.lessons.index', $song)->with('success', __('Lesson deleted.'));
    }

    /**
     * Saves the stretches to repeat of the lesson's video or audio (marked while it plays).
     */
    public function loops(Request $request, Song $song, SongLesson $lesson, SaveSongLessonLoopsService $service): JsonResponse
    {
        abort_unless($lesson->song_id === $song->id && in_array($lesson->type, ['youtube', 'audio'], true), 404);
        $data = $request->validate([
            'loops'         => ['present', 'array', 'max:' . SaveSongLessonLoopsService::MAX],
            'loops.*.start' => ['required', 'numeric', 'min:0'],
            'loops.*.end'   => ['required', 'numeric', 'min:0'],
            'loops.*.label' => ['nullable', 'string', 'max:60'],
            'loops.*.contributed' => ['nullable', 'boolean'],
        ]);

        return response()->json(['loops' => $service->execute($lesson, $data['loops'])->loops]);
    }
}
