<?php

namespace App\Modules\Members\Services;

use App\Helpers\NameHelper;
use App\Modules\Members\Models\Member;
use Carbon\Carbon;

class CompleteRegistrationService
{
    /**
     * Saves what a pending member filled in on the "complete your registration" page (only the fields still
     * missing; empty ones are left as they are). The spouse is linked when its mobile number finds them (see
     * findSpouse()); otherwise the name and number go to the notes, to link the spouse by hand.
     */
    public function execute(Member $member, array $data): void
    {
        $data = array_filter($data, fn ($value) => $value !== null && $value !== '');

        if (isset($data['spouse_name']) || isset($data['spouse_phone'])) {
            $name = isset($data['spouse_name']) ? NameHelper::normalizeName($data['spouse_name']) : null;
            $phone = $data['spouse_phone'] ?? null;
            $spouse = $phone ? $this->findSpouse($member, $phone) : null;

            if ($spouse) {
                $data['spouse_id'] = (string) $spouse->id;
                // The observer gives both the member's wedding date: keep the spouse's when the member has none.
                if (! isset($data['date_joined']) && ! $member->date_joined && $spouse->date_joined) {
                    $data['date_joined'] = Carbon::parse($spouse->date_joined)->toDateString();
                }
                $note = 'Cônjuge associado pelo próprio membro (telemóvel ' . $phone . ')';
            } else {
                $note = GetMemberPendingInformationService::SPOUSE_NOTE . collect([$name, $phone ? 'telemóvel ' . $phone : null])->filter()->implode(' · ');
            }
            $member->notes = trim($member->notes . "\n" . $note);
            unset($data['spouse_name'], $data['spouse_phone']);
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

    /**
     * The member with this mobile number who can be the spouse: only one with it, of the other gender, not the
     * member, and not married to someone else in the church yet. Anything else (nobody, or doubt) links nobody, so a
     * number typed on the page cannot tie the member to a stranger.
     */
    private function findSpouse(Member $member, string $phone): ?Member
    {
        $digits = fn ($value) => preg_replace('/^(00)?351(?=\d{9}$)/', '', preg_replace('/\D+/', '', (string) $value));

        // Numbers are written in many ways ("+351 912 345 678", "912-345-678"): compared by their digits.
        $candidates = Member::query()
            ->where('id', '!=', $member->id)
            ->whereNotNull('phone_number')
            ->get(['id', 'phone_number', 'gender', 'spouse_id', 'date_joined'])
            ->filter(fn (Member $other) => $digits($other->phone_number) === $phone);

        $spouse = $candidates->count() === 1 ? $candidates->first() : null;

        return $spouse && ! $spouse->spouse_id && $spouse->gender && $spouse->gender !== $member->gender ? $spouse : null;
    }
}
