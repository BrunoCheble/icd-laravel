<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;
use App\Modules\Gallery\Models\GalleryPhoto;
use Illuminate\Database\Eloquent\Builder;

class ListPublicGalleryService
{
    public const EVENTS = 40;

    /**
     * The site's gallery: the published albums with visible photos, first the events, the most recent first (at most
     * EVENTS), then the church's spaces in the order they were made. Each with its date (Y-m-d; null for a space),
     * cover and photos (the cover first, then in their order).
     */
    public function execute(): array
    {
        $events = $this->published(GalleryEvent::EVENT)->orderByDesc('event_date')->orderByDesc('id')->limit(self::EVENTS)->get();
        $spaces = $this->published(GalleryEvent::SPACE)->orderBy('id')->get();

        return $events->concat($spaces)->map(fn (GalleryEvent $album) => $this->album($album))->all();
    }

    /** The published albums of a kind (event or space) that have visible photos, with their photos. */
    private function published(string $kind): Builder
    {
        return GalleryEvent::query()->where('kind', $kind)->where('is_published', true)
            ->whereHas('photos', fn ($query) => $query->where('is_hidden', false))->with('photos');
    }

    private function album(GalleryEvent $album): array
    {
        $cover = $album->cover();
        $photos = $album->photos->where('is_hidden', false)
            ->sortBy(fn (GalleryPhoto $photo) => $photo->id === $cover?->id ? 0 : 1, SORT_NUMERIC)->values();

        return [
            'id' => $album->id,
            'name' => $album->name,
            'date' => $album->event_date?->format('Y-m-d'),
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
    }
}
