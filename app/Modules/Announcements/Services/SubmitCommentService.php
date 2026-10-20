<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Models\AnnouncementComment;
use App\Modules\Members\Models\Member;

class SubmitCommentService
{
    /**
     * A member's comment about a published announcement; it waits for approval before the site shows it.
     */
    public function execute(Announcement $announcement, Member $member, string $body): AnnouncementComment
    {
        return $announcement->comments()->create([
            'member_id' => $member->id,
            'body' => trim($body),
            'is_approved' => false,
        ]);
    }
}
