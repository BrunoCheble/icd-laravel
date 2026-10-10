<?php

namespace App\Services;

use App\Models\Song;

class GetSongCatalogService
{
    /**
     * Every song as the setlist app shows it (see GetPublicSetlistService::songPayload), saved with the offline copy
     * so songs can be searched and added to a setlist without a connection (only on that device).
     */
    public function execute(): array
    {
        return Song::query()
            ->with('versions')
            ->orderBy('title')
            ->get()
            ->map(fn (Song $song) => GetPublicSetlistService::songPayload($song))
            ->all();
    }
}
