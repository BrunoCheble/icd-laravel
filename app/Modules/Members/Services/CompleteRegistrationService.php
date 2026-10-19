<?php

namespace App\Modules\Members\Services;

use App\Enums\MembershipStatus;
use App\Helpers\NameHelper;
use App\Modules\Members\Models\Member;
use Carbon\Carbon;

class CompleteRegistrationService
{
    public function __construct(private GetMemberPendingInformationService $pending)
    {
    }

    /**
     * Saves what a pending member filled in on the "complete your registration" page (only the fields still
     * missing; empty ones are left as they are). The spouse is linked when its mobile number finds them (see
     * findSpouse()); otherwise the name is kept as the spouse's (spouse_name) and the number goes to the notes.
     * A pending member with nothing left to send becomes active.
     */
    public function execute(Member $member, array $data): void
    {
        $data = array_filter($data, fn ($value) => $value !== null && $value !== '');

        if (isset($data['spouse_name']) || isset($data['spouse_phone'])) {
            $phone = $data['spouse_phone'] ?? null;
            $spouse = $phone ? $this->findSpouse($member, $phone) : null;

            if ($spouse) {
                // A member: linked (both ways, by the observer), keeping the spouse's wedding date when the member
                // has none.
                $data['spouse_id'] = (string) $spouse->id;
                unset($data['spouse_name']);
                if (! isset($data['date_joined']) && ! $member->date_joined && $spouse->date_joined) {
                    $data['date_joined'] = Carbon::parse($spouse->date_joined)->toDateString();
                }
                $note = 'Cônjuge associado pelo próprio membro (telemóvel ' . $phone . ')';
            } else {
                // Not a member (or not found): the name is kept as the spouse's; the number, in the notes.
                if (isset($data['spouse_name'])) {
                    $data['spouse_name'] = NameHelper::normalizeName($data['spouse_name']);
                }
                $note = $phone ? 'Telemóvel do cônjuge (indicado pelo membro): ' . $phone : null;
            }
            if ($note) {
                $member->notes = trim($member->notes . "\n" . $note);
            }
            unset($data['spouse_phone']);
        }

        foreach (['address', 'city'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = NameHelper::normalizeName($data[$field]);
            }
        }
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        $member->fill($data);

        // Everything sent: the registration is complete, the member is active.
        if ($member->isPending() && ! $this->pending->fields($member)) {
            $member->membership_status = MembershipStatus::ACTIVED;
            $member->notes = trim($member->notes . "\n" . 'Registo completado pelo próprio membro em ' . now()->format('d/m/Y'));
        }

        $member->save();
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
