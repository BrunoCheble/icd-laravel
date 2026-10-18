<?php

namespace App\Modules\Music\Http\Controllers;

use App\Modules\Music\Http\Requests\ImportScheduleRequest;
use App\Modules\Music\Services\ImportScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class SetlistImportController extends Controller
{
    /**
     * Page to paste a schedule shared as text (e.g. from LouveApp).
     */
    public function create(): View
    {
        return view('music::setlists.import');
    }

    public function store(ImportScheduleRequest $request, ImportScheduleService $service): RedirectResponse
    {
        $result = $service->execute($request->validated('text'));

        return Redirect::route('setlists.import')->with('import', [
            'setlist'  => ['title' => $result['setlist']->title, 'date' => $result['setlist']->event_date?->format('d/m/Y'), 'url' => route('setlists.index', ['open' => $result['setlist']->id])],
            'created'  => collect($result['created'])->map(fn ($song) => $song->title)->all(),
            'existing' => collect($result['existing'])->map(fn ($song) => $song->title)->all(),
            'pending'  => collect($result['pending'])->map(fn ($song) => ['title' => $song->title, 'layout' => route('songs.layout.edit', $song), 'source' => $song->source_url])->all(),
        ]);
    }
}
