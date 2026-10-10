<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudySongMapRequest;
use App\Models\Song;
use App\Services\GetStudySongService;
use App\Services\ListStudySongsService;
use App\Services\SaveStudySongMapService;
use Illuminate\Http\JsonResponse;

/**
 * Songs for the study app (icd-chords): list, read and save the chord map edited there (token protected).
 */
class StudySongController extends Controller
{
    public function index(ListStudySongsService $service): JsonResponse
    {
        return response()->json($service->execute());
    }

    public function show(Song $song, GetStudySongService $service): JsonResponse
    {
        return response()->json($service->execute($song));
    }

    public function updateMap(StudySongMapRequest $request, Song $song, SaveStudySongMapService $service, GetStudySongService $getService): JsonResponse
    {
        $bpm = $request->validated('bpm');
        $sheetUpdated = $service->execute(
            $song,
            $request->validated('blocks'),
            $request->validated('updated_at'),
            $bpm === null ? null : (float) $bpm,
            null,
            $request->validated('chord_sheet'),
            $request->boolean('keep_sheet'),
        );

        return response()->json([
            'song'          => $getService->execute($song->refresh()),
            'sheet_updated' => $sheetUpdated,
        ]);
    }
}
