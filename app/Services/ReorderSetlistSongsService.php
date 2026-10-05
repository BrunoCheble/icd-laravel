<?php

namespace App\Services;

use App\Models\Setlist;
use Illuminate\Support\Facades\DB;

class ReorderSetlistSongsService
{
    /**
     * Stores positions 1..n following the given song id order.
     */
    public function execute(Setlist $setlist, array $orderedSongIds): void
    {
        DB::transaction(function () use ($setlist, $orderedSongIds) {
            foreach (array_values($orderedSongIds) as $index => $songId) {
                $setlist->songs()->updateExistingPivot($songId, ['position' => $index + 1]);
            }
        });
    }
}
