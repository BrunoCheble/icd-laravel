<?php

namespace App\Modules\Members\Services;

use App\Helpers\NameHelper;
use App\Modules\Members\Models\Member;

class CompleteRegistrationService
{
    /**
     * Saves what a pending member filled in on the "complete your registration" page (only the fields still
     * missing; empty ones are left as they are). The spouse's name goes to the notes, to link the spouse by hand.
     */
    public function execute(Member $member, array $data): void
    {
        $data = array_filter($data, fn ($value) => $value !== null && $value !== '');

        if (isset($data['spouse_name'])) {
            $member->notes = trim($member->notes . "\n" . GetMemberPendingInformationService::SPOUSE_NOTE . NameHelper::normalizeName($data['spouse_name']));
            unset($data['spouse_name']);
        }
        foreach (['address', 'city'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = NameHelper::normalizeName($data[$field]);
            }
        }
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        $member->fill($data)->save();
    }
}
