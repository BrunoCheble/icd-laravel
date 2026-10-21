<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\MinistryMember;
use App\Modules\Ministries\Support\MinistryBio;

class SaveMembershipService
{
    /** A member's role, order, whether active and biography in the ministry. */
    public function execute(MinistryMember $membership, array $data): MinistryMember
    {
        $membership->fill([
            'role' => $data['role'] ?? null,
            'active' => (bool) $data['active'],
            'sort_order' => (int) $data['sort_order'],
            'bio' => MinistryBio::clean($data['bio'] ?? null),
        ])->save();

        return $membership;
    }
}
