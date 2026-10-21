<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\MinistryRole;

class RemoveMinistryRoleService
{
    /** Deletes the role; who had it stays in the ministry, without a role. */
    public function execute(MinistryRole $role): void
    {
        $role->delete();
    }
}
