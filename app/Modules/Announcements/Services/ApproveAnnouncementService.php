<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;

class ApproveAnnouncementService
{
    /**
     * Approves the announcement (now) or takes the approval back.
     */
    public function execute(Announcement $announcement, bool $approved): void
    {
        $announcement->update(['is_approved' => $approved, 'approved_at' => $approved ? now() : null]);
    }
}
