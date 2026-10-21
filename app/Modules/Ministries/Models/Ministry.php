<?php

namespace App\Modules\Ministries\Models;

use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ministry of the church (ICD Music, ICD Kids…) and its members. The one with key PASTORAL is the site's
 * "Pastores".
 */
class Ministry extends Model
{
    public const PASTORAL = 'pastoral';

    protected $fillable = ['name', 'key'];

    /** Who is in it (each with role, order, active and biography). */
    public function memberships(): HasMany
    {
        return $this->hasMany(MinistryMember::class)->orderBy('sort_order')->orderBy('id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'ministry_members');
    }

    public function isPastoral(): bool
    {
        return $this->key === self::PASTORAL;
    }
}
