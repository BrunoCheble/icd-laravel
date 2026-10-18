<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\SongSuggestion;

class ApplySongSuggestionService
{
    public function __construct(private UpdateChordSheetChordsService $sheetChords)
    {
    }

    /**
     * Applies the accepted changes of a suggestion (`$accepted`: their indexes in the suggestion) to the song's map
     * and, when it has the map's chords, to its chord sheet, so both keep the same chords:
     * - a chord renamed;
     * - a chord removed: its beats go to the chord before it in the block (or the one after, when it is the first);
     *   a block keeps at least one chord;
     * - a chord inserted on a beat (or its "and") where none started: it goes on until the next chord or the end of
     *   that bar, the chord it was put in getting shorter (and going on after it when it was longer than the bar).
     *   In the chord sheet it is written right after the chord it was put in.
     * A change whose chord is no longer the one suggested on (the song changed since) is not applied: "outdated".
     * Each change gets its result; the suggestion is approved when any was applied, otherwise rejected.
     */
    public function execute(SongSuggestion $suggestion, array $accepted): SongSuggestion
    {
        $song = $suggestion->song;
        ['changes' => $changes, 'structure' => $structure, 'replacements' => $replacements, 'sheetFollows' => $sheetFollows,
            'sections' => $sections, 'oldChords' => $oldChords] = $this->plan($suggestion, $accepted);

        $applied = collect($changes)->where('result', 'applied')->isNotEmpty();
        if ($applied) {
            $song->structure = $structure;
            if ($sheetFollows) {
                $newChords = UpdateSongLayoutService::chordSequence($structure);
                $rewritten = self::rewriteSheet($sections, $replacements);
                // The sheet must have exactly the map's chords; otherwise the same chord changes as when the map is saved.
                if (UpdateChordSheetChordsService::sheetChords($rewritten) !== $newChords) {
                    $rewritten = $this->sheetChords->execute($sections, $oldChords, $newChords) ?? $sections;
                }
                $song->chord_sheet = array_merge($song->chord_sheet ?? [], ['sections' => $rewritten]);
            }
            $song->save();
        }

        $suggestion->update([
            'changes'     => array_values($changes),
            'status'      => $applied ? 'approved' : 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return $suggestion;
    }

    /**
     * The chord sheet as it would be with every change of the suggestion that still applies, for the review: each
     * line as segments of a chord (or none) and the lyrics after it; each chord "same", "changed" (with its old name
     * in "from"), "inserted" or "removed" (shown, to be struck through). Null when the sheet does not have the map's
     * chords (the changes would then only reach the map).
     */
    public function preview(SongSuggestion $suggestion): ?array
    {
        ['replacements' => $replacements, 'sheetFollows' => $sheetFollows, 'sections' => $sections] = $this->plan($suggestion, null);
        if (! $sheetFollows) {
            return null;
        }
        $chord = 0;

        return array_map(function ($section) use (&$chord, $replacements) {
            $lines = [];
            foreach ($section['lines'] ?? [] as $line) {
                $text = (string) $line;
                if (str_starts_with(ltrim($text), '{')) {
                    $lines[] = ['comment' => trim(preg_replace('/^\{\s*(?:c|comment)\s*:\s*|\s*\}$/i', '', trim($text)))];
                    continue;
                }
                $segments = [];
                foreach (preg_split('/(\[[^\]]+\])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
                    if (preg_match('/^\[([^\]]+)\]$/u', $part, $match)) {
                        $n = $chord++;
                        foreach ($replacements[$n] ?? [['name' => $match[1], 'kind' => 'same']] as $item) {
                            $segments[] = ['chord' => $item['name'], 'kind' => $item['kind'], 'from' => $item['from'] ?? null, 'text' => ''];
                        }
                    } elseif ($segments === []) {
                        $segments[] = ['chord' => null, 'kind' => 'same', 'from' => null, 'text' => $part];
                    } else {
                        $segments[count($segments) - 1]['text'] .= $part;
                    }
                }
                $lines[] = ['segments' => $segments];
            }

            return ['label' => $section['label'] ?? str_replace('_', ' ', (string) ($section['section'] ?? '')), 'lines' => $lines];
        }, $sections);
    }

    /**
     * What a suggestion does when the changes accepted (their indexes; null: all of them) are applied: each change with
     * its result, the new map, and for each chord n of the old map the chords that take its place.
     */
    private function plan(SongSuggestion $suggestion, ?array $accepted): array
    {
        $song = $suggestion->song;
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $sections = $song->chord_sheet['sections'] ?? [];
        $oldChords = UpdateSongLayoutService::chordSequence($structure);
        $sheetFollows = $sections !== [] && UpdateChordSheetChordsService::sheetChords($sections) === $oldChords;
        $places = self::places($structure);
        $same = fn (?string $a, ?string $b) => NormalizeChordSpellingService::chord((string) $a) === NormalizeChordSpellingService::chord((string) $b);

        $changes = [];
        $renames = [];
        $removals = [];
        $inserts = [];
        foreach (array_values($suggestion->changes ?? []) as $index => $change) {
            $n = (int) $change['n'];
            $insert = ($change['type'] ?? 'change') === 'insert';
            $place = $places[$n] ?? null;
            $result = $accepted === null || in_array($index, $accepted, true) ? 'applied' : 'rejected';
            if ($result === 'applied' && (! $place || ! $same($place['chord'], $change['from']))) {
                $result = 'outdated';
            }
            $offset = (int) ($change['offset'] ?? 0);
            if ($result === 'applied' && $insert && ($place['ticks'] === null || $offset < 1 || $offset >= $place['ticks'])) {
                $result = 'outdated';
            }
            if ($result === 'applied') {
                $to = $change['to'] === null ? null : NormalizeChordSpellingService::chord($change['to']);
                if ($insert) {
                    $inserts[$n][] = ['offset' => $offset, 'to' => $to, 'index' => $index];
                } elseif ($to === null) {
                    $removals[$n] = $index;
                } else {
                    $renames[$n] = $to;
                }
            }
            $changes[$index] = array_merge($change, ['result' => $result]);
        }

        // A chord removed cannot take an inserted one; a block keeps at least one chord.
        foreach ($inserts as $n => $list) {
            if (isset($removals[$n])) {
                foreach ($list as $item) {
                    $changes[$item['index']]['result'] = 'outdated';
                }
                unset($inserts[$n]);
            }
        }
        foreach ($places as $n => $place) {
            $block = $place['block'];
            $kept = collect($places)->filter(fn ($other, $m) => $other['block'] === $block && ! isset($removals[$m]))->count();
            if (isset($removals[$n]) && $kept === 0) {
                $changes[$removals[$n]]['result'] = 'outdated';
                unset($removals[$n]);
            }
        }

        // Each block with a change rebuilt beat by beat; each chord of the old map gets the chords that take its place.
        $replacements = [];
        $barStart = 0;
        foreach ($structure as $b => $block) {
            if (! is_array($block) || ($block['jump'] ?? null) === 'start') {
                continue;
            }
            $numbers = array_keys(array_filter($places, fn ($place) => $place['block'] === $b));
            $length = array_sum(array_map(fn ($n) => $places[$n]['ticks'] ?? 1, $numbers));
            $touched = array_filter($numbers, fn ($n) => isset($renames[$n]) || isset($removals[$n]) || isset($inserts[$n]));
            if ($touched !== []) {
                [$structure[$b], $blockReplacements] = self::rebuild($block, $numbers, $places, $renames, $removals, $inserts, $barStart);
                $replacements += $blockReplacements;
            }
            $barStart += $length;
        }

        return compact('changes', 'structure', 'replacements', 'sheetFollows', 'sections', 'oldChords');
    }

    /**
     * Each chord n of the map: its block, its position there (line breaks not counted), its name, its length in half
     * beats (null without durations) and where it starts counted from the first block (null when not known).
     */
    public static function places(array $structure): array
    {
        $places = [];
        $tick = 0;
        foreach ($structure as $b => $block) {
            if (! is_array($block) || ($block['jump'] ?? null) === 'start') {
                continue;
            }
            $chords = UpdateSongLayoutService::chordSequence([$block]);
            $durations = is_array($block['durations'] ?? null) && count($block['durations']) === count($chords) ? $block['durations'] : null;
            foreach ($chords as $position => $chord) {
                $ticks = $durations !== null ? (int) round((float) $durations[$position] * 2) : null;
                $places[] = [
                    'block'    => $b,
                    'section'  => (string) ($block['section'] ?? ''),
                    'position' => $position,
                    'chord'    => $chord,
                    'ticks'    => $ticks,
                    'start'    => $tick,
                    'perBar'   => (int) ($block['beats_per_bar'] ?? 4) ?: 4,
                ];
                $tick = $ticks !== null && $tick !== null ? $tick + $ticks : null;
            }
        }

        return $places;
    }

    /**
     * A block with its changes, half beat by half beat: [the block, for each of its old chords n the chords that take
     * its place, in order: ['name', 'kind' (same, changed, inserted; removed: the chord itself, gone), 'from']].
     */
    private static function rebuild(array $block, array $numbers, array $places, array $renames, array $removals, array $inserts, ?int $barStart): array
    {
        $count = count($numbers);
        $hasDurations = $places[$numbers[0]]['ticks'] !== null;
        $perBar = $places[$numbers[0]]['perBar'];

        // Who sounds on each half beat: "e<position>" (a chord of the block) or "i<k>" (an inserted one).
        $ids = [];
        $startOf = [];
        $names = [];
        foreach ($numbers as $position => $n) {
            $startOf[$position] = count($ids);
            $names["e{$position}"] = $renames[$n] ?? $places[$n]['chord'];
            array_push($ids, ...array_fill(0, $hasDurations ? max(1, $places[$n]['ticks']) : 1, "e{$position}"));
        }
        $hostOf = [];
        $k = 0;
        foreach ($numbers as $position => $n) {
            $list = $inserts[$n] ?? [];
            usort($list, fn ($a, $b) => $a['offset'] <=> $b['offset']);
            foreach ($list as $item) {
                $id = 'i' . $k++;
                $names[$id] = $item['to'];
                $hostOf[$id] = $position;
                $at = $startOf[$position] + $item['offset'];
                $global = $barStart + $at;
                $barEnd = (intdiv($global, $perBar * 2) + 1) * $perBar * 2 - $barStart;
                // Over what sounds there (the chord it was put in, or a chord inserted before it), as when typed.
                $under = $ids[$at] ?? null;
                for ($t = $at; $t < min($barEnd, count($ids)) && ($t === $at || $ids[$t] === $under); $t++) {
                    $ids[$t] = $id;
                }
            }
        }
        foreach ($numbers as $position => $n) {
            if (! isset($removals[$n])) {
                continue;
            }
            $own = array_keys($ids, "e{$position}", true);
            if ($own === []) {
                continue;
            }
            $heir = $own[0] > 0 ? $ids[$own[0] - 1] : ($ids[end($own) + 1] ?? null);
            foreach ($own as $t) {
                $ids[$t] = $heir;
            }
        }

        // Half beats back to chords: a new chord where who sounds changes.
        $out = [];
        foreach ($ids as $t => $id) {
            if ($out !== [] && $out[count($out) - 1]['id'] === $id) {
                $out[count($out) - 1]['ticks']++;
            } else {
                $out[] = ['id' => $id, 'ticks' => 1, 'start' => $t];
            }
        }
        $chords = array_map(fn ($item) => $names[$item['id']], $out);

        // Line breaks and passing chords stay with the chords they were on (a removed chord's break: the next chord).
        $firstAtOrAfter = function (int $tick) use ($out) {
            foreach ($out as $index => $item) {
                if ($item['start'] >= $tick) {
                    return $index;
                }
            }

            return null;
        };
        $breaks = [];
        $position = 0;
        foreach ($block['chords'] ?? [] as $chord) {
            if ($chord !== UpdateSongLayoutService::LINE_BREAK) {
                $position++;
            } elseif ($position > 0 && $position < $count) {
                $index = $firstAtOrAfter($startOf[$position]);
                if ($index !== null && $index > 0) {
                    $breaks[$index] = true;
                }
            }
        }
        $list = [];
        foreach ($chords as $index => $chord) {
            if (isset($breaks[$index])) {
                $list[] = UpdateSongLayoutService::LINE_BREAK;
            }
            $list[] = $chord;
        }
        $block['chords'] = UpdateSongLayoutService::cleanBreaks($list);
        if ($hasDurations) {
            $block['durations'] = array_map(fn ($item) => $item['ticks'] / 2, $out);
        }
        if (is_array($block['passing'] ?? null)) {
            $passing = [];
            foreach ($block['passing'] as $p) {
                $index = array_search("e{$p}", array_column($out, 'id'), true);
                if ($index !== false && $out[$index]['start'] === ($startOf[$p] ?? -1)) {
                    $passing[] = $index;
                }
            }
            $block['passing'] = $passing;
        }

        $replacements = [];
        foreach ($numbers as $position => $n) {
            $original = $places[$n]['chord'];
            if (isset($removals[$n])) {
                $replacements[$n] = [['name' => $original, 'kind' => 'removed', 'from' => null]];
                continue;
            }
            $renamed = isset($renames[$n]) && NormalizeChordSpellingService::chord($renames[$n]) !== NormalizeChordSpellingService::chord($original);
            $own = false;
            $replacements[$n] = [];
            foreach ($out as $item) {
                if ($item['id'] === "e{$position}" && ! $own) {
                    $own = true;
                    $replacements[$n][] = ['name' => $names[$item['id']], 'kind' => $renamed ? 'changed' : 'same', 'from' => $renamed ? $original : null];
                } elseif ($item['id'] === "e{$position}" || ($hostOf[$item['id']] ?? null) === $position) {
                    // An inserted chord, or the chord going on again after one inserted in it.
                    $replacements[$n][] = ['name' => $names[$item['id']], 'kind' => 'inserted', 'from' => null];
                }
            }
        }

        return [$block, $replacements];
    }

    /**
     * The chord sheet with each old chord n written as the chords that take its place (none: removed). A line of
     * chords only keeps them one space apart, and goes away when it lost all of them.
     */
    private static function rewriteSheet(array $sections, array $replacements): array
    {
        $chord = 0;

        return array_map(function ($section) use (&$chord, $replacements) {
            $lines = [];
            foreach ($section['lines'] ?? [] as $line) {
                $text = (string) $line;
                if (str_starts_with(ltrim($text), '{')) {
                    $lines[] = $line;
                    continue;
                }
                $hadChords = (bool) preg_match('/\[[^\]]+\]/u', $text);
                $text = preg_replace_callback('/\[([^\]]+)\]/u', function (array $match) use (&$chord, $replacements) {
                    $n = $chord++;
                    $names = isset($replacements[$n])
                        ? array_column(array_filter($replacements[$n], fn ($item) => $item['kind'] !== 'removed'), 'name')
                        : [$match[1]];

                    return implode('', array_map(fn ($name) => "[{$name}]", $names));
                }, $text);
                if ($hadChords && trim(preg_replace('/\[[^\]]+\]/u', '', $text)) === '') {
                    if (! preg_match_all('/\[[^\]]+\]/u', $text, $found)) {
                        continue;
                    }
                    $text = implode(' ', $found[0]);
                }
                $lines[] = $text;
            }

            return array_merge($section, ['lines' => $lines]);
        }, $sections);
    }
}
