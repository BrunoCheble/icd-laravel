<?php

namespace App\Http\Controllers;

use App\Enums\MusicalKey;
use App\Http\Requests\ListSongsRequest;
use App\Http\Requests\SongRequest;
use App\Models\Song;
use App\Services\FindSongBySourceService;
use App\Services\GetSongOverviewService;
use App\Services\ListSongsService;
use App\Services\SaveSongAudioService;
use App\Services\CreateSongBarsService;
use App\Services\DeleteSongsService;
use App\Http\Requests\BulkDestroySongsRequest;
use App\Services\SaveSongService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SongController extends Controller
{
    public function index(ListSongsRequest $request, ListSongsService $service): View
    {
        $filters = $request->validated();
        $songs = $service->execute($filters);
        // Every song of the filters, for selecting them all across pages.
        $allIds = $service->ids($filters);
        return view('songs.index', compact('songs', 'filters', 'allIds'));
    }

    public function create(FindSongBySourceService $sources): View
    {
        $song = new Song();
        $keyOptions = MusicalKey::pairs();
        $existingSources = $sources->all();
        $existingTitles = $sources->titles();
        return view('songs.create', compact('song', 'keyOptions', 'existingSources', 'existingTitles'));
    }

    public function store(SongRequest $request, SaveSongService $service, SaveSongAudioService $audioService, CreateSongBarsService $bars): RedirectResponse
    {
        try {
            $song = $service->execute(collect($request->validated())->except(['audio', 'remove_audio', 'meter'])->all());
            $audioService->execute($song, $request->file('audio'));
            $bars->execute($song, CreateSongBarsService::METERS[$request->validated('meter') ?? '4/4']);
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', __('Song created successfully.'));
    }

    public function show(Song $song, GetSongOverviewService $service): View
    {
        $overview = $service->execute($song);
        return view('songs.show', compact('song', 'overview'));
    }

    public function edit(Song $song, FindSongBySourceService $sources): View
    {
        $keyOptions = MusicalKey::pairs();
        $existingSources = $sources->all($song->id);
        return view('songs.edit', compact('song', 'keyOptions', 'existingSources'));
    }

    public function update(SongRequest $request, Song $song, SaveSongService $service, SaveSongAudioService $audioService, CreateSongBarsService $bars): RedirectResponse
    {
        try {
            $service->execute(collect($request->validated())->except(['audio', 'remove_audio', 'meter'])->all(), $song);
            $audioService->execute($song, $request->file('audio'), $request->boolean('remove_audio'));
            // A new time signature moves only the bar lines (chords keep their beats); blocks without bars (a new
            // song, or its map replaced) get them in it.
            $song = $song->fresh();
            $beats = CreateSongBarsService::METERS[$request->validated('meter') ?? CreateSongBarsService::meterOf($song->structure)];
            $bars->changeMeter($song, $beats);
            $bars->execute($song, $beats);
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', __('Song updated successfully.'));
    }

    public function bulkDestroy(BulkDestroySongsRequest $request, DeleteSongsService $service): RedirectResponse
    {
        try {
            $count = $service->execute($request->validated('ids'));
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', trans_choice(':count song deleted.|:count songs deleted.', $count, ['count' => $count]));
    }

    public function destroy(Song $song, SaveSongAudioService $audioService): RedirectResponse
    {
        try {
            $song->delete();
            $audioService->deleteFile($song);
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', __('Song deleted successfully.'));
    }
}
