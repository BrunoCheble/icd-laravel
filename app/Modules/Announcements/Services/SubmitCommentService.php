<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Models\AnnouncementComment;
use App\Modules\Members\Models\Member;

class SubmitCommentService
{
    /**
     * A member's private comment about a published announcement, for the church's team (never shown on the site).
     */
    public function execute(Announcement $announcement, Member $member, string $body): AnnouncementComment
    {
        return $announcement->comments()->create(['member_id' => $member->id, 'body' => trim($body)]);
    }
}
