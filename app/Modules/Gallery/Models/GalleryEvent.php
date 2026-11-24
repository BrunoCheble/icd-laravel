<?php

namespace App\Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An album of the site's gallery and its photos: an event (a service, a baptism, a meeting…, with its date) or one of
 * the church's spaces (the entrance, the hall, the kids' rooms…, without a date). Published, it shows on the site (its
 * visible photos, the cover first; the events first, then the spaces).
 */
class GalleryEvent extends Model
{
    public const EVENT = 'event';
    public const SPACE = 'space';

    protected $fillable = ['kind', 'name', 'event_date', 'is_published', 'cover_photo_id'];

    protected $attributes = ['kind' => self::EVENT];

    protected $casts = [
        'event_date' => 'date',
        'is_published' => 'boolean',
    ];

    public function isSpace(): bool
    {
        return $this->kind === self::SPACE;
    }

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
