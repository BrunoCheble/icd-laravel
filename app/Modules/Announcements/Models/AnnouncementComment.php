<?php

namespace App\Modules\Announcements\Models;

use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A comment a member sent about an announcement (found by the NIF); the site shows it once approved.
 */
class AnnouncementComment extends Model
{
    protected $fillable = [
        'announcement_id',
        'member_id',
        'body',
        'is_approved',
        'approved_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // The site's list carries the approved comments.
        $forget = fn () => Cache::forget(Announcement::PUBLIC_CACHE);
        static::saved($forget);
        static::deleted($forget);
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
