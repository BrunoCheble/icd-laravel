<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryPhoto;

class RemoveGalleryPhotoService
{
    /** Deletes the photo and its files (a cover that goes: the event's first photo is the cover again). */
    public function execute(GalleryPhoto $photo): void
    {
        $event = $photo->event;
        $photo->deleteFiles();
        $photo->delete();
        if ($event->cover_photo_id === $photo->id) {
            $event->update(['cover_photo_id' => null]);
        }
    }
}
