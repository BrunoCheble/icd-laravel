<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;
use Illuminate\Support\Collection;

class ListGalleryEventsService
{
    /**
     * Every album with its photos (for the cover and the count), split: the events, the most recent first, and the
     * spaces in the order they were made (the site's order).
     *
     * @return array{events: Collection, spaces: Collection}
     */
    public function execute(): array
    {
        $albums = GalleryEvent::query()->with('photos')->orderByDesc('event_date')->orderByDesc('id')->get();

        return [
            'events' => $albums->reject->isSpace()->values(),
            'spaces' => $albums->filter->isSpace()->sortBy('id')->values(),
        ];
    }
}
