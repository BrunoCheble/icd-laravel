<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\Ministry;

class RemoveMinistryService
{
    /** Deletes the ministry, its roles and who was in it. */
    public function execute(Ministry $ministry): void
    {
        $ministry->delete();
    }
}
