<?php

namespace App\Modules\Ministries\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A role in a ministry ("cargo": Pastor Presidente, Vocal, Tecladista…). */
class MinistryRole extends Model
{
    protected $fillable = ['ministry_id', 'name', 'sort_order'];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }
}
