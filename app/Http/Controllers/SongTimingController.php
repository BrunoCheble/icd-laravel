<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongTimingRequest;
use App\Models\Song;
use App\Services\UpdateSongLayoutService;
use App\Services\UpdateSongSectionTimesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SongTimingController extends Controller
{
    /**
     * Section times live only in the chord map (structure); the chord sheet uses them for its matching blocks.
     */
    public function edit(Song $song): View
    {
        // "Back to the start" markers have no time; the others keep their index in the structure.
        $sections = collect(is_array($song->structure) ? $song->structure : [])
            ->reject(fn ($section) => Song::isReturnMarker($section))
            ->map(fn ($section, $index) => [
                'index'  => $index,
                'name'   => is_object($section) ? str_replace('_', ' ', (string) ($section->section ?? '')) : '',
                'anchor' => is_object($section) ? ($section->anchor ?? null) : null,
                'chords' => is_object($section) && is_array($section->chords ?? null) ? implode(' ', UpdateSongLayoutService::chordSequence([['chords' => $section->chords]])) : '',
                'lyrics' => Song::sectionLyrics($section),
                'start'  => is_object($section) ? $this->startText($section->start ?? null) : '',
            ])
            ->values();

        return view('songs.timing', compact('song', 'sections'));
    }

    /**
     * Stored start as "m:ss"; unrecognized values are shown as-is so they can be fixed.
     */
    private function startText(mixed $start): string
    {
        if ($start === null || $start === '') {
            return '';
        }

        $seconds = UpdateSongSectionTimesService::toSeconds($start);

        return $seconds === null ? (string) $start : UpdateSongSectionTimesService::format($seconds);
    }

    public function update(SongTimingRequest $request, Song $song, UpdateSongSectionTimesService $service): RedirectResponse
    {
        try {
            $service->execute($song, $request->validated('starts', []));
        } catch (\Exception $e) {
            return Redirect::route('songs.timing.edit', $song)
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('songs.timing.edit', $song)
            ->with('success', __('Section times saved.'));
    }
}
