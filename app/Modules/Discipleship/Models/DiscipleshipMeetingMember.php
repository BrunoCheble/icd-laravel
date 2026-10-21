<?php

namespace App\Modules\Discipleship\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/** A disciple at a meeting: "attended" true / false once marked, null before. */
class DiscipleshipMeetingMember extends Pivot
{
    protected $table = 'discipleship_meeting_member';

    public $timestamps = false;

    protected $casts = ['attended' => 'boolean'];
}
