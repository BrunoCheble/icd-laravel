<?php

namespace App\Observers;

use App\Models\Member;

class MemberObserver
{
    /**
     * Handle the Member "created" event.
     */
    public function created(Member $member): void
    {
        $this->updateSpouse($member);
    }

    /**
     * Handle the Member "updated" event.
     */
    public function updated(Member $member): void
    {
        $this->updateSpouse($member);
    }

    /**
     * Handle the Member "deleted" event.
     */
    public function deleted(Member $member): void
    {
        //
    }

    /**
     * Handle the Member "restored" event.
     */
    public function restored(Member $member): void
    {
        //
    }

    /**
     * Handle the Member "force deleted" event.
     */
    public function forceDeleted(Member $member): void
    {
        //
    }

    /**
     * Update the spouse_id of the spouse.
     *
     * @param Member $member
     * @return void
     */
    protected function updateSpouse(Member $member): void
    {
        $oldSpouseId = $member->getOriginal('spouse_id');

        $changedSpouse = $oldSpouseId != $member->spouse_id;
        $changeDateJoined = $member->getOriginal('date_joined') != $member->date_joined;

        // if the spouse_id hasn't been changed, don't update it
        if (!$changedSpouse && !$changeDateJoined) {
            return;
        }

        // if the spouse_id has been changed to null, set the spouse_id of the old spouse to null
        if (!$member->spouse_id) {
            Member::where('spouse_id','=',$member->id)->update(['spouse_id' => null, 'date_joined' => null]);
            return;
        }

        // if the spouse_id has been changed, update the spouse_id of the old spouse
        if ($changedSpouse || $changeDateJoined) {
            Member::where('id','=',$member->spouse_id)->update(['spouse_id' => $member->id, 'date_joined' => $member->date_joined]);
        }

        // if the old spouse exists, update the spouse_id of the old spouse to null
        if ($oldSpouseId && $changedSpouse) {
            Member::where('id','=',$oldSpouseId)->update(['spouse_id' => null, 'date_joined' => null]);
        }
    }
}
