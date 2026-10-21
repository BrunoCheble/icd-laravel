<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\DiscipleshipMeeting;

class RemoveMeetingService
{
    public function execute(DiscipleshipMeeting $meeting): void
    {
        $meeting->delete();
    }
}
