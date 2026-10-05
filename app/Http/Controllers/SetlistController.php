<?php

namespace App\Http\Controllers;

use App\Enums\MusicalKey;
use App\Http\Requests\SetlistRequest;
use App\Models\Setlist;
use App\Models\Song;
use App\Services\ListSetlistsService;
use App\Services\SaveSetlistService;
use App\Services\SearchSongsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SetlistController extends Controller
{
    public function index(Request $request, ListSetlistsService $service): View
    {
        $search = $request->query('search');
        $setlists = $service->execute($search);

        return view('setlists.index', compact('setlists', 'search'));
    }

    public function create(): View
    {
        $setlist = new Setlist();
        $selectedSongs = $this->selectedSongs($setlist);
        $keyOptions = MusicalKey::options();
        return view('setlists.create', compact('setlist', 'selectedSongs', 'keyOptions'));
    }

    public function store(SetlistRequest $request, SaveSetlistService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated());
        } catch (\Exception $e) {
            return Redirect::route('setlists.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('setlists.index')
            ->with('success', __('Setlist created successfully.'));
    }

    public function show(Setlist $setlist): View
    {
        $setlist->load('songs');
        return view('setlists.show', compact('setlist'));
    }

    public function edit(Setlist $setlist): View
    {
        $selectedSongs = $this->selectedSongs($setlist);
        $keyOptions = MusicalKey::options();
        return view('setlists.edit', compact('setlist', 'selectedSongs', 'keyOptions'));
    }

    public function update(SetlistRequest $request, Setlist $setlist, SaveSetlistService $service): RedirectResponse
    {
        try {
            $service->execute($request->validated(), $setlist);
        } catch (\Exception $e) {
            return Redirect::route('setlists.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('setlists.index')
            ->with('success', __('Setlist updated successfully.'));
    }

    public function destroy(Setlist $setlist): RedirectResponse
    {
        try {
            // setlist_songs rows are removed by ON DELETE CASCADE; songs are untouched.
            $setlist->delete();
        } catch (\Exception $e) {
            return Redirect::route('setlists.index')
                ->with('error', __('Something went wrong'));
        }

        return Redirect::route('setlists.index')
            ->with('success', __('Setlist deleted successfully.'));
    }

    /**
     * Searches existing songs by title or artist for the setlist form.
     */
    public function searchSongs(Request $request, SearchSongsService $service): JsonResponse
    {
        return response()->json($service->execute($request->query('q')));
    }

    /**
     * Songs to prefill the form with their setlist settings:
     * the submitted ones after a validation error, otherwise the setlist's.
     */
    private function selectedSongs(Setlist $setlist): Collection
    {
        if (session()->hasOldInput()) {
            $submitted = collect(old('songs', []))->values();
            $songs = Song::whereIn('id', $submitted->pluck('song_id')->map(fn ($id) => (int) $id))->get()->keyBy('id');

            return $submitted
                ->filter(fn ($item) => $songs->has((int) ($item['song_id'] ?? 0)))
                ->map(fn ($item) => $this->songRow($songs[(int) $item['song_id']], $item['musical_key'] ?? null, $item['minister_name'] ?? null))
                ->values();
        }

        return $setlist->exists
            ? $setlist->songs->map(fn (Song $song) => $this->songRow($song, $song->pivot->musical_key, $song->pivot->minister_name))
            : collect();
    }

    private function songRow(Song $song, ?string $musicalKey, ?string $ministerName): array
    {
        return [
            'id'            => $song->id,
            'title'         => $song->title,
            'artist'        => $song->artist,
            'original_key'  => $song->musical_key,
            'musical_key'   => $musicalKey,
            'minister_name' => $ministerName,
        ];
    }
}
