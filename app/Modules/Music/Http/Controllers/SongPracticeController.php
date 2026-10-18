<?php

namespace App\Modules\Music\Http\Controllers;

use App\Modules\Music\Models\Setlist;
use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\GetPracticeSongService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class SongPracticeController extends Controller
{
    /**
     * Practice page: the song's chord sheet (or chord map) with some chords hidden; nothing is saved.
     */
    public function show(Song $song, GetPracticeSongService $service): View
    {
        $data = $service->execute($song);

        return view('music::songs.practice', compact('data'));
    }

    /**
     * Practice through a setlist: its songs in order and in its keys, starting with the first one.
     */
    public function setlist(Setlist $setlist, GetPracticeSongService $service, ?Song $song = null): View|RedirectResponse
    {
        $song ??= $setlist->songs()->first();

        if (! $song) {
            return Redirect::route('setlists.show', $setlist)->with('error', __('No songs in this setlist yet.'));
        }

        $data = $service->execute($song, $setlist);

        return view('music::songs.practice', compact('data'));
    }
}
