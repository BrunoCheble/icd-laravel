<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;
use Illuminate\Database\Eloquent\Collection;

class ListMinistriesService
{
    /** The ministries by name, with how many members (active) each has. */
    public function execute(): Collection
    {
        return Ministry::query()
            ->withCount(['memberships as members_count' => fn ($query) => $query->where('active', true)])
            ->orderBy('name')
            ->get();
    }
}
