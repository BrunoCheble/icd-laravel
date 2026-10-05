<?php

namespace App\Http\Controllers\Site;

use App\Enums\MusicalKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddSongToSetlistRequest;
use App\Http\Requests\ReorderSetlistSongsRequest;
use App\Http\Requests\UpdateSetlistSongKeyRequest;
use App\Models\Setlist;
use App\Models\Song;
use App\Services\AddSongToSetlistService;
use App\Services\GetPublicSetlistService;
use App\Services\ListPublicSetlistsService;
use App\Services\RemoveSongFromSetlistService;
use App\Services\ReorderSetlistSongsService;
use App\Services\SearchSongsService;
use App\Services\UpdateSetlistSongKeyService;
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

    public function reorder(ReorderSetlistSongsRequest $request, Setlist $setlist, ReorderSetlistSongsService $service): Response
    {
        $service->execute($setlist, $request->orderedSongIds());

        return response()->noContent();
    }

    private function presentation(?Setlist $setlist, ListPublicSetlistsService $listService, GetPublicSetlistService $getService): View
    {
        $data = $setlist ? $getService->execute($setlist) : null;
        $setlistOptions = $listService->execute();
        $keyOptions = MusicalKey::options();

        return view('site.setlist', compact('data', 'setlistOptions', 'keyOptions'));
    }

    private function ensureSongInSetlist(Setlist $setlist, Song $song): void
    {
        abort_unless($setlist->songs()->whereKey($song->id)->exists(), 404);
    }
}
