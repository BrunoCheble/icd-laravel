<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;

class RemoveGalleryEventService
{
    /** Deletes the event with its photos and their files. */
    public function execute(GalleryEvent $event): void
    {
        $event->photos->each->deleteFiles();
        $event->delete();
    }
}
