<?php

namespace App\Services;

use App\Models\SongSuggestion;

class ApplySongSuggestionService
{
    /**
     * Applies the accepted changes of a suggestion (`$accepted`: the chord numbers n to apply) to the song's map and,
     * when it has the map's chords, to its chord sheet: a chord renamed, or removed (its beats go to the chord
     * before it in the block, or the one after when it is the first; a block keeps at least one chord). A change
     * whose chord is no longer the one suggested on (the song changed since) is not applied: "outdated". Each change
     * gets its result; the suggestion is approved when any was applied, otherwise rejected.
     */
    public function execute(SongSuggestion $suggestion, array $accepted): SongSuggestion
    {
        $song = $suggestion->song;
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $sections = $song->chord_sheet['sections'] ?? [];
        $sheetFollows = $sections !== [] && UpdateChordSheetChordsService::sheetChords($sections) === UpdateSongLayoutService::chordSequence($structure);

        // Chord n -> [block, position in the block's chords without line breaks].
        $where = [];
        foreach ($structure as $b => $block) {
            if (! is_array($block)) {
                continue;
            }
            $position = 0;
            foreach ($block['chords'] ?? [] as $chord) {
                if ($chord !== UpdateSongLayoutService::LINE_BREAK) {
                    $where[] = [$b, $position++];
                }
            }
        }
        $current = UpdateSongLayoutService::chordSequence($structure);
        $same = fn (?string $a, ?string $b) => NormalizeChordSpellingService::chord((string) $a) === NormalizeChordSpellingService::chord((string) $b);

        $changes = [];
        $apply = [];
        foreach ($suggestion->changes as $change) {
            $n = (int) $change['n'];
            $result = in_array($n, $accepted, true) ? 'applied' : 'rejected';
            if ($result === 'applied' && (! isset($current[$n]) || ! $same($current[$n], $change['from']))) {
                $result = 'outdated';
            }
            if ($result === 'applied') {
                $apply[$n] = $change['to'] === null ? null : NormalizeChordSpellingService::chord($change['to']);
            }
            $changes[] = array_merge($change, ['result' => $result]);
        }

        // Removals from the last chord back, so earlier numbers stay valid; a block keeps at least one chord.
        krsort($apply);
        $names = $current;
        $removed = [];
        foreach ($apply as $n => $to) {
            [$b, $position] = $where[$n];
            if ($to !== null) {
                $names[$n] = $to;
                $structure[$b]['chords'] = self::replaceAt($structure[$b]['chords'], $position, $to);
                continue;
            }
            $count = count(UpdateSongLayoutService::chordSequence([$structure[$b]]));
            if ($count <= 1) {
                $changes = array_map(fn ($change) => (int) $change['n'] === $n ? array_merge($change, ['result' => 'outdated']) : $change, $changes);
                continue;
            }
            $removed[] = $n;
            $structure[$b] = self::removeAt($structure[$b], $position, $count);
        }

        if ($sheetFollows) {
            $chord = 0;
            $sections = array_map(function ($section) use (&$chord, $names, $removed) {
                $lines = [];
                foreach ($section['lines'] ?? [] as $line) {
                    $text = (string) $line;
                    if (str_starts_with(ltrim($text), '{')) {
                        $lines[] = $line;
                        continue;
                    }
                    $hadChords = (bool) preg_match('/\[[^\]]+\]/u', $text);
                    $text = preg_replace_callback('/\[([^\]]+)\]/u', function () use (&$chord, $names, $removed) {
                        $n = $chord++;

                        return in_array($n, $removed, true) ? '' : '[' . $names[$n] . ']';
                    }, $text);
                    // A line of chords only that lost all of them goes away; one that lost some is tidied.
                    if ($hadChords && trim(preg_replace('/\[[^\]]+\]/u', '', $text)) === '') {
                        if (! preg_match('/\[[^\]]+\]/u', $text)) {
                            continue;
                        }
                        $text = trim(preg_replace('/\s+/u', ' ', $text));
                    }
                    $lines[] = $text;
                }

                return array_merge($section, ['lines' => $lines]);
            }, $sections);
        }

        $applied = collect($changes)->where('result', 'applied')->isNotEmpty();
        if ($applied) {
            $song->structure = $structure;
            if ($sheetFollows) {
                $song->chord_sheet = array_merge($song->chord_sheet ?? [], ['sections' => $sections]);
            }
            $song->save();
        }

        $suggestion->update([
            'changes'     => $changes,
            'status'      => $applied ? 'approved' : 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return $suggestion;
    }

    private static function replaceAt(array $chords, int $position, string $to): array
    {
        $index = -1;
        foreach ($chords as $i => $chord) {
            if ($chord !== UpdateSongLayoutService::LINE_BREAK && ++$index === $position) {
                $chords[$i] = $to;
                break;
            }
        }

        return $chords;
    }

    /**
     * The block without its chord at `$position`: its beats go to the chord before (or after, for the first one)
     * and the passing chords after it move back one.
     */
    private static function removeAt(array $block, int $position, int $count): array
    {
        $index = -1;
        foreach ($block['chords'] as $i => $chord) {
            if ($chord !== UpdateSongLayoutService::LINE_BREAK && ++$index === $position) {
                array_splice($block['chords'], $i, 1);
                break;
            }
        }
        $block['chords'] = UpdateSongLayoutService::cleanBreaks($block['chords']);

        if (is_array($block['durations'] ?? null) && count($block['durations']) === $count) {
            $beats = $block['durations'][$position];
            array_splice($block['durations'], $position, 1);
            $block['durations'][$position > 0 ? $position - 1 : 0] += $beats;
        }
        if (is_array($block['passing'] ?? null)) {
            $block['passing'] = array_values(array_map(
                fn ($p) => $p > $position ? $p - 1 : $p,
                array_filter($block['passing'], fn ($p) => $p !== $position),
            ));
        }

        return $block;
    }
}
