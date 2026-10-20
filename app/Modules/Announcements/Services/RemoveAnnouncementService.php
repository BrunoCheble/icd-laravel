<?php

namespace App\Modules\Announcements\Services;

use App\Modules\Announcements\Models\Announcement;

class RemoveAnnouncementService
{
    public function __construct(private AnnouncementPhotosService $photos)
    {
    }

    /**
     * Deletes the announcement, with its photos and its PDF (comments and stars go with it).
     */
    public function execute(Announcement $announcement): void
    {
        $this->photos->delete([...($announcement->photos ?? []), $announcement->image_path]);
        $announcement->delete();
    }
}
