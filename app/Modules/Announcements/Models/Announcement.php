<?php

namespace App\Modules\Announcements\Models;

use App\Enums\AnnouncementType;
use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    // Where an announcement stands: waiting for approval, published (approved, not expired), or expired.
    public const PENDING = 'pending';
    public const PUBLISHED = 'published';
    public const EXPIRED = 'expired';

    protected $fillable = [
        'member_id',
        'title',
        'description',
        'type',
        'contact',
        'link',
        'image_path',
        'is_approved',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /**
     * The member who sent the announcement.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
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
        return AnnouncementType::options()[$this->type] ?? (string) $this->type;
    }

    /**
     * The uploaded file's address ("storage/announcements/…", on the public disk), or null without one.
     */
    public function getUrlImageAttribute(): ?string
    {
        return $this->image_path && file_exists(public_path($this->image_path)) ? asset($this->image_path) : null;
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
