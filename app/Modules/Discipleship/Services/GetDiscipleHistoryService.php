<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Models\DiscipleshipMeeting;

class GetDiscipleHistoryService
{
    /** All the disciple's meetings (with any mentor, newest first) and who followed them, newest first. */
    public function execute(Discipleship $discipleship): array
    {
        $disciple = $discipleship->disciple_id;

        return [
            'meetings' => DiscipleshipMeeting::query()
                ->whereHas('disciples', fn ($query) => $query->where('members.id', $disciple))
                ->with(['disciples:id,first_name,last_name', 'mentor:id,first_name,last_name'])
                ->withCount('comments')
                ->orderByDesc('scheduled_at')
                ->get(),
            'mentors' => Discipleship::query()->where('disciple_id', $disciple)->with('mentor:id,first_name,last_name')
                ->orderByDesc('is_active')->orderByDesc('started_at')->get(),
        ];
    }
}
