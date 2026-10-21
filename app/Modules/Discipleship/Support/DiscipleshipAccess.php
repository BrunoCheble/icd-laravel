<?php

namespace App\Modules\Discipleship\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Whose discipleships a user handles: administrators, everyone's; the others, only their own as mentor (the member
 * their user is). A user who is no member has none.
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

    /** Stops the request (403) when the record is not the user's. */
    public static function authorize(User $user, int $mentorId): void
    {
        abort_unless(self::allows($user, $mentorId), 403, __('You do not have access to this page.'));
    }
}
