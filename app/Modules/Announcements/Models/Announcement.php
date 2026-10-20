<?php

namespace App\Modules\Announcements\Models;

use App\Enums\AnnouncementType;
use App\Modules\Announcements\Support\AnnouncementCategories;
use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    // Where an announcement stands: waiting for approval, published (approved, not expired), or expired.
    public const PENDING = 'pending';
    public const PUBLISHED = 'published';
    public const EXPIRED = 'expired';

    // The picture of an announcement is its category's banner (no pictures sent by people); the default one for the
    // old types (donations, products).
    public const DEFAULT_PICTURE = 'images/announcements/default.jpg';

    // The site's list of announcements (see ListPublicAnnouncementsService), cached; any change clears it.
    public const PUBLIC_CACHE = 'public-announcements';

    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget(self::PUBLIC_CACHE);
        static::saved($forget);
        static::deleted($forget);
    }

    protected $fillable = [
        'member_id',
        'title',
        'description',
        'type',
        'details',
        'contact',
        'link',
        'image_path',
        'is_approved',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'details' => 'array',
    ];

    /**
     * The member who sent the announcement.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * The members' comments about it (the site shows the approved ones).
     */
    public function comments()
    {
        return $this->hasMany(AnnouncementComment::class)->latest()->orderByDesc('id');
    }

    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }

    public function status(): string
    {
        if (! $this->is_approved) {
            return self::PENDING;
        }

        return $this->expires_at && $this->expires_at < now()->toDateString() ? self::EXPIRED : self::PUBLISHED;
    }

    public function getTypeNameAttribute(): string
    {
        // A category, or one of the old types (donation, product) kept by older announcements.
        return AnnouncementCategories::label((string) $this->type) ?? AnnouncementType::options()[$this->type] ?? (string) $this->type;
    }

    /**
     * The file sent with it (a résumé's PDF; pictures from before the categories), or null without one.
     */
    public function getUrlImageAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }
        if (file_exists(public_path($this->image_path))) {
            return asset($this->image_path);
        }

        // Sent before the files went to the public folder: in storage/app/public, served by the system.
        $old = self::storedFile($this->image_path);
        return $old ? route('announcements.file', basename($old)) : null;
    }

    /**
     * The picture to show: the category's banner (the default picture for the old types).
     */
    public function getPictureUrlAttribute(): string
    {
        return asset(AnnouncementCategories::has($this->type) ? AnnouncementCategories::banner($this->type) : self::DEFAULT_PICTURE);
    }

    /** The category's fields as they read (see AnnouncementCategories::describe()). */
    public function getDetailLinesAttribute(): array
    {
        return AnnouncementCategories::describe($this->type, $this->details);
    }

    /**
     * Where a file sent before the public folder was used is ("storage/announcements/x.png" -> storage/app/public/
     * announcements/x.png), or null.
     */
    public static function storedFile(string $path): ?string
    {
        $file = storage_path('app/public/announcements/' . basename($path));
        return str_starts_with($path, 'storage/announcements/') && is_file($file) ? $file : null;
    }

    public function isPdf(): bool
    {
        return str_ends_with(strtolower((string) $this->image_path), '.pdf');
    }

    public function getExpiresAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }
}
