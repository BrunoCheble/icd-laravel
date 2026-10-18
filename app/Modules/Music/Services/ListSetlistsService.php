<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Setlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListSetlistsService
{
    public const PER_PAGE = 15;

    /**
     * Setlists, newest event first, each with its songs (in order, with their key, tempo and minister in the setlist)
     * so the list can show them; filtered by title or minister.
     */
    public function execute(?string $search, ?int $page = null): LengthAwarePaginator
    {
        return $this->query($search)
            ->withCount('songs')
            ->with(['songs:id,title,artist,musical_key,bpm'])
            ->addSelect(['ministers' => DB::table('setlist_songs')
                ->selectRaw("GROUP_CONCAT(DISTINCT minister_name ORDER BY minister_name SEPARATOR ', ')")
                ->whereColumn('setlist_songs.setlist_id', 'setlists.id')])
            ->paginate(self::PER_PAGE, ['setlists.*'], 'page', $page);
    }

    /**
     * The page of the list (same filter and order) where a setlist is, to open the list right on it.
     */
    public function pageOf(Setlist $setlist, ?string $search): int
    {
        $before = $this->query($search)->where(function (Builder $query) use ($setlist) {
            if ($setlist->event_date === null) {
                // No date: after every dated setlist, then by title.
                $query->whereNotNull('event_date')->orWhere(fn ($q) => $q->whereNull('event_date')->where('title', '<', $setlist->title));
            } else {
                $date = $setlist->event_date->toDateString();
                $query->whereDate('event_date', '>', $date)
                    ->orWhere(fn ($q) => $q->whereDate('event_date', $date)->where('title', '<', $setlist->title));
            }
        })->count();

        return intdiv($before, self::PER_PAGE) + 1;
    }

    /**
     * The next setlist from today on (the one the band is preparing), or null.
     */
    public function next(): ?Setlist
    {
        return Setlist::whereDate('event_date', '>=', today())->orderBy('event_date')->orderBy('title')->first();
    }

    private function query(?string $search): Builder
    {
        return Setlist::query()
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('songs', fn ($query) => $query->where('setlist_songs.minister_name', 'like', "%{$search}%"))))
            ->orderByDesc('event_date')
            ->orderBy('title');
    }
}
