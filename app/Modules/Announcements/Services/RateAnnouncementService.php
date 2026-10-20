<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Models\AnnouncementRating;
use App\Modules\Members\Models\Member;

class RateAnnouncementService
{
    /**
     * The member's stars for the announcement: one per member, so a new vote replaces the one given before.
     */
    public function execute(Announcement $announcement, Member $member, int $stars): AnnouncementRating
    {
        return AnnouncementRating::updateOrCreate(
            ['announcement_id' => $announcement->id, 'member_id' => $member->id],
            ['stars' => $stars],
        );
    }
}
