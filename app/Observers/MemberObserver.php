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
        // if the spouse_id hasn't been changed, don't update it
        if (!$member->isDirty('spouse_id') || (!$member->getOriginal('spouse_id') && !$member->spouse_id)) {
            return;
        }

        // if the spouse_id has been changed to null, set the spouse_id of the old spouse to null
        if (!$member->spouse_id) {
            Member::where('spouse_id','=',$member->id)->update(['spouse_id' => null]);
            return;
        }

        // if the old spouse exists, update the spouse_id of the old spouse to null
        if ($oldSpouseId = $member->getOriginal('spouse_id')) {
            Member::find($oldSpouseId)->update(['spouse_id' => null]);
        }

        // if the spouse_id has been changed, update the spouse_id of the old spouse
        Member::where('id','=',$member->spouse_id, 'and', 'spouse_id', '!=', $member->id)->update(['spouse_id' => $member->id]);
    }
}
