<?php

namespace App\Modules\Visitors\Services;

use App\Enums\InvitedBy;
use App\Enums\VisitorStatus;
use App\Helpers\NameHelper;
use App\Modules\Visitors\Models\Visitor;

class RegisterPublicVisitorService
{
    // What the reception page keeps for the next visitor (often of the same family): who registers, how they heard
    // of the church, the phone and the city.
    public const REMEMBERED = ['visitor_created_by', 'invited_by_other', 'invited_by', 'phone_number', 'city'];

    /**
     * Registers a visitor from the reception page: "invited_by" is the person who invited them, or how they heard of
     * the church (Instagram, Google…). The details shared by a family are kept in the session for the next one.
     */
    public function execute(array $data): Visitor
    {
        $how = $data['invited_by_other'] ?? InvitedBy::WHO;
        $invitedBy = $how === InvitedBy::WHO ? (NameHelper::normalizeName((string) ($data['invited_by'] ?? '')) ?: null) : $how;

        $visitor = Visitor::create([
            'name' => $data['name'],
            'gender' => $data['gender'],
            'phone_number' => $data['phone_number'] ?? null,
            'city' => $data['city'] ?? null,
            'group' => $data['group'] ?? null,
            'invited_by' => $invitedBy,
            'created_by' => $data['created_by'],
            'status' => VisitorStatus::ACTIVED,
        ]);

        session([
            'visitor_created_by' => $visitor->created_by,
            'invited_by_other' => $how,
            'invited_by' => $how === InvitedBy::WHO ? $invitedBy : null,
            'phone_number' => $visitor->phone_number,
            'city' => $visitor->city,
        ]);

        return $visitor;
    }

    /**
     * A new family: only who registers is kept.
     */
    public function forgetFamily(): void
    {
        session()->forget(array_diff(self::REMEMBERED, ['visitor_created_by']));
    }
}
