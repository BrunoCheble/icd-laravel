<?php

namespace App\Modules\Visitors\Services;

use App\Enums\VisitorStatus;
use App\Modules\Visitors\Models\Visitor;

class ActivateVisitorService
{
    /**
     * Makes an inactive visitor active again.
     */
    public function execute(Visitor $visitor): void
    {
        $visitor->status = VisitorStatus::ACTIVED;
        $visitor->save();
    }
}
