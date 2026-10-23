<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;

class SaveGalleryEventService
{
    /** Creates or updates an event (name, date, whether it shows on the site). */
    public function execute(array $data, ?GalleryEvent $event = null): GalleryEvent
    {
        $event ??= new GalleryEvent();
        $event->fill([
            'name' => $data['name'],
            'event_date' => $data['event_date'],
            'is_published' => (bool) ($data['is_published'] ?? false),
        ])->save();

        return $event;
    }
}
