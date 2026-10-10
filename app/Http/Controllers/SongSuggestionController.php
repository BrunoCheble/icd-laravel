<?php

namespace App\Http\Controllers;

use App\Models\SongSuggestion;
use App\Services\ApplySongSuggestionService;
use App\Services\ListSongSuggestionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SongSuggestionController extends Controller
{
    /**
     * Chord corrections suggested on the public setlist page, to approve or reject.
     */
    public function index(ListSongSuggestionsService $service): View
    {
        return view('songs.suggestions', $service->execute());
    }

    /**
     * Applies the checked changes (the others are rejected).
     */
    public function approve(Request $request, SongSuggestion $suggestion, ApplySongSuggestionService $service): RedirectResponse
    {
        abort_unless($suggestion->status === 'pending', 409);
        $accepted = array_map('intval', (array) $request->input('accepted', []));
        $suggestion = $service->execute($suggestion, $accepted);
        $applied = collect($suggestion->changes)->where('result', 'applied')->count();

        return Redirect::route('song-suggestions.index')->with('success', __(':count change(s) applied to :song.', ['count' => $applied, 'song' => $suggestion->song->title]));
    }

    public function reject(SongSuggestion $suggestion): RedirectResponse
    {
        abort_unless($suggestion->status === 'pending', 409);
        $suggestion->update([
            'status'      => 'rejected',
            'changes'     => array_map(fn ($change) => array_merge($change, ['result' => 'rejected']), $suggestion->changes),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return Redirect::route('song-suggestions.index')->with('success', __('Suggestion rejected.'));
    }
}
