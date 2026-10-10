<?php

namespace App\Http\Controllers;

use App\Enums\MusicalKey;
use App\Enums\SongInstrument;
use App\Http\Requests\ListSongsRequest;
use App\Http\Requests\SongRequest;
use App\Models\Song;
use App\Services\FindSongBySourceService;
use App\Services\GetSongOverviewService;
use App\Services\ListSongsService;
use App\Services\SaveSongAudioService;
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
        return view('songs.index', compact('songs', 'filters'));
    }

    public function create(FindSongBySourceService $sources): View
    {
        $song = new Song();
        $instrumentOptions = SongInstrument::options();
        $keyOptions = MusicalKey::pairs();
        $existingSources = $sources->all();
        $existingTitles = $sources->titles();
        return view('songs.create', compact('song', 'instrumentOptions', 'keyOptions', 'existingSources', 'existingTitles'));
    }

    public function store(SongRequest $request, SaveSongService $service, SaveSongAudioService $audioService): RedirectResponse
    {
        try {
            $song = $service->execute(collect($request->validated())->except(['audio', 'remove_audio'])->all());
            $audioService->execute($song, $request->file('audio'));
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', __('Song created successfully.'));
    }

    public function show(Song $song, GetSongOverviewService $service): View
    {
        $instrumentOptions = SongInstrument::options();
        $overview = $service->execute($song);
        return view('songs.show', compact('song', 'instrumentOptions', 'overview'));
    }

    public function edit(Song $song, FindSongBySourceService $sources): View
    {
        $instrumentOptions = SongInstrument::options();
        $keyOptions = MusicalKey::pairs();
        $existingSources = $sources->all($song->id);
        return view('songs.edit', compact('song', 'instrumentOptions', 'keyOptions', 'existingSources'));
    }

    public function update(SongRequest $request, Song $song, SaveSongService $service, SaveSongAudioService $audioService): RedirectResponse
    {
        try {
            $service->execute(collect($request->validated())->except(['audio', 'remove_audio'])->all(), $song);
            $audioService->execute($song, $request->file('audio'), $request->boolean('remove_audio'));
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', __('Song updated successfully.'));
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
