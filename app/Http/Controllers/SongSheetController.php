<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongSheetRequest;
use App\Models\Song;
use App\Services\GenerateBarsFromSheetService;
use App\Services\SaveSongChordSheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SongSheetController extends Controller
{
    /**
     * The chord sheet page: lyrics with chords, made by ear while the song plays; the bars are made from it.
     */
    public function edit(Song $song): View
    {
        $sections = $song->chord_sheet['sections'] ?? [];

        return view('songs.sheet', compact('song', 'sections'));
    }

    public function update(SongSheetRequest $request, Song $song, SaveSongChordSheetService $service): JsonResponse
    {
        $song = $service->execute($song, $request->validated('sections'), $request->validated('updated_at'));

        return response()->json(['updated_at' => $song->updated_at?->toIso8601String()]);
    }

    public function bars(SongSheetRequest $request, Song $song, GenerateBarsFromSheetService $service): JsonResponse
    {
        $service->execute($song, $request->validated('sections'), $request->validated('updated_at'));
        session()->flash('success', __('Bars made from the chord sheet: one bar per chord. Adjust them here (the map before is in the history).'));

        return response()->json(['redirect' => route('songs.layout.edit', $song)]);
    }
}
