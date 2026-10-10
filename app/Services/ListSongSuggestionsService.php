<?php

namespace App\Services;

use App\Models\SongSuggestion;

class ListSongSuggestionsService
{
    /**
     * Suggestions to review (pending first, then the last reviewed ones), each change described by its block and its
     * place there ("CHORUS, chord 3") and whether the song still has the chord it was suggested on.
     */
    public function execute(): array
    {
        $pending = SongSuggestion::with('song', 'setlist')->where('status', 'pending')->oldest()->get();
        $reviewed = SongSuggestion::with('song', 'setlist', 'reviewer')->where('status', '!=', 'pending')->latest('reviewed_at')->limit(20)->get();

        return [
            'pending'  => $pending->map(fn ($suggestion) => self::describe($suggestion))->all(),
            'reviewed' => $reviewed->map(fn ($suggestion) => self::describe($suggestion))->all(),
        ];
    }

    private static function describe(SongSuggestion $suggestion): array
    {
        $structure = json_decode(json_encode($suggestion->song?->structure ?? []), true) ?? [];
        $places = [];
        foreach ($structure as $block) {
            if (! is_array($block)) {
                continue;
            }
            $position = 0;
            foreach ($block['chords'] ?? [] as $chord) {
                if ($chord !== UpdateSongLayoutService::LINE_BREAK) {
                    $places[] = ['block' => str_replace('_', ' ', (string) ($block['section'] ?? '')), 'position' => ++$position, 'chord' => $chord];
                }
            }
        }

        return [
            'suggestion' => $suggestion,
            'changes'    => array_map(function ($change) use ($places) {
                $place = $places[(int) $change['n']] ?? null;

                return array_merge($change, [
                    'block'    => $place['block'] ?? '?',
                    'position' => $place['position'] ?? null,
                    'current'  => $place['chord'] ?? null,
                    'outdated' => ! $place || NormalizeChordSpellingService::chord($place['chord']) !== NormalizeChordSpellingService::chord($change['from']),
                ]);
            }, $suggestion->changes ?? []),
        ];
    }
}
