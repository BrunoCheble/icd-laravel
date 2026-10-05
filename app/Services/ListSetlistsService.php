<?php

namespace App\Services;

use App\Models\Setlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ListSetlistsService
{
    public function execute(?string $search): LengthAwarePaginator
    {
        return Setlist::withCount('songs')
            ->addSelect(['ministers' => DB::table('setlist_songs')
                ->selectRaw("GROUP_CONCAT(DISTINCT minister_name ORDER BY minister_name SEPARATOR ', ')")
                ->whereColumn('setlist_songs.setlist_id', 'setlists.id')])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('songs', fn ($query) => $query->where('setlist_songs.minister_name', 'like', "%{$search}%"))))
            ->orderByDesc('event_date')
            ->orderBy('title')
            ->paginate();
    }
}
