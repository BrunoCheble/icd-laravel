<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryPhoto;
use Illuminate\Support\Facades\DB;

class MoveGalleryPhotoService
{
    /** Moves the photo one place back (-1) or on (1) among the event's photos. */
    public function execute(GalleryPhoto $photo, int $step): void
    {
        DB::transaction(function () use ($photo, $step) {
            $photos = $photo->event->photos()->get()->values();
            $at = $photos->search(fn (GalleryPhoto $item) => $item->id === $photo->id);
            $to = $at + ($step < 0 ? -1 : 1);
            if ($at === false || $to < 0 || $to >= $photos->count()) {
                return;
            }
            $order = $photos->all();
            [$order[$at], $order[$to]] = [$order[$to], $order[$at]];
            foreach ($order as $position => $item) {
                $item->update(['sort_order' => $position + 1]);
            }
        });
    }
}
