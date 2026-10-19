<?php

namespace App\Modules\Prayers\Services;

use App\Modules\Prayers\Models\Prayer;

class RemovePrayerService
{
    /**
     * Deletes the prayer request.
     */
    public function execute(Prayer $prayer): void
    {
        $prayer->delete();
    }
}
