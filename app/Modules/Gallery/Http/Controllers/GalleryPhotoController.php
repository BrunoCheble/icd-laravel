<?php

namespace App\Modules\Gallery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Http\Requests\GalleryPhotoRequest;
use App\Modules\Gallery\Http\Requests\UpdateGalleryPhotoRequest;
use App\Modules\Gallery\Models\GalleryEvent;
use App\Modules\Gallery\Models\GalleryPhoto;
use App\Modules\Gallery\Services\AddGalleryPhotoService;
use App\Modules\Gallery\Services\MoveGalleryPhotoService;
use App\Modules\Gallery\Services\RemoveGalleryPhotoService;
use App\Modules\Gallery\Services\UpdateGalleryPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GalleryPhotoController extends Controller
{
    /** One photo (the page sends them one by one); answers with it. */
    public function store(GalleryPhotoRequest $request, GalleryEvent $event, AddGalleryPhotoService $service): JsonResponse
    {
        $photo = $service->execute($event, $request->file('photo'));

        return response()->json(['id' => $photo->id, 'thumb' => $photo->thumbUrl()], 201);
    }

    public function update(UpdateGalleryPhotoRequest $request, GalleryPhoto $photo, UpdateGalleryPhotoService $service): JsonResponse|RedirectResponse
    {
        $service->execute($photo, $request->validated());

        return $request->wantsJson() ? response()->json(['ok' => true]) : back()->with('success', __('Photo saved.'));
    }

    public function move(Request $request, GalleryPhoto $photo, MoveGalleryPhotoService $service): RedirectResponse
    {
        $service->execute($photo, (int) $request->input('step'));

        return back();
    }

    public function destroy(GalleryPhoto $photo, RemoveGalleryPhotoService $service): RedirectResponse
    {
        $service->execute($photo);

        return back()->with('success', __('Photo deleted.'));
    }
}
