<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\AnnouncementComment;

class RemoveCommentService
{
    public function execute(AnnouncementComment $comment): void
    {
        $comment->delete();
    }
}
