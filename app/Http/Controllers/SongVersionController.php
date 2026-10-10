<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudySongMapRequest;
use App\Models\Song;
use App\Models\SongVersion;
use App\Services\CreateSongVersionService;
use App\Services\SaveSongVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongVersionController extends Controller
{
    /**
     * Makes a version of the song's map for an instrument (a copy of the map as it is now).
     */
    public function store(Request $request, Song $song, CreateSongVersionService $service): JsonResponse
    {
        $data = $request->validate(['instrument' => ['required', 'string', 'max:50']]);

        return response()->json(self::payload($song, $service->execute($song, $data['instrument'])), 201);
    }

    /**
     * Saves the chords of a version, edited as bars on the layout page.
     */
    public function update(StudySongMapRequest $request, Song $song, SongVersion $version, SaveSongVersionService $service): JsonResponse
    {
        abort_unless($version->song_id === $song->id, 404);
        $version = $service->execute($version, $request->validated('blocks'), $request->validated('updated_at'), $request->validated('musical_key'));

        return response()->json(self::payload($song, $version));
    }

    public function destroy(Song $song, SongVersion $version): JsonResponse
    {
        abort_unless($version->song_id === $song->id, 404);
        $version->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * A version as the layout page uses it.
     */
    public static function payload(Song $song, SongVersion $version): array
    {
        return [
            'id'          => $version->id,
            'instrument'  => $version->instrument,
            'musical_key' => $version->musical_key,
            'structure'   => $version->structure,
            'updated_at'  => $version->updated_at?->toIso8601String(),
            'update_url'  => route('songs.versions.update', [$song, $version]),
            'delete_url'  => route('songs.versions.destroy', [$song, $version]),
        ];
    }
}
