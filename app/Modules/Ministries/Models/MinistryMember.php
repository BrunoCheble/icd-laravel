<?php

namespace App\Modules\Ministries\Models;

use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member in a ministry: their role (optional free text: Pastor Presidente, Vocal…), whether they are active, the
 * order they show in, and an optional biography (see MinistryBio).
 */
class MinistryMember extends Model
{
    protected $table = 'ministry_members';

    protected $fillable = ['ministry_id', 'member_id', 'role', 'active', 'sort_order', 'bio'];

    protected $casts = [
        'active' => 'boolean',
        'bio' => 'array',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

}
