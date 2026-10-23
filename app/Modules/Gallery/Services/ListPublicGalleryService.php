<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;
use App\Modules\Gallery\Models\GalleryPhoto;

class ListPublicGalleryService
{
    public const EVENTS = 40;

    /**
     * The site's gallery: the published events with visible photos, the most recent first (at most EVENTS), each
     * with its date (Y-m-d), cover and photos (the cover first, then in their order).
     */
    public function execute(): array
    {
        return GalleryEvent::query()->where('is_published', true)
            ->whereHas('photos', fn ($query) => $query->where('is_hidden', false))
            ->with('photos')->orderByDesc('event_date')->orderByDesc('id')->limit(self::EVENTS)->get()
            ->map(function (GalleryEvent $event) {
                $cover = $event->cover();
                $photos = $event->photos->where('is_hidden', false)
                    ->sortBy(fn (GalleryPhoto $photo) => $photo->id === $cover?->id ? 0 : 1, SORT_NUMERIC)->values();

                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'date' => $event->event_date->format('Y-m-d'),
                    'cover' => $cover?->thumbUrl(),
                    'photos' => $photos->map(fn (GalleryPhoto $photo) => [
                        'id' => $photo->id,
                        'src' => $photo->url(),
                        'thumb' => $photo->thumbUrl(),
                        'caption' => $photo->caption,
                        'width' => $photo->width,
                        'height' => $photo->height,
                    ])->all(),
                ];
            })->all();
    }
}
