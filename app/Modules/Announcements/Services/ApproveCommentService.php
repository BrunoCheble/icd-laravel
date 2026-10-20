<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\AnnouncementComment;

class ApproveCommentService
{
    /**
     * Approves the comment (the site shows it) or takes the approval back.
     */
    public function execute(AnnouncementComment $comment, bool $approved): void
    {
        $comment->update(['is_approved' => $approved, 'approved_at' => $approved ? now() : null]);
    }
}
