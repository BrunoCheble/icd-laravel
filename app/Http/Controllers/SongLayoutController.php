<?php

namespace App\Http\Controllers;

use App\Enums\MusicalKey;
use App\Http\Requests\SongLayoutRequest;
use App\Http\Requests\SongSheetSectionRequest;
use App\Http\Requests\StudySongMapRequest;
use App\Services\CreateSongBarsService;
use App\Services\SaveStudySongMapService;
use App\Services\SyncChordSheetWithStructureService;
use App\Services\UpdateChordSheetSectionService;
use Illuminate\Http\JsonResponse;
use App\Models\Song;
use App\Models\SongVersion;
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
    public function edit(Song $song, SyncChordSheetWithStructureService $syncSheet): View
    {
        $blocks = json_decode(json_encode(is_array($song->structure) ? $song->structure : []), true) ?? [];

        $sheet = $song->chord_sheet['sections'] ?? [];

        $keyOptions = MusicalKey::pairs();

        // A map with bars (chord durations) is edited as a grid of bars, next to the chord sheet split like it.
        if ($song->hasBars()) {
            $sheet = $sheet ? $syncSheet->execute($sheet, $song->structure) : [];

            $versions = $song->versions->map(fn ($version) => SongVersionController::payload($song, $version))->values()->all();
            $instruments = SongVersion::instrumentNames();

            return view('songs.bars', compact('song', 'blocks', 'sheet', 'keyOptions', 'versions', 'instruments'));
        }

        return view('songs.layout', compact('song', 'blocks', 'sheet', 'keyOptions'));
    }

    /**
     * Saves the grid of bars (JSON from the layout page): same rules as the study app, the chord sheet following
     * the chord changes.
     */
    public function updateBars(StudySongMapRequest $request, Song $song, SaveStudySongMapService $service, SyncChordSheetWithStructureService $syncSheet): JsonResponse
    {
        $bpm = $request->validated('bpm');
        $sheetUpdated = $service->execute(
            $song,
            $request->validated('blocks'),
            $request->validated('updated_at'),
            $bpm === null ? null : (float) $bpm,
            $request->validated('musical_key'),
        );
        $song->refresh();
        $sheet = $song->chord_sheet['sections'] ?? [];

        return response()->json([
            'updated_at'    => $song->updated_at?->toIso8601String(),
            'sheet_updated' => $sheetUpdated,
            'sheet'         => $sheet ? $syncSheet->execute($sheet, $song->structure) : [],
        ]);
    }

    /**
     * Saves the lyrics (and where the chords fall) of one section of the chord sheet, edited on the layout page.
     */
    public function updateSheetSection(SongSheetSectionRequest $request, Song $song, UpdateChordSheetSectionService $service, SyncChordSheetWithStructureService $syncSheet): JsonResponse
    {
        $song = $service->execute($song, (int) $request->validated('index'), $request->validated('lines'), $request->validated('updated_at'));
        $sheet = $song->chord_sheet['sections'] ?? [];
        $blocks = collect(json_decode(json_encode($song->structure ?? []), true) ?? []);

        return response()->json([
            'updated_at' => $song->updated_at?->toIso8601String(),
            'sheet'      => $sheet ? $syncSheet->execute($sheet, $song->structure) : [],
            // Lyrics and anchors of the map's blocks, in order, for the page's copy of the map.
            'lyrics'     => $blocks->map(fn ($block) => is_array($block) ? ($block['lyrics'] ?? null) : null)->all(),
            'anchors'    => $blocks->map(fn ($block) => is_array($block) ? ($block['anchor'] ?? null) : null)->all(),
        ]);
    }

    /**
     * Gives the map one bar per chord, to be edited as a grid of bars.
     */
    public function createBars(Song $song, CreateSongBarsService $service): RedirectResponse
    {
        $service->execute($song);

        return Redirect::route('songs.layout.edit', $song)
            ->with('success', __('Bars created: one bar per chord. Adjust them on the grid.'));
    }

    public function update(SongLayoutRequest $request, Song $song, UpdateSongLayoutService $service): RedirectResponse
    {
        try {
            $service->execute($song, $request->blocks(), $request->sheetSections(), $request->input('musical_key'));
        } catch (\Exception $e) {
            return Redirect::route('songs.layout.edit', $song)
                ->with('error', __('Something went wrong'));
        }

        // Back in the same mode (e.g. times) the page was in.
        $mode = in_array($request->input('mode'), ['block', 'passing', 'edit', 'time'], true) ? $request->input('mode') : null;

        return Redirect::route('songs.layout.edit', array_filter(['song' => $song, 'mode' => $mode]))
            ->with('success', __('Layout saved.'));
    }
}
