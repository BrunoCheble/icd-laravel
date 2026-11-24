<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;

class SaveGalleryEventService
{
    /** Creates or updates an album: event or space, name, date (events only), whether it shows on the site. */
    public function execute(array $data, ?GalleryEvent $event = null): GalleryEvent
    {
        $event ??= new GalleryEvent();
        $kind = ($data['kind'] ?? GalleryEvent::EVENT) === GalleryEvent::SPACE ? GalleryEvent::SPACE : GalleryEvent::EVENT;
        $event->fill([
            'kind' => $kind,
            'name' => $data['name'],
            'event_date' => $kind === GalleryEvent::SPACE ? null : $data['event_date'],
            'is_published' => (bool) ($data['is_published'] ?? false),
        ])->save();

        return $event;
    }
}
