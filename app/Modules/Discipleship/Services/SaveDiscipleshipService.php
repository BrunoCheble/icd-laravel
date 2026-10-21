<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\Discipleship;

class SaveDiscipleshipService
{
    public function execute(array $data, ?Discipleship $discipleship = null): Discipleship
    {
        $discipleship ??= new Discipleship(['is_active' => true]);
        $discipleship->fill([
            'mentor_id' => $data['mentor_id'],
            'disciple_id' => $data['disciple_id'],
            'started_at' => $data['started_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? $discipleship->is_active ?? true,
        ])->save();

        return $discipleship;
    }
}
