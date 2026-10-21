<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use Illuminate\Support\Collection;

/**
 * The reminders of the meetings to their mentors (see SendPushDigestService): in the morning, the day's meetings;
 * and about BEFORE_HOURS before each one. A meeting near enough gets only the second (it says the time too). Each
 * is marked once given, so it is not given again.
 */
class SendMeetingRemindersService
{
    public const BEFORE_HOURS = 2;

    /** The notifications due now, by mentor (member id): [mentor_id => [notification, …]]. */
    public function due(bool $withMorning): array
    {
        $now = now();
        $soon = DiscipleshipMeeting::query()->with('disciples:id,first_name,last_name')
            ->where('status', DiscipleshipMeeting::SCHEDULED)->whereNull('reminded_before_at')
            ->whereBetween('scheduled_at', [$now, $now->copy()->addHours(self::BEFORE_HOURS)])
            ->orderBy('scheduled_at')->get();

        $today = collect();
        if ($withMorning) {
            $day = $now->copy()->timezone(DiscipleshipMeeting::TIMEZONE);
            $today = DiscipleshipMeeting::query()->with('disciples:id,first_name,last_name')
                ->where('status', DiscipleshipMeeting::SCHEDULED)->whereNull('reminded_morning_at')
                ->whereBetween('scheduled_at', [$day->copy()->startOfDay()->utc(), $day->copy()->endOfDay()->utc()])
                ->whereNotIn('id', $soon->pluck('id'))
                ->orderBy('scheduled_at')->get();
        }

        $notifications = [];
        foreach ($today->groupBy('mentor_id') as $mentor => $meetings) {
            $notifications[$mentor][] = [
                'title' => trans_choice('Discipleship meeting today|:count discipleship meetings today', $meetings->count(), ['count' => $meetings->count()]),
                'body' => $meetings->map(fn ($meeting) => $this->line($meeting))->implode("\n"),
                'url' => route('discipleships.index'),
                'tag' => 'discipleship-day-' . $mentor,
            ];
        }
        foreach ($soon as $meeting) {
            $notifications[$meeting->mentor_id][] = [
                'title' => __('Discipleship meeting at :time', ['time' => $meeting->localAt()->format('H:i')]),
                'body' => $this->line($meeting, false),
                'url' => route('discipleships.meetings.show', $meeting),
                'tag' => 'discipleship-' . $meeting->id,
            ];
        }

        $this->mark($today, 'reminded_morning_at');
        $this->mark($soon, 'reminded_before_at');

        return $notifications;
    }

    private function line(DiscipleshipMeeting $meeting, bool $withTime = true): string
    {
        return ($withTime ? $meeting->localAt()->format('H:i') . ' · ' : '') . $meeting->disciplesNames()
            . ($meeting->location ? ' · ' . $meeting->location : '');
    }

    private function mark(Collection $meetings, string $column): void
    {
        if ($meetings->isNotEmpty()) {
            DiscipleshipMeeting::query()->whereIn('id', $meetings->pluck('id'))->update([$column => now()]);
        }
    }
}
