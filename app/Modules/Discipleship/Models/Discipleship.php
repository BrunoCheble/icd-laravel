<?php

namespace App\Modules\Discipleship\Models;

use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member (the disciple) followed by another (the mentor, "responsável").
 */
class Discipleship extends Model
{
    protected $fillable = ['mentor_id', 'disciple_id', 'started_at', 'is_active', 'notes'];

    protected $casts = [
        'started_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mentor_id');
    }

    public function disciple(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'disciple_id');
    }
}
