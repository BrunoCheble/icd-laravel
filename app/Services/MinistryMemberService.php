<?php

namespace App\Services;

use App\Models\Ministry;

class MinistryMemberService
{
    /**
     * Save or update the association of a member to a ministry.
     *
     * @param Ministry $ministry
     * @param array $data
     * @return void
     */
    public function saveOrUpdateMemberAssociation(Ministry $ministry, array $data)
    {
        // Set default values if not provided
        $role = $data['role'] ?? 'member';
        $active = $data['active'] ?? true;

        // Attach or update the member association using syncWithoutDetaching
        $ministry->members()->syncWithoutDetaching([
            $data['member_id'] => [
                'role' => $role,
                'active' => $active,
            ]
        ]);
    }
}
