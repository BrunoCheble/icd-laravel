<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;
use App\Modules\Ministries\Models\MinistryRole;

class SaveMinistryRoleService
{
    /** Adds a role to the ministry (last), or renames / reorders one. */
    public function execute(array $data, ?Ministry $ministry = null, ?MinistryRole $role = null): MinistryRole
    {
        $role ??= new MinistryRole([
            'ministry_id' => $ministry->id,
            'sort_order' => (int) $ministry->roles()->max('sort_order') + 1,
        ]);
        $role->fill(['name' => trim($data['name'])] + (isset($data['sort_order']) ? ['sort_order' => (int) $data['sort_order']] : []))->save();

        return $role;
    }
}
