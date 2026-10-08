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
        return view('songs.create', compact('song', 'instrumentOptions', 'keyOptions', 'existingSources'));
    }

    public function store(SongRequest $request, SaveSongService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated());
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

    public function update(SongRequest $request, Song $song, SaveSongService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated(), $song);
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', __('Song updated successfully.'));
    }

    public function destroy(Song $song): RedirectResponse
    {
        try {
            $song->delete();
        } catch (\Exception $e) {
            return Redirect::route('songs.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.index')
            ->with('success', __('Song deleted successfully.'));
    }
}
