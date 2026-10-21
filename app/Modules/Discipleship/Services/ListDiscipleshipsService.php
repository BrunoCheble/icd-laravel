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
            ->get()
            ->map(function (Discipleship $discipleship) use ($meetings) {
                $theirs = $meetings->filter(fn (DiscipleshipMeeting $meeting) => $meeting->mentor_id === $discipleship->mentor_id
                    && $meeting->disciples->contains('id', $discipleship->disciple_id));
                $discipleship->next_meeting = $theirs->first(fn ($meeting) => $meeting->status === DiscipleshipMeeting::SCHEDULED && $meeting->scheduled_at->isFuture());
                // The meetings held that the disciple was at (not marked one by one: they were).
                $held = $theirs->filter(fn ($meeting) => $meeting->status === DiscipleshipMeeting::HELD
                    && $meeting->disciples->firstWhere('id', $discipleship->disciple_id)?->pivot->attended !== false);
                $discipleship->last_held = $held->last();
                $discipleship->held_count = $held->count();

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
