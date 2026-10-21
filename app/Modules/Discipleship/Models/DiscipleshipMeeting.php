<?php

namespace App\Modules\Discipleship\Models;

use App\Modules\Members\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A meeting of a mentor with one or more of their disciples, on a day and at a time (kept in UTC, shown in Lisbon
 * time): scheduled, then marked as held (who attended) or not held (and why).
 */
class DiscipleshipMeeting extends Model
{
    public const TIMEZONE = 'Europe/Lisbon';

    public const SCHEDULED = 'scheduled';
    public const HELD = 'held';
    public const NOT_HELD = 'not_held';

    // Why a meeting did not take place.
    public const REASONS = [
        'disciple_absent' => 'The disciple could not come',
        'mentor_unavailable' => 'The mentor could not come',
        'postponed' => 'Postponed',
        'illness' => 'Illness',
        'other' => 'Other',
    ];

    protected $fillable = ['mentor_id', 'scheduled_at', 'location', 'status', 'reason', 'reason_note', 'reminded_morning_at', 'reminded_before_at'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'reminded_morning_at' => 'datetime',
        'reminded_before_at' => 'datetime',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mentor_id');
    }

    /** The disciples at the meeting (pivot "attended": whether they were there, once it is marked). */
    public function disciples(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'discipleship_meeting_member', 'meeting_id', 'member_id')->using(DiscipleshipMeetingMember::class)->withPivot('attended');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DiscipleshipMeetingComment::class, 'meeting_id')->latest();
    }

    /** The day and time in Lisbon. */
    public function localAt(): Carbon
    {
        return $this->scheduled_at->copy()->timezone(self::TIMEZONE);
    }

    /** Still scheduled though its time went by: waiting to be marked. */
    public function isOverdue(): bool
    {
        return $this->status === self::SCHEDULED && $this->scheduled_at->isPast();
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', self::SCHEDULED)->where('scheduled_at', '>=', now())->orderBy('scheduled_at');
    }

    public function disciplesNames(): string
    {
        return $this->disciples->map(fn (Member $member) => $member->full_name)->implode(', ');
    }
}
