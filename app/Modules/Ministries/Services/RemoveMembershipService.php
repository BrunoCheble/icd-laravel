<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\MinistryMember;

class RemoveMembershipService
{
    public function execute(MinistryMember $membership): void
    {
        $membership->delete();
    }
}
