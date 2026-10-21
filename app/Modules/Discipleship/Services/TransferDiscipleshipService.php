<?php

namespace App\Modules\Discipleship\Services;

use App\Modules\Discipleship\Models\Discipleship;
use Illuminate\Support\Facades\DB;

class TransferDiscipleshipService
{
    /**
     * The disciple goes on with another mentor: this discipleship ends and one with the new mentor starts today (or
     * the earlier one with them comes back). The meetings stay as they were; the new mentor sees them in the history.
     */
    public function execute(Discipleship $discipleship, int $mentorId): Discipleship
    {
        return DB::transaction(function () use ($discipleship, $mentorId) {
            $discipleship->update(['is_active' => false]);

            $next = Discipleship::query()->firstOrNew(['mentor_id' => $mentorId, 'disciple_id' => $discipleship->disciple_id]);
            $next->fill(['is_active' => true, 'started_at' => now()->toDateString()]);
            $next->notes ??= $discipleship->notes;
            $next->save();

            return $next;
        });
    }
}
