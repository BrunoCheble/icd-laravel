<?php

namespace App\Modules\Ministries\Models;

use App\Modules\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ministry of the church (ICD Music, ICD Kids…) and its members. The one with key PASTORAL is the site's
 * "Pastores". Its page on the site (site_*): shown or not, order, group (a ministry or a department), icon, the
 * card's tag, title and summary, the page's title and its text in sections (see MinistryBio).
 */
class Ministry extends Model
{
    public const PASTORAL = 'pastoral';

    /** The groups of the site's ministries, with the label the page shows. */
    public const SITE_GROUPS = ['ministries' => 'Ministérios', 'departments' => 'Departamentos'];

    protected $fillable = ['name', 'key', 'site_visible', 'site_order', 'site_group', 'site_icon', 'site_tag', 'site_title',
        'site_page_title', 'site_summary', 'site_page'];

    protected $casts = [
        'site_visible' => 'boolean',
        'site_order' => 'integer',
        'site_page' => 'array',
    ];

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
