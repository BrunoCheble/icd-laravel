<?php

namespace App\Modules\Discipleship\Services;

use App\Models\User;
use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use App\Modules\Discipleship\Models\DiscipleshipMeetingComment;

class AddMeetingCommentService
{
    public function execute(DiscipleshipMeeting $meeting, User $user, string $body): DiscipleshipMeetingComment
    {
        return $meeting->comments()->create(['user_id' => $user->id, 'body' => trim($body)]);
    }
}
