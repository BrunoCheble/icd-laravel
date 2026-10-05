<?php

namespace App\Services;

use App\Models\Setlist;
use Illuminate\Support\Facades\DB;

class SaveSetlistService
{
    public function execute(array $data, ?Setlist $setlist = null): Setlist
    {
        return DB::transaction(function () use ($data, $setlist) {
            $values = [
                'title'      => $data['title'],
                'event_date' => $data['event_date'] ?? null,
            ];

            $setlist = $setlist ? tap($setlist)->update($values) : Setlist::create($values);

            // Only the setlist_songs links are changed; songs themselves are never touched.
            $setlist->songs()->sync($this->links($data['songs'] ?? []));

            return $setlist;
        });
    }

    /**
     * Pivot data in the submitted order: position, key and minister of each song in this setlist.
     */
    private function links(array $songs): array
    {
        $links = [];
        foreach (array_values($songs) as $index => $song) {
            $links[(int) $song['song_id']] = [
                'position'      => $index + 1,
                'musical_key'   => $song['musical_key'] ?? null,
                'minister_name' => $song['minister_name'] ?? null,
            ];
        }

        return $links;
    }
}
