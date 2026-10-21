<?php

namespace App\Modules\Discipleship\Services;

use App\Models\User;
use App\Modules\Discipleship\Models\Discipleship;
use App\Modules\Discipleship\Support\DiscipleshipAccess;
use App\Modules\Members\Models\Member;

class GetDiscipleshipFormOptionsService
{
    /** The members for the pickers: [id, name, document]. */
    public function people(): array
    {
        return Member::query()->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'document_number'])
            ->map(fn (Member $member) => ['id' => (string) $member->id, 'name' => $member->full_name, 'document' => $member->document_number])
            ->values()->all();
    }

    /**
     * The mentors the user handles, each with their active disciples (and the ones given, e.g. a meeting's):
     * [{id, name, disciples: [{id, name}]}].
     */
    public function mentors(User $user, array $keep = []): array
    {
        return DiscipleshipAccess::scope(Discipleship::query(), $user)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('disciple_id', $keep))
            ->with(['mentor:id,first_name,last_name', 'disciple:id,first_name,last_name'])
            ->get()
            ->groupBy('mentor_id')
            ->map(fn ($items) => [
                'id' => (string) $items->first()->mentor_id,
                'name' => $items->first()->mentor->full_name,
                'disciples' => $items->map(fn ($item) => ['id' => (string) $item->disciple_id, 'name' => $item->disciple->full_name])
                    ->sortBy('name')->values()->all(),
            ])
            ->sortBy('name')->values()->all();
    }
}
