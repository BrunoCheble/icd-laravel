<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;
use App\Models\SongSuggestion;

class StoreSongSuggestionService
{
    /**
     * Keeps a suggestion of chord corrections for an admin to review: one change per chord and one insertion per
     * place (the last one sent wins), changes that keep the chord as it is left out.
     */
    public function execute(Song $song, ?Setlist $setlist, array $data): ?SongSuggestion
    {
        $changes = collect($data['changes'])
            ->map(function ($change) {
                $insert = ($change['type'] ?? 'change') === 'insert';
                $item = ['n' => (int) $change['n'], 'from' => trim($change['from']), 'to' => isset($change['to']) ? trim($change['to']) : null];

                return $insert ? ['type' => 'insert'] + $item + ['offset' => (int) $change['offset']] : $item;
            })
            ->keyBy(fn ($change) => sprintf('%06d-%06d', $change['n'], $change['offset'] ?? 0))
            ->reject(fn ($change) => ! isset($change['type']) && $change['to'] === $change['from'])
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
