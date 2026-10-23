<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;
use Illuminate\Support\Collection;

class ListGalleryEventsService
{
    /** Every event, the most recent first, with its photos (for the cover and the count). */
    public function execute(): Collection
    {
        return GalleryEvent::query()->with('photos')->orderByDesc('event_date')->orderByDesc('id')->get();
    }
}
