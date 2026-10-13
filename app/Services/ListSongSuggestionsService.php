<?php

namespace App\Services;

use App\Models\SongSuggestion;

class ListSongSuggestionsService
{
    public function __construct(private ApplySongSuggestionService $apply)
    {
    }

    /**
     * Suggestions to review (pending first, then the last reviewed ones), each change described by its block and its
     * place there ("CHORUS, chord 3"; an inserted chord: "CHORUS, bar 14, beat 3 (and)") and whether the song still
     * has the chord it was suggested on.
     */
    public function execute(): array
    {
        $pending = SongSuggestion::with('song', 'setlist')->where('status', 'pending')->oldest()->get();
        $reviewed = SongSuggestion::with('song', 'setlist', 'reviewer')->where('status', '!=', 'pending')->latest('reviewed_at')->limit(20)->get();

        return [
            // Pending ones come with the chord sheet as it would be with their changes.
            'pending'  => $pending->map(fn ($suggestion) => self::describe($suggestion) + ['preview' => $this->apply->preview($suggestion)])->all(),
            'reviewed' => $reviewed->map(fn ($suggestion) => self::describe($suggestion))->all(),
        ];
    }

    private static function describe(SongSuggestion $suggestion): array
    {
        $structure = json_decode(json_encode($suggestion->song?->structure ?? []), true) ?? [];
        $places = ApplySongSuggestionService::places($structure);

        return [
            'suggestion' => $suggestion,
            'changes'    => array_map(function ($change) use ($places) {
                $place = $places[(int) $change['n']] ?? null;
                $insert = ($change['type'] ?? 'change') === 'insert';
                $offset = (int) ($change['offset'] ?? 0);
                $outdated = ! $place || NormalizeChordSpellingService::chord($place['chord']) !== NormalizeChordSpellingService::chord($change['from'])
                    || ($insert && ($place['ticks'] === null || $offset < 1 || $offset >= $place['ticks']));
                // Where an inserted chord goes: bar and beat of the song (half beats counted from the first block).
                $at = $insert && ! $outdated && $place['start'] !== null ? $place['start'] + $offset : null;
                $size = ($place['perBar'] ?? 4) * 2;

                return array_merge($change, [
                    'insert'   => $insert,
                    'block'    => str_replace('_', ' ', $place['section'] ?? '?'),
                    'position' => $place ? $place['position'] + 1 : null,
                    'bar'      => $at !== null ? intdiv($at, $size) + 1 : null,
                    'beat'     => $at !== null ? intdiv($at % $size, 2) + 1 : null,
                    'and'      => $at !== null && $at % 2 === 1,
                    'current'  => $place['chord'] ?? null,
                    'outdated' => $outdated,
                ]);
            }, array_values($suggestion->changes ?? [])),
        ];
    }
}
