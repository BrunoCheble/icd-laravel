<?php

namespace App\Modules\Discipleship\Support;

use App\Models\User;
use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Models\DiscipleshipMeeting;
use Illuminate\Database\Eloquent\Builder;

/**
 * Whose discipleships a user handles: administrators, everyone's; the others, only their own as mentor (the member
 * their user is). A user who is no member has none. The history is the member's: a mentor also sees (and comments)
 * the earlier meetings of the disciples they follow now, with their previous mentors.
 */
class DiscipleshipAccess
{
    /** The mentor the user is limited to (0: none), or null for everyone's. */
    public static function mentorOf(User $user): ?int
    {
        return $user->is_admin ? null : (int) $user->member_id;
    }

    /** The query limited to what the user handles (by its "mentor_id"). */
    public static function scope(Builder $query, User $user): Builder
    {
        $mentor = self::mentorOf($user);

        return $mentor === null ? $query : $query->where($query->getModel()->getTable() . '.mentor_id', $mentor);
    }

    public static function allows(User $user, int $mentorId): bool
    {
        $mentor = self::mentorOf($user);

        return $mentor === null || ($mentor !== 0 && $mentor === $mentorId);
    }

    /** Whether the user may see the meeting: theirs, or of a disciple they follow now. */
    public static function canView(User $user, DiscipleshipMeeting $meeting): bool
    {
        if (self::allows($user, $meeting->mentor_id)) {
            return true;
        }
        $mentor = self::mentorOf($user);

        return $mentor !== 0 && Discipleship::query()->where('mentor_id', $mentor)->where('is_active', true)
            ->whereIn('disciple_id', $meeting->disciples()->select('members.id'))->exists();
    }

    /** Whether the user may see the history of the discipleship's disciple: its mentor while it goes on. */
    public static function canViewHistory(User $user, Discipleship $discipleship): bool
    {
        return $user->is_admin || ($discipleship->is_active && self::allows($user, $discipleship->mentor_id));
    }

    /** Stops the request (403) when the record is not the user's. */
    public static function authorize(User $user, int $mentorId): void
    {
        abort_unless(self::allows($user, $mentorId), 403, __('You do not have access to this page.'));
    }
}
