<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Models\MinistryMember;

class ListPastorsService
{
    /**
     * The site's "Pastores": the active members of the pastoral ministry, in their order, with name, role, photo
     * (null without one) and biography (null without one).
     */
    public function execute(): array
    {
        $ministry = Ministry::query()->where('key', Ministry::PASTORAL)->first();
        if (! $ministry) {
            return [];
        }

        return $ministry->memberships()->where('active', true)->with(['member', 'ministryRole'])->get()
            ->filter(fn (MinistryMember $membership) => $membership->member)
            ->map(fn (MinistryMember $membership) => [
                'id' => $membership->id,
                'name' => $membership->member->full_name,
                'role' => $membership->roleName(),
                'photo' => $membership->member->photo && is_file(public_path('images/' . $membership->member->photo))
                    ? asset('images/' . $membership->member->photo) : null,
                'bio' => $membership->bio ?: null,
            ])
            ->values()->all();
    }
}
