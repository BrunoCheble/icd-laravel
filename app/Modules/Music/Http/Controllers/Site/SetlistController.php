<?php

namespace App\Modules\Music\Http\Controllers\Site;

use App\Modules\Music\Enums\MusicalKey;
use App\Http\Controllers\Controller;
use App\Modules\Music\Http\Requests\ContributeLessonLoopRequest;
use App\Modules\Music\Http\Requests\ContributeSongLessonRequest;
use App\Modules\Music\Http\Requests\AddSongToSetlistRequest;
use App\Modules\Music\Http\Requests\ReorderSetlistSongsRequest;
use App\Modules\Music\Http\Requests\SongSuggestionRequest;
use App\Modules\Music\Http\Requests\UpdateSetlistSongBpmRequest;
use App\Modules\Music\Http\Requests\UpdateSetlistSongKeyRequest;
use App\Modules\Music\Models\Setlist;
use App\Modules\Music\Models\Song;
use App\Modules\Music\Models\SongLesson;
use App\Modules\Music\Models\SongVersion;
use App\Modules\Music\Services\ContributeLessonLoopService;
use App\Modules\Music\Services\ContributeSongLessonService;
use App\Modules\Music\Services\GetSongCatalogService;
use App\Modules\Music\Services\AddSongToSetlistService;
use App\Modules\Music\Services\GetPublicSetlistService;
use App\Modules\Music\Services\ListPublicSetlistsService;
use App\Modules\Music\Services\RemoveSongFromSetlistService;
use App\Modules\Music\Services\ReorderSetlistSongsService;
use App\Modules\Music\Services\SearchSongsService;
use App\Modules\Music\Services\StoreSongSuggestionService;
use App\Modules\Music\Services\UpdateSetlistSongBpmService;
use App\Modules\Music\Services\UpdateSetlistSongKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SetlistController extends Controller
{
    public function index(ListPublicSetlistsService $listService, GetPublicSetlistService $getService): View
    {
        $setlist = $listService->default();
        return $this->presentation($setlist, $listService, $getService);
    }

    public function show(Setlist $setlist, ListPublicSetlistsService $listService, GetPublicSetlistService $getService): View
    {
        return $this->presentation($setlist, $listService, $getService);
    }

    public function searchSongs(Request $request, SearchSongsService $service): JsonResponse
    {
        return response()->json($service->execute($request->query('q')));
    }

    // Every song with its chords, saved with the offline copy of the app.
    public function catalog(GetSongCatalogService $service): JsonResponse
    {
        return response()->json($service->execute());
    }

    public function addSong(AddSongToSetlistRequest $request, Setlist $setlist, AddSongToSetlistService $service): JsonResponse
    {
        $song = $service->execute($setlist, Song::findOrFail($request->validated('song_id')));

        return response()->json(GetPublicSetlistService::songPayload($song), 201);
    }

    public function removeSong(Setlist $setlist, Song $song, RemoveSongFromSetlistService $service): Response
    {
        $this->ensureSongInSetlist($setlist, $song);
        $service->execute($setlist, $song);

        return response()->noContent();
    }

    public function updateKey(UpdateSetlistSongKeyRequest $request, Setlist $setlist, Song $song, UpdateSetlistSongKeyService $service): Response
    {
        $this->ensureSongInSetlist($setlist, $song);
        $service->execute($setlist, $song, $request->validated('musical_key'));

        return response()->noContent();
    }

    public function updateBpm(UpdateSetlistSongBpmRequest $request, Setlist $setlist, Song $song, UpdateSetlistSongBpmService $service): Response
    {
        $this->ensureSongInSetlist($setlist, $song);
        $bpm = $request->validated('bpm');
        $service->execute($setlist, $song, $bpm === null ? null : (float) $bpm);

        return response()->noContent();
    }

    public function reorder(ReorderSetlistSongsRequest $request, Setlist $setlist, ReorderSetlistSongsService $service): Response
    {
        $service->execute($setlist, $request->orderedSongIds());

        return response()->noContent();
    }

    private function presentation(?Setlist $setlist, ListPublicSetlistsService $listService, GetPublicSetlistService $getService): View
    {
        $data = $setlist ? $getService->execute($setlist) : null;
        $setlistOptions = $listService->execute();
        $keyOptions = MusicalKey::pairs();
        // Instruments offered for lessons (filter and videos sent by musicians): the usual ones and any with lessons.
        $instruments = SongVersion::instrumentNames();

        return view('music::site.setlist', compact('data', 'setlistOptions', 'keyOptions', 'instruments'));
    }

    /**
     * Chord corrections suggested by a musician on the public page, kept for an admin to review.
     */
    public function suggest(SongSuggestionRequest $request, Setlist $setlist, Song $song, StoreSongSuggestionService $service): JsonResponse
    {
        $this->ensureSongInSetlist($setlist, $song);
        $suggestion = $service->execute($song, $setlist, $request->validated());

        return response()->json(['id' => $suggestion?->id], $suggestion ? 201 : 200);
    }

    /**
     * A YouTube video for an instrument, sent by a musician on the public page: it becomes a lesson of the song, marked
     * as sent by a musician (the page cannot change or remove it).
     */
    public function contributeLesson(ContributeSongLessonRequest $request, Setlist $setlist, Song $song, ContributeSongLessonService $service): JsonResponse
    {
        $this->ensureSongInSetlist($setlist, $song);
        $lesson = $service->execute($song, $request->validated('instrument'), $request->validated('video_url'));

        return response()->json($lesson->payload(), $lesson->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * A stretch of a lesson's video or audio (an intro, a solo…) saved by a musician on the public page: kept in the
     * lesson for everyone, marked as sent by a musician (the page cannot change or remove it).
     */
    public function contributeLessonLoop(ContributeLessonLoopRequest $request, Setlist $setlist, Song $song, SongLesson $lesson, ContributeLessonLoopService $service): JsonResponse
    {
        $this->ensureSongInSetlist($setlist, $song);
        abort_unless($lesson->song_id === $song->id && in_array($lesson->type, ['youtube', 'audio'], true), 404);
        $lesson = $service->execute($lesson, (float) $request->validated('start'), (float) $request->validated('end'), $request->validated('label'));

        return response()->json(['loops' => $lesson->loops ?? []]);
    }

    private function ensureSongInSetlist(Setlist $setlist, Song $song): void
    {
        abort_unless($setlist->songs()->whereKey($song->id)->exists(), 404);
    }
}
