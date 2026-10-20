<?php

namespace App\Modules\Announcements\Models;

use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;

/**
 * A private comment a member (found by the NIF) sent to the church's team about an announcement; only the admin shows
 * it. read_at: when the team read it.
 */
class AnnouncementComment extends Model
{
    protected $fillable = [
        'announcement_id',
        'member_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
