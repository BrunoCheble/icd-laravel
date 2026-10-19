<?php

namespace App\Modules\Prayers\Services;

use App\Modules\Prayers\Models\Prayer;

class RegisterPrayerService
{
    /**
     * Keeps a prayer request sent from the public page.
     */
    public function execute(array $data): Prayer
    {
        return Prayer::create([
            'name' => $data['name'],
            'request' => $data['request'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
        ]);
    }
}
