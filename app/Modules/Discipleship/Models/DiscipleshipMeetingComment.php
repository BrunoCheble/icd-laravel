<?php

namespace App\Modules\Discipleship\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscipleshipMeetingComment extends Model
{
    protected $fillable = ['meeting_id', 'user_id', 'body'];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(DiscipleshipMeeting::class, 'meeting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
