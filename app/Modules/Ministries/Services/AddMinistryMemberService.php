<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Models\MinistryMember;

class AddMinistryMemberService
{
    /** Puts a member in the ministry, with a role (optional), last in the order. */
    public function execute(Ministry $ministry, array $data): MinistryMember
    {
        return $ministry->memberships()->create([
            'member_id' => $data['member_id'],
            'role' => $data['role'] ?? null,
            'active' => true,
            'sort_order' => (int) $ministry->memberships()->max('sort_order') + 1,
        ]);
    }
}
