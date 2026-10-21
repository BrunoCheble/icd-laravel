<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Modules\Members\Models\Member;

class GetUserFormOptionsService
{
    /**
     * The members for the picker, with what fills the user's fields ([id, name, document, phone, email]); the ones
     * who are already users are left out (except the user's own).
     */
    public function execute(?User $user = null): array
    {
        $taken = User::query()->whereNotNull('member_id')->when($user?->member_id, fn ($query) => $query->where('member_id', '!=', $user->member_id))->pluck('member_id');

        return Member::query()
            ->whereNotIn('id', $taken)
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'document_number', 'phone_number', 'email'])
            ->map(fn (Member $member) => [
                'id' => (string) $member->id,
                'name' => $member->full_name,
                'document' => $member->document_number,
                'phone' => $member->phone_number,
                'email' => $member->email,
            ])
            ->values()
            ->all();
    }
}
