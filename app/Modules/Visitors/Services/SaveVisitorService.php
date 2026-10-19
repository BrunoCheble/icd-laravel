<?php

namespace App\Modules\Visitors\Services;

use App\Enums\VisitorStatus;
use App\Modules\Visitors\Models\Visitor;

class SaveVisitorService
{
    /**
     * Creates the visitor (active), or updates the one given.
     */
    public function execute(array $data, ?Visitor $visitor = null): Visitor
    {
        if ($visitor) {
            $visitor->update($data);
            return $visitor;
        }

        return Visitor::create(['status' => VisitorStatus::ACTIVED] + $data);
    }
}
