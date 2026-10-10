<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\SongRevision;
use App\Services\ListSongRevisionsService;
use App\Services\RestoreSongRevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SongHistoryController extends Controller
{
    /**
     * Earlier versions of the song's chord map, chord sheet, key and tempo.
     */
    public function index(Song $song, ListSongRevisionsService $service): View
    {
        $revisions = $service->execute($song);

        return view('songs.history', compact('song', 'revisions'));
    }

    /**
     * Puts a revision back (what the song had is kept as a revision too).
     */
    public function restore(Song $song, SongRevision $revision, RestoreSongRevisionService $service): RedirectResponse
    {
        abort_unless($revision->song_id === $song->id, 404);
        $service->execute($song, $revision);

        return Redirect::route('songs.history', $song)->with('success', __('Version of :date restored. What the song had before is in the history too.', ['date' => $revision->created_at->format('d/m/Y H:i')]));
    }
}
