<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;
use App\Models\SongSuggestion;

class StoreSongSuggestionService
{
    /**
     * Keeps a suggestion of chord corrections for an admin to review: one change per chord (the last one sent wins),
     * changes that keep the chord as it is left out.
     */
    public function execute(Song $song, ?Setlist $setlist, array $data): ?SongSuggestion
    {
        $changes = collect($data['changes'])
            ->keyBy(fn ($change) => (int) $change['n'])
            ->map(fn ($change, $n) => ['n' => $n, 'from' => trim($change['from']), 'to' => isset($change['to']) ? trim($change['to']) : null])
            ->reject(fn ($change) => $change['to'] === $change['from'])
            ->sortKeys()
            ->values()
            ->all();
        if ($changes === []) {
            return null;
        }

        return $song->suggestions()->create([
            'setlist_id' => $setlist?->id,
            'author'     => trim((string) ($data['author'] ?? '')) ?: null,
            'note'       => trim((string) ($data['note'] ?? '')) ?: null,
            'changes'    => $changes,
            'status'     => 'pending',
        ]);
    }
}
