<?php

namespace App\Modules\Visitors\Models;

use App\Enums\Gender;
use App\Enums\InvitedBy;
use App\Enums\VisitorGroup;
use App\Enums\VisitorStatus;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'phone_number',
        'invited_by',
        'group',
        'city',
        'observation',
        'created_by',
        'status',
    ];

    public function scopeActives($query)
    {
        return $query->where('status', VisitorStatus::ACTIVED);
    }

    public function getCreatedAtFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->created_at)->format('d/m/Y H:i');
    }

    public function getUpdatedAtFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->updated_at)->format('d/m/Y H:i');
    }

    public function getGenderNameAttribute()
    {
        return Gender::options()[$this->gender] ?? 'N/D';
    }

    public function getGroupNameAttribute()
    {
        return VisitorGroup::options()[$this->group] ?? $this->group;
    }

    // Who invited: a person's name, or how the visitor heard of the church (Instagram, Google…).
    public function getInvitedByNameAttribute()
    {
        return $this->invited_by === InvitedBy::WHO ? null : (InvitedBy::options()[$this->invited_by] ?? $this->invited_by);
    }

    public function getStatusNameAttribute()
    {
        return VisitorStatus::options()[$this->status] ?? 'N/D';
    }

    // Up to two initials of the name, for its avatar.
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);
        $first = mb_substr($words[0] ?? '?', 0, 1);
        $last = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }

    public function isActive()
    {
        return $this->status == VisitorStatus::ACTIVED;
    }

    public function isInactive()
    {
        return $this->status == VisitorStatus::INACTIVED;
    }
}
