<?php

namespace App\Modules\Gallery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Http\Requests\GalleryEventRequest;
use App\Modules\Gallery\Models\GalleryEvent;
use App\Modules\Gallery\Services\ListGalleryEventsService;
use App\Modules\Gallery\Services\RemoveGalleryEventService;
use App\Modules\Gallery\Services\SaveGalleryEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GalleryEventController extends Controller
{
    public function index(ListGalleryEventsService $service): View
    {
        return view('gallery::gallery.index', ['events' => $service->execute()]);
    }

    public function create(): View
    {
        return view('gallery::gallery.form', ['event' => new GalleryEvent(['event_date' => today(), 'is_published' => true])]);
    }

    public function store(GalleryEventRequest $request, SaveGalleryEventService $service): RedirectResponse
    {
        $event = $service->execute($request->validated());

        return redirect()->route('gallery.show', $event)->with('success', __('Event saved. Now add its photos.'));
    }

    /** Its photos: sending more, caption, cover, order, hidden or deleted. */
    public function show(GalleryEvent $event): View
    {
        $event->load('photos');

        return view('gallery::gallery.show', ['event' => $event, 'cover' => $event->cover()]);
    }

    public function edit(GalleryEvent $event): View
    {
        return view('gallery::gallery.form', ['event' => $event]);
    }

    public function update(GalleryEventRequest $request, GalleryEvent $event, SaveGalleryEventService $service): RedirectResponse
    {
        $service->execute($request->validated(), $event);

        return redirect()->route('gallery.show', $event)->with('success', __('Event saved.'));
    }

    public function destroy(GalleryEvent $event, RemoveGalleryEventService $service): RedirectResponse
    {
        $service->execute($event);

        return redirect()->route('gallery.index')->with('success', __('Event deleted.'));
    }
}
