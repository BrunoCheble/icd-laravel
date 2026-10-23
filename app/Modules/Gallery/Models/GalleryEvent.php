<?php

namespace App\Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An event of the site's gallery (a service, a baptism, a meeting…) and its photos. Published, it shows on the site
 * (its visible photos, the cover first).
 */
class GalleryEvent extends Model
{
    protected $fillable = ['name', 'event_date', 'is_published', 'cover_photo_id'];

    protected $casts = [
        'event_date' => 'date',
        'is_published' => 'boolean',
    ];

    /** Its photos in their order. */
    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The cover: the chosen photo when it is still there and visible, otherwise the first visible one. */
    public function cover(): ?GalleryPhoto
    {
        $photos = $this->photos->where('is_hidden', false);

        return $photos->firstWhere('id', $this->cover_photo_id) ?? $photos->first();
    }
}
