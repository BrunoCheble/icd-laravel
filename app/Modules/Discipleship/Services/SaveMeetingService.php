<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use Carbon\Carbon;

class SaveMeetingService
{
    /**
     * Schedules a meeting (or changes it): the day and time given in Lisbon, the disciples at it. A new time means
     * new reminders.
     */
    public function execute(array $data, ?DiscipleshipMeeting $meeting = null): DiscipleshipMeeting
    {
        $meeting ??= new DiscipleshipMeeting(['status' => DiscipleshipMeeting::SCHEDULED]);
        $at = Carbon::parse($data['date'] . ' ' . $data['time'], DiscipleshipMeeting::TIMEZONE)->utc();

        if (! $meeting->exists || ! $meeting->scheduled_at->equalTo($at)) {
            $meeting->reminded_morning_at = null;
            $meeting->reminded_before_at = null;
        }
        $meeting->fill([
            'mentor_id' => $data['mentor_id'],
            'scheduled_at' => $at,
            'location' => $data['location'] ?? null,
        ])->save();

        // The disciples kept keep their attendance.
        $meeting->disciples()->sync(collect($data['disciples'])->map(fn ($id) => (int) $id)->all());

        return $meeting;
    }
}
