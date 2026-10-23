<?php

namespace App\Modules\Gallery\Services;

use App\Modules\Gallery\Models\GalleryEvent;
use App\Modules\Gallery\Models\GalleryPhoto;
use App\Support\ImageResizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AddGalleryPhotoService
{
    public const MAX = 1600;
    public const THUMB = 600;

    /**
     * Adds the photo at the end of the event: made light (upright, at most 1600 px) with a small one for grids (at most
     * 600 px), in the public folder; what the server cannot resize is kept as it came (without the small one).
     */
    public function execute(GalleryEvent $event, UploadedFile $file): GalleryPhoto
    {
        $folder = public_path(GalleryPhoto::FOLDER);
        if (! is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        $name = time() . '-' . $event->id . '-' . Str::lower(Str::random(8));

        $photo = ImageResizer::resize($file->getRealPath(), $folder . '/' . $name, self::MAX);
        $thumb = $photo ? ImageResizer::resize($file->getRealPath(), $folder . '/' . $name . '-thumb', self::THUMB) : null;
        if (! $photo) {
            $photo = $name . '.' . strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $file->move($folder, $photo);
        }
        [$width, $height] = @getimagesize($folder . '/' . $photo) ?: [null, null];

        return $event->photos()->create([
            'path' => GalleryPhoto::FOLDER . '/' . $photo,
            'thumb' => $thumb ? GalleryPhoto::FOLDER . '/' . $thumb : null,
            'width' => $width,
            'height' => $height,
            'sort_order' => (int) $event->photos()->max('sort_order') + 1,
        ]);
    }
}
