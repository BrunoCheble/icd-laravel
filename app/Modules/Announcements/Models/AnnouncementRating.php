<?php

namespace App\Modules\Announcements\Models;

use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A member's stars (1 to 5) for an announcement: one per member, which they can change.
 */
class AnnouncementRating extends Model
{
    protected $fillable = [
        'announcement_id',
        'member_id',
        'stars',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];

    protected static function booted(): void
    {
        // The site's list carries each announcement's average.
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
