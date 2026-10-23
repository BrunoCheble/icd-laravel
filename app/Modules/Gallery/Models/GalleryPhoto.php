<?php

namespace App\Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A photo of a gallery event: its files in the public folder (images/gallery), caption, order and whether it is hidden. */
class GalleryPhoto extends Model
{
    public const FOLDER = 'images/gallery';

    protected $fillable = ['gallery_event_id', 'path', 'thumb', 'width', 'height', 'caption', 'sort_order', 'is_hidden'];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(GalleryEvent::class, 'gallery_event_id');
    }

    public function url(): string
    {
        return asset($this->path);
    }

    /** The small one for grids (the photo itself when there is none). */
    public function thumbUrl(): string
    {
        return asset($this->thumb ?: $this->path);
    }

    /** Deletes its files (only ones of the gallery's folder). */
    public function deleteFiles(): void
    {
        foreach (array_filter([$this->path, $this->thumb]) as $path) {
            if (str_starts_with($path, self::FOLDER . '/') && ! str_contains($path, '..') && is_file(public_path($path))) {
                @unlink(public_path($path));
            }
        }
    }
}
