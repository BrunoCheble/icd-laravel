<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\AnnouncementComment;

class MarkCommentReadService
{
    /**
     * Marks the private comment as read by the team (now), or as not read again.
     */
    public function execute(AnnouncementComment $comment, bool $read): void
    {
        $comment->update(['read_at' => $read ? now() : null]);
    }
}
