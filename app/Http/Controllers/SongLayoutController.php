<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongLayoutRequest;
use App\Models\Song;
use App\Services\UpdateSongLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SongLayoutController extends Controller
{
    /**
     * Line breaks and blocks of the chord map (structure), edited by clicking on the chords, over the chord
     * sheet when the song has one.
     */
    public function edit(Song $song): View
    {
        $blocks = json_decode(json_encode(is_array($song->structure) ? $song->structure : []), true) ?? [];

        $sheet = $song->chord_sheet['sections'] ?? [];

        return view('songs.layout', compact('song', 'blocks', 'sheet'));
    }

    public function update(SongLayoutRequest $request, Song $song, UpdateSongLayoutService $service): RedirectResponse
    {
        try {
            $service->execute($song, $request->blocks(), $request->sheetSections());
        } catch (\Exception $e) {
            return Redirect::route('songs.layout.edit', $song)
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.layout.edit', $song)
            ->with('success', __('Layout saved.'));
    }
}
