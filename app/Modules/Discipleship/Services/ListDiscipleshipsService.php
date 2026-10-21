<?php

namespace App\Modules\Discipleship\Services;

use App\Models\User;
use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use App\Modules\Discipleship\Support\DiscipleshipAccess;
use Illuminate\Support\Collection;

class ListDiscipleshipsService
{
    /**
     * What the user handles (see DiscipleshipAccess), optionally of one mentor: the meetings to come, the ones
     * whose time went by without being marked, the latest marked ones, and the disciples by mentor, each with their
     * next meeting and the last one held.
     */
    public function execute(User $user, ?int $mentor = null): array
    {
        $meetings = DiscipleshipAccess::scope(DiscipleshipMeeting::query(), $user)
            ->when($mentor, fn ($query) => $query->where('mentor_id', $mentor))
            ->where('scheduled_at', '>=', now()->subYear())
            ->with(['disciples:id,first_name,last_name', 'mentor:id,first_name,last_name'])
            ->withCount('comments')
            ->orderBy('scheduled_at')
            ->get();

        $discipleships = DiscipleshipAccess::scope(Discipleship::query(), $user)
            ->when($mentor, fn ($query) => $query->where('mentor_id', $mentor))
            ->with(['mentor:id,first_name,last_name,phone_number', 'disciple:id,first_name,last_name,phone_number'])
            ->get();
        // The meetings held with each disciple, with any mentor (an earlier one too).
        $held = DiscipleshipMeeting::query()->where('status', DiscipleshipMeeting::HELD)
            ->whereHas('disciples', fn ($query) => $query->whereIn('members.id', $discipleships->pluck('disciple_id')))
            ->with('disciples:id')->orderBy('scheduled_at')->get();

        $discipleships = $discipleships
            ->map(function (Discipleship $discipleship) use ($meetings, $held) {
                $theirs = $meetings->filter(fn (DiscipleshipMeeting $meeting) => $meeting->mentor_id === $discipleship->mentor_id
                    && $meeting->disciples->contains('id', $discipleship->disciple_id));
                $discipleship->next_meeting = $theirs->first(fn ($meeting) => $meeting->status === DiscipleshipMeeting::SCHEDULED && $meeting->scheduled_at->isFuture());
                // The meetings held that the disciple was at (not marked one by one: they were).
                $attended = $held->filter(function (DiscipleshipMeeting $meeting) use ($discipleship) {
                    $disciple = $meeting->disciples->firstWhere('id', $discipleship->disciple_id);

                    return $disciple && $disciple->pivot->attended !== false;
                });
                $discipleship->last_held = $attended->last();
                $discipleship->held_count = $attended->count();

                return $discipleship;
            });

        return [
            'upcoming' => $meetings->filter(fn ($meeting) => $meeting->status === DiscipleshipMeeting::SCHEDULED && $meeting->scheduled_at->isFuture())->values(),
            'overdue' => $meetings->filter(fn ($meeting) => $meeting->isOverdue())->values(),
            'recent' => $meetings->where('status', '!=', DiscipleshipMeeting::SCHEDULED)->sortByDesc('scheduled_at')->take(15)->values(),
            'groups' => $this->byMentor($discipleships),
        ];
    }

    /** The discipleships by mentor (by name), the active ones first in each. */
    private function byMentor(Collection $discipleships): Collection
    {
        return $discipleships->groupBy('mentor_id')
            ->map(fn (Collection $items) => [
                'mentor' => $items->first()->mentor,
                'items' => $items->sortBy([['is_active', 'desc'], fn ($a, $b) => strcmp($a->disciple->full_name, $b->disciple->full_name)])->values(),
            ])
            ->sortBy(fn ($group) => $group['mentor']->full_name)
            ->values();
    }
}
