<?php

namespace App\Services;

use App\Models\SongVersion;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SaveSongVersionService
{
    /**
     * Saves the chords of an instrument's version (edited as bars on the layout page). Only the chords change: the
     * blocks, their times and lengths stay as in the version. `$key`: the key the chords come in (the song's), which
     * the version is stored in from now on. Refused (409) when it changed since the page read it.
     */
    public function execute(SongVersion $version, array $blocks, string $readAt, ?string $key = null): SongVersion
    {
        if (! $version->updated_at || ! $version->updated_at->equalTo(Carbon::parse($readAt))) {
            throw new ConflictHttpException(__('The song was changed meanwhile. Load it again before saving.'));
        }

        $old = array_map([self::class, 'shape'], array_values($version->structure ?? []));
        $new = array_map([self::class, 'shape'], array_values($blocks));
        // A block without a time gets one computed by the page: only times that were there must stay.
        foreach ($old as $index => $shape) {
            if ($shape['start'] === null && isset($new[$index])) {
                $new[$index]['start'] = null;
            }
        }
        if ($old != $new) {
            throw ValidationException::withMessages(['blocks' => __('A version changes only the chords: the blocks and their times stay as they are.')]);
        }

        $version->structure = array_map(function (array $block, int $index) {
            $block['order'] = $index + 1;
            $block['chords'] = array_map(
                fn ($chord) => $chord === UpdateSongLayoutService::LINE_BREAK ? $chord : NormalizeChordSpellingService::chord(trim($chord)),
                $block['chords'] ?? [],
            );

            return $block;
        }, array_values($blocks), array_keys(array_values($blocks)));
        if ($key !== null && $key !== '') {
            $version->musical_key = $key;
        }
        $version->save();

        return $version;
    }

    /**
     * What a version cannot change in a block: its name, kind, time and length in beats.
     */
    private static function shape(mixed $block): array
    {
        $block = is_array($block) ? $block : (array) $block;
        $start = UpdateSongLayoutService::toSeconds($block['start'] ?? null);

        return [
            'section' => mb_strtoupper(trim((string) ($block['section'] ?? ''))),
            'jump'    => $block['jump'] ?? null,
            'start'   => $start === null ? null : round($start, 1),
            'beats'   => array_sum(array_map('floatval', $block['durations'] ?? [])),
        ];
    }
}
