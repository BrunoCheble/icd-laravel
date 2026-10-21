<?php

namespace App\Modules\Music\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Music\Http\Requests\ReadScoreRequest;
use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\ReadScoreService;
use App\Modules\Music\Support\MusicXmlScore;
use Illuminate\Http\JsonResponse;
use Throwable;

class SongScoreController extends Controller
{
    /**
     * A score (MusicXML) read as the song's grid of bars, to preview on the grid page; nothing is saved here (the
     * page saves it as it saves the grid, the map before going to the history).
     */
    public function read(ReadScoreRequest $request, Song $song, ReadScoreService $service): JsonResponse
    {
        try {
            $score = MusicXmlScore::fromFile($request->file('score')->getRealPath());
            $preview = $service->execute($score, $song);
        } catch (Throwable $error) {
            report($error);

            return response()->json(['message' => __('The score could not be read: :error', ['error' => $error->getMessage()])], 422);
        }
        if (! collect($preview['blocks'])->contains(fn ($block) => collect($block['chords'])->contains(fn ($chord) => $chord !== '|' && $chord !== '?'))) {
            return response()->json(['message' => __('The score has no chord symbols (only notes): there is nothing to make the bars from.')], 422);
        }

        return response()->json($preview);
    }
}
