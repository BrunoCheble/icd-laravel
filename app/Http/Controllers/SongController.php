<?php

namespace App\Http\Controllers;

use App\Enums\MusicalKey;
use App\Enums\SongInstrument;
use App\Http\Requests\SongRequest;
use App\Models\Song;
use App\Services\SaveSongService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SongController extends Controller
{
    public function index(): View
    {
        $songs = Song::orderBy('title')->paginate();
        return view('songs.index', compact('songs'));
    }

    public function create(): View
    {
        $song = new Song();
        $instrumentOptions = SongInstrument::options();
        $keyOptions = MusicalKey::options();
        return view('songs.create', compact('song', 'instrumentOptions', 'keyOptions'));
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

    public function show(Song $song): View
    {
        $instrumentOptions = SongInstrument::options();
        return view('songs.show', compact('song', 'instrumentOptions'));
    }

    public function edit(Song $song): View
    {
        $instrumentOptions = SongInstrument::options();
        $keyOptions = MusicalKey::options();
        return view('songs.edit', compact('song', 'instrumentOptions', 'keyOptions'));
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
