<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\DiscipleshipMeeting;

class MarkMeetingService
{
    /**
     * Whether the meeting took place: held (and which disciples were there), not held (and why) or back to
     * scheduled.
     */
    public function execute(DiscipleshipMeeting $meeting, array $data): void
    {
        $status = $data['status'];
        $meeting->fill([
            'status' => $status,
            'reason' => $status === DiscipleshipMeeting::NOT_HELD ? $data['reason'] : null,
            'reason_note' => $status === DiscipleshipMeeting::NOT_HELD ? ($data['reason_note'] ?? null) : null,
        ])->save();

        $attended = collect($data['attended'] ?? [])->map(fn ($id) => (int) $id);
        foreach ($meeting->disciples as $disciple) {
            $meeting->disciples()->updateExistingPivot($disciple->id, [
                'attended' => $status === DiscipleshipMeeting::HELD ? $attended->contains($disciple->id) : null,
            ]);
        }
    }
}
