<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Collection;

class SearchSongsService
{
    public function execute(?string $term, int $limit = 20): Collection
    {
        $term = trim((string) $term);

        return Song::query()
            ->select('id', 'title', 'artist', 'musical_key')
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$term}%")
                ->orWhere('artist', 'like', "%{$term}%")))
            ->orderBy('title')
            ->limit($limit)
            ->get();
    }
}
