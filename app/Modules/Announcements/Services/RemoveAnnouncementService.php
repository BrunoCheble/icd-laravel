<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;

class RemoveAnnouncementService
{
    /**
     * Deletes the announcement.
     */
    public function execute(Announcement $announcement): void
    {
        $announcement->delete();
    }
}
