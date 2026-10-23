<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryPhoto;

class UpdateGalleryPhotoService
{
    /** Its caption, whether it is hidden, or whether it is the event's cover (only what $data has). */
    public function execute(GalleryPhoto $photo, array $data): GalleryPhoto
    {
        if (array_key_exists('caption', $data)) {
            $photo->caption = trim((string) $data['caption']) ?: null;
        }
        if (array_key_exists('is_hidden', $data)) {
            $photo->is_hidden = (bool) $data['is_hidden'];
        }
        $photo->save();

        if (! empty($data['is_cover'])) {
            $photo->event->update(['cover_photo_id' => $photo->id]);
        }

        return $photo;
    }
}
