<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongReviewRequest;
use App\Models\Song;
use App\Services\MarkSongReviewedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class SongReviewController extends Controller
{
    public function update(SongReviewRequest $request, Song $song, MarkSongReviewedService $service): RedirectResponse
    {
        $reviewed = $request->boolean('reviewed');

        try {
            $service->execute($song, $reviewed);
        } catch (\Exception $e) {
            return Redirect::back()->with('error', __('Something went wrong'));
        }

        return Redirect::back()->with('success', $reviewed ? __('Song marked as reviewed.') : __('Review mark removed.'));
    }
}
