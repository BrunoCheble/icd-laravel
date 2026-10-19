<?php

namespace App\Modules\Visitors\Services;

use App\Enums\VisitorStatus;
use App\Modules\Visitors\Models\Visitor;

class RemoveVisitorService
{
    public const DEACTIVATED = 'deactivated';
    public const DELETED = 'deleted';

    /**
     * An active visitor is only deactivated, keeping the reason in the observation; an inactive one is deleted.
     */
    public function execute(Visitor $visitor, ?string $reason = null): string
    {
        if ($visitor->isActive()) {
            $visitor->status = VisitorStatus::INACTIVED;
            $visitor->observation .= ' - Motivo da desativação: ' . trim((string) $reason);
            $visitor->save();
            return self::DEACTIVATED;
        }

        $visitor->delete();
        return self::DELETED;
    }
}
