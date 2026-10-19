<?php

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Member;
use Illuminate\Support\Facades\URL;

class GetRegistrationLinkService
{
    // How long a link sent to a member works.
    public const DAYS = 30;

    /**
     * The link a pending member gets (by WhatsApp) to fill in, on their own, what is missing from their
     * registration. Signed: it only opens that member's page, and only for DAYS days.
     */
    public function execute(Member $member): string
    {
        return URL::temporarySignedRoute('members.complete', now()->addDays(self::DAYS), ['member' => $member->id]);
    }
}
