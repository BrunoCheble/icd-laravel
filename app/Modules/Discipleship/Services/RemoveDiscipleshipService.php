<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\Discipleship;

class RemoveDiscipleshipService
{
    /** Deletes the discipleship; its meetings stay (they belong to the mentor). */
    public function execute(Discipleship $discipleship): void
    {
        $discipleship->delete();
    }
}
