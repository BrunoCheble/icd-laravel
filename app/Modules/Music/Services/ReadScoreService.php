<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Song;
use App\Modules\Music\Support\MusicXmlScore;
use Illuminate\Support\Str;

/**
 * A score (MusicXML) as the song's grid of bars, to preview before importing: the bars in the order they are played
 * (repeats and 1st / 2nd endings expanded), each chord's beats, and the bars split in blocks by what the score says
 * (repeats, double bars, rehearsal marks), by where a known lyric starts and by bars without lyrics. A phrase sung
 * from the second half of a bar after a rest (an anacrusis: "Teu nome é", "Je-") belongs to the next bar. Blocks are
 * named after the song's own blocks with the same lyrics (keeping their lyrics), else after the score's section
 * words, else by their place (INTRO, INTERLUDE, FINAL) or as verses (VERSE_1, VERSE_2…: the same lyrics, the same
 * name), in the codes the chord sheets use.
 */
class ReadScoreService
{
    // Chords fall on the grid's half beats.
    private const STEP = 0.5;
    // Bars per line of chords in a block.
    private const BARS_PER_LINE = 4;

    private MusicXmlScore $score;
    /** Bar indexes in the order they are played. */
    private array $order = [];
    /** The song's blocks with lyrics: [section, lyrics, anchor, trigrams]. */
    private array $known = [];

    public function execute(MusicXmlScore $score, Song $song): array
    {
        $this->score = $score;
        $this->known = $this->knownBlocks($song);
        $this->anacruses();
        $this->order = $this->playbackOrder();

        $segments = $this->segments();
        $segments = $this->tidy($this->splitByLyrics($segments, $this->openings($segments)));
        $blocks = [];
        $last = null;
        foreach ($segments as $k => $segment) {
            $block = $this->block($segment, $k, count($segments), $last);
            if ($blocks && end($blocks)['section'] === $block['section']) {
                $blocks[array_key_last($blocks)] = $this->join(end($blocks), $block);
            } else {
                $blocks[] = $block;
            }
        }
        $this->nameParts($blocks);
        foreach ($blocks as &$block) {
            $block['sheet'] = $this->sheet($block);
        }
        unset($block);

        return [
            'title' => $score->title,
            'bpm' => $score->tempo ? round($score->tempo) : null,
            'meter' => $score->beatsPerBar . '/' . $score->beatType,
            'beats_per_bar' => $score->beatsPerBar,
            'bars' => count($this->order),
            'blocks' => array_map(fn ($block) => array_diff_key($block, ['grams' => 1, 'timeline' => 1]), $blocks),
        ];
    }

    /** The lyrics a bar sings, in playing order: what the bar before carried over, then its own. */
    private function sung(int $position): array
    {
        $carried = $position > 0 ? $this->score->bars[$this->order[$position - 1]]['carry'] : [];

        return array_merge($carried, $this->score->bars[$this->order[$position]]['own']);
    }

    private function anacruses(): void
    {
        foreach ($this->score->bars as &$bar) {
            $syllables = $bar['syllables'];
            $cut = count($syllables);
            $lastRest = $bar['rests'] ? max($bar['rests']) : null;
            $after = array_values(array_filter(array_keys($syllables), fn ($i) => $lastRest === null || $syllables[$i]['beat'] > $lastRest));
            // After the bar's last rest, starting in its second half: sung with the next bar.
            if ($after && $syllables[$after[0]]['beat'] >= $bar['beats'] / 2 && ($lastRest !== null || count($after) === count($syllables))) {
                $cut = $after[0];
            }
            $own = array_slice($syllables, 0, $cut);
            $carry = array_slice($syllables, $cut);
            // A word left unfinished goes on in the next bar.
            while ($own && in_array(end($own)['syllabic'], ['begin', 'middle'], true)) {
                array_unshift($carry, array_pop($own));
            }
            $bar['own'] = $own;
            $bar['carry'] = $carry;
        }
    }

    /** Repeats (‖: :‖) played twice, the 1st ending only the first time. */
    private function playbackOrder(): array
    {
        $bars = $this->score->bars;
        $order = [];
        $start = 0;
        $repeated = [];
        for ($i = 0, $guard = 0; $i < count($bars) && $guard < 2000; $guard++) {
            $marks = $bars[$i]['marks'];
            if (in_array('repeat-forward', $marks, true)) {
                $start = $i;
            }
            $pass = isset($repeated[$start]) ? 2 : 1;
            $ending = collect($marks)->first(fn ($mark) => str_starts_with($mark, 'ending:') && str_ends_with($mark, ':start'));
            if ($ending && ! in_array((string) $pass, array_map('trim', explode(',', explode(':', $ending)[1])), true)) {
                // Skip this ending: to the bar after the one that closes it.
                while ($i < count($bars) && ! collect($bars[$i]['marks'])->contains(fn ($mark) => str_starts_with($mark, 'ending:') && (str_ends_with($mark, ':stop') || str_ends_with($mark, ':discontinue')))) {
                    $i++;
                }
                $i++;
                continue;
            }
            $order[] = $i;
            if (in_array('repeat-backward', $marks, true) && ! isset($repeated[$start])) {
                $repeated[$start] = true;
                $i = $start;
                continue;
            }
            $i++;
        }

        return $order;
    }

    /** Positions (in playing order) split where the score marks a new part. */
    private function segments(): array
    {
        $bars = $this->score->bars;
        $bounds = [0 => true];
        foreach ($this->order as $p => $index) {
            $marks = $bars[$index]['marks'];
            if (array_intersect(['repeat-forward', 'rehearsal', 'left-double'], $marks) || $bars[$index]['words']) {
                $bounds[$p] = true;
            }
            if ($p > 0 && (in_array('right-double', $bars[$this->order[$p - 1]]['marks'], true) || $this->order[$p - 1] >= $index)) {
                $bounds[$p] = true;
            }
            if (collect($marks)->contains(fn ($mark) => preg_match('/^ending:2.*:start$/', $mark))) {
                unset($bounds[$p]);
            }
        }
        $starts = array_keys($bounds);
        sort($starts);
        $segments = [];
        foreach ($starts as $k => $from) {
            $to = ($starts[$k + 1] ?? count($this->order)) - 1;
            $segments[] = range($from, $to);
        }

        return $segments;
    }

    /** How the score's own parts start (their first letters), to find them again where the score does not mark them. */
    private function openings(array $segments): array
    {
        $openings = [];
        foreach ($segments as $segment) {
            $text = self::letters(self::words(collect($segment)->flatMap(fn ($p) => $this->sung($p))->all()));
            if (strlen($text) >= 16) {
                $openings[] = substr($text, 0, 16);
            }
        }

        return $openings;
    }

    /** Also split where a known block's first line (or a part's opening) starts, and around 3+ bars without lyrics. */
    private function splitByLyrics(array $segments, array $openings = []): array
    {
        $anchors = array_values(array_unique(array_filter(array_merge(
            array_map(fn ($block) => Str::substr(self::letters($block['anchor']), 0, 16), $this->known),
            $openings,
        ))));
        $out = [];
        foreach ($segments as $segment) {
            $parts = [[]];
            foreach ($segment as $i => $p) {
                // This bar's lyrics and the next ones' (an opening runs over a few bars).
                $text = self::words($this->sung($p)) === '' ? '' : self::letters(self::words(collect(array_slice($segment, $i, 4))->flatMap(fn ($q) => $this->sung($q))->all()));
                $current = end($parts);
                $startsKnown = $text !== '' && collect($anchors)->contains(fn ($anchor) => str_starts_with($text, $anchor));
                if ($current && $startsKnown && collect($current)->contains(fn ($q) => self::words($this->sung($q)) !== '')) {
                    $parts[] = [];
                }
                $parts[array_key_last($parts)][] = $p;
            }
            foreach ($parts as $part) {
                $head = [];
                $quiet = [];
                foreach ($part as $p) {
                    if (self::words($this->sung($p)) === '') {
                        $quiet[] = $p;
                        continue;
                    }
                    if (count($quiet) >= 3 && $head) {
                        $out[] = $head;
                        $out[] = $quiet;
                        $head = [];
                    } else {
                        $head = array_merge($head, $quiet);
                    }
                    $quiet = [];
                    $head[] = $p;
                }
                if (count($quiet) >= 3 && $head) {
                    $out[] = $head;
                    $out[] = $quiet;
                } elseif ($head || $quiet) {
                    $out[] = array_merge($head, $quiet);
                }
            }
        }

        return $out;
    }

    /** A bar or two with no lyrics of their own joins the block before (a pickup, a turnaround). */
    private function tidy(array $segments): array
    {
        $out = [];
        foreach ($segments as $segment) {
            $own = collect($segment)->flatMap(fn ($p) => $this->score->bars[$this->order[$p]]['own'])->all();
            if ($out && count($segment) <= 2 && self::words($own) === '') {
                $out[array_key_last($out)] = array_merge(end($out), $segment);
            } else {
                $out[] = $segment;
            }
        }

        return $out;
    }

    private function block(array $segment, int $k, int $count, ?string &$last): array
    {
        $bars = $this->score->bars;
        $text = self::words(collect($segment)->flatMap(fn ($p) => $this->sung($p))->all());
        [$section, $known] = $this->name($segment, $text, $k, $count);

        $chords = [];
        $durations = [];
        $beatsPerBar = $this->score->beatsPerBar;
        // When each syllable is sung and where the rests are, in beats from the block's start (an anacrusis carried
        // from the bar before comes just before its bar).
        $syllables = [];
        $rests = [];
        foreach ($segment as $j => $p) {
            $index = $this->order[$p];
            if ($p > 0) {
                foreach ($bars[$this->order[$p - 1]]['carry'] as $syllable) {
                    $syllables[] = ['at' => ($j - 1) * $beatsPerBar + $syllable['beat'], 'text' => $syllable['text'], 'syllabic' => $syllable['syllabic']];
                }
            }
            foreach ($bars[$index]['own'] as $syllable) {
                $syllables[] = ['at' => $j * $beatsPerBar + $syllable['beat'], 'text' => $syllable['text'], 'syllabic' => $syllable['syllabic']];
            }
            foreach ($bars[$index]['rests'] as $rest) {
                $rests[] = $j * $beatsPerBar + $rest;
            }
        }
        $line = 0;
        foreach ($segment as $j => $p) {
            $bar = $bars[$this->order[$p]];
            $marks = array_map(fn ($chord) => [round($chord[0] / self::STEP) * self::STEP, $chord[1]], $bar['chords']);
            if (! $marks || $marks[0][0] > 0) {
                // The chord before goes on (a "?" when there is none yet).
                array_unshift($marks, [0, $last ?? '?']);
            }
            foreach ($marks as $c => [$beat, $name]) {
                $end = $marks[$c + 1][0] ?? $bar['beats'];
                if ($end - $beat > 0) {
                    // The same chord going on (also over the bar line) is one longer chord, as on the grid.
                    $previous = collect($chords)->last(fn ($chord) => $chord !== '|');
                    if ($previous === $name) {
                        $durations[array_key_last($durations)] += $end - $beat;
                    } else {
                        // A new line of chords every BARS_PER_LINE bars.
                        if ($chords && intdiv($j, self::BARS_PER_LINE) > $line) {
                            $chords[] = '|';
                            $line = intdiv($j, self::BARS_PER_LINE);
                        }
                        $chords[] = $name;
                        $durations[] = $end - $beat;
                    }
                }
                $last = $name;
            }
        }
        // The written bars, as ranges in playing order ("39–45, 38–46").
        $ranges = [];
        foreach ($segment as $p) {
            $number = (int) $bars[$this->order[$p]]['number'];
            if ($ranges && $number === end($ranges)[1] + 1) {
                $ranges[array_key_last($ranges)][1] = $number;
            } else {
                $ranges[] = [$number, $number];
            }
        }

        return [
            'section' => $section,
            'known' => (bool) $known,
            'bars' => count($segment),
            'measures' => implode(', ', array_map(fn ($range) => $range[0] === $range[1] ? (string) $range[0] : $range[0] . '–' . $range[1], $ranges)),
            'chords' => $chords,
            'durations' => array_map(fn ($beats) => $beats == (int) $beats ? (int) $beats : $beats, $durations),
            'beats_per_bar' => $this->score->beatsPerBar,
            'lyrics' => $known['lyrics'] ?? ($text !== '' ? $text : null),
            'anchor' => $known['anchor'] ?? ($text !== '' ? Str::words($text, 6, '') : null),
            'grams' => self::grams($text),
            'timeline' => ['syllables' => $syllables, 'rests' => $rests],
        ];
    }

    /** [name, the song's block it is] for a block. */
    private function name(array $segment, string $text, int $k, int $count): array
    {
        if ($text !== '' && $this->known) {
            $grams = self::grams($text);
            $best = collect($this->known)->map(fn ($block) => [self::similarity($grams, $block['grams']), $block])->sortByDesc(0)->first();
            if ($best && $best[0] >= 0.3) {
                return [$best[1]['section'], $best[1]];
            }
        }
        $words = collect($segment)->flatMap(fn ($p) => $this->score->bars[$this->order[$p]]['words'])->implode(' ');
        // A verse marked as such is named with the others (VERSE_1, VERSE_2…).
        if ($words !== '' && ($marked = self::sectionOf($words)) && $marked !== 'VERSE') {
            return [$marked, null];
        }
        if ($text === '') {
            return [$k === 0 ? 'INTRO' : ($k === $count - 1 ? 'FINAL' : 'INTERLUDE'), null];
        }

        return ['?', null];
    }

    /** Sung blocks still unnamed: verses, the same lyrics with the same name (VERSE_1, VERSE_2…). */
    private function nameParts(array &$blocks): void
    {
        $groups = [];
        foreach ($blocks as &$block) {
            if ($block['section'] !== '?') {
                continue;
            }
            foreach ($groups as [$grams, $name]) {
                if (self::similarity($grams, $block['grams']) >= 0.5) {
                    $block['section'] = $name;
                    continue 2;
                }
            }
            $block['section'] = 'VERSE_' . (count($groups) + 1);
            $groups[] = [$block['grams'], $block['section']];
        }
    }

    private function join(array $a, array $b): array
    {
        $offset = array_sum($a['durations']);
        foreach ($b['timeline']['syllables'] as $syllable) {
            $a['timeline']['syllables'][] = ['at' => $syllable['at'] + $offset] + $syllable;
        }
        foreach ($b['timeline']['rests'] as $rest) {
            $a['timeline']['rests'][] = $rest + $offset;
        }
        $chords = $b['chords'];
        $durations = $b['durations'];
        // The same chord going on over the join is one longer chord.
        if ($chords && end($a['chords']) === $chords[0]) {
            $a['durations'][array_key_last($a['durations'])] += array_shift($durations);
            array_shift($chords);
        }
        $a['chords'] = $chords ? array_merge($a['chords'], ['|'], $chords) : $a['chords'];
        $a['durations'] = array_merge($a['durations'], $durations);
        $a['bars'] += $b['bars'];
        $a['measures'] .= ', ' . $b['measures'];
        if (! $a['known']) {
            $a['lyrics'] = trim(($a['lyrics'] ?? '') . "\n" . ($b['lyrics'] ?? '')) ?: null;
        }

        return $a;
    }

    /**
     * The block's chord sheet lines ("Jes[E]us, quero d[E4]eclarar…"): the lyrics (the song's, else the score's),
     * each chord before the syllable the score sings when it starts. The song's lyrics are matched with the score's
     * syllables in order, letter by letter (spaces and the score's typing slips ignored). A chord that starts with no
     * syllable of its own goes at the end of the line before, or on a line of chords (intros, interludes).
     */
    private function sheet(array $block): array
    {
        // Each chord change and when it starts.
        $changes = [];
        $at = 0;
        $d = 0;
        foreach ($block['chords'] as $chord) {
            if ($chord === '|') {
                continue;
            }
            $changes[] = ['at' => $at, 'chord' => $chord];
            $at += $block['durations'][$d++];
        }
        $syllables = $block['timeline']['syllables'];
        if (! $syllables) {
            return $this->chordLines($changes, $block['beats_per_bar']);
        }

        $text = $block['known'] && $block['lyrics'] ? $block['lyrics'] : $this->scoreLines($block['timeline']);
        // A repeated part (CHORUS ×2) whose lyrics the song writes once: as many times as the score sings them.
        $scoreLetters = strlen(self::letters(implode(' ', array_column($syllables, 'text'))));
        $times = max(1, (int) round($scoreLetters / max(1, strlen(self::letters($text)))));
        if ($block['known'] && $times > 1) {
            $text = implode("\n", array_fill(0, min($times, 4), trim($text)));
        }

        $lines = $this->place($text, $syllables, $changes, $block['timeline'], true);
        // The grid refuses a chord sheet without exactly the map's chords: then the chords on lines of their own.
        if (UpdateChordSheetChordsService::sheetChords([['lines' => $lines]]) !== UpdateSongLayoutService::chordSequence([$block])) {
            return array_merge($this->chordLines($changes, $block['beats_per_bar']), explode("\n", trim(preg_replace('/\[[^\]]*\]/u', '', $text))));
        }

        return $lines;
    }

    /** The chords placed in the text (see sheet()); the score's lyrics sung after the text ends are added to it. */
    private function place(string $text, array $syllables, array $changes, array $timeline, bool $more): array
    {
        // The text's letters, each with its place in the text (in characters).
        $chars = mb_str_split($text);
        $letters = '';
        $places = [];
        foreach ($chars as $i => $char) {
            $plain = strtolower(Str::ascii($char));
            if (strlen($plain) === 1 && ctype_alpha($plain)) {
                $letters .= $plain;
                $places[] = $i;
            }
        }
        // Each syllable found in order, a little ahead of the last one at most.
        $found = [];
        $cursor = 0;
        foreach ($syllables as $k => $syllable) {
            $needle = self::letters($syllable['text']);
            if ($needle === '') {
                continue;
            }
            $hit = strpos(substr($letters, $cursor, 40), $needle);
            if ($hit !== false) {
                $found[$k] = $places[$cursor + $hit];
                $cursor += $hit + strlen($needle);
            }
        }

        // The score goes on singing after the text: its own lyrics are added for that part.
        $lastFound = $found ? max(array_keys($found)) : -1;
        $leftover = array_slice($syllables, $lastFound + 1);
        if ($more && strlen(self::letters(implode('', array_column($leftover, 'text')))) >= 12) {
            // Sung again: the lines of the text most like it (the song's lyrics, not the score's typing); else the
            // score's own lyrics.
            $extra = $this->scoreLines(['syllables' => $leftover, 'rests' => $timeline['rests']]);
            $extra = $this->again($text, $extra) ?? $extra;

            return $this->place(rtrim($text) . "\n" . $extra, $syllables, $changes, $timeline, false);
        }

        // Where each chord goes: before a syllable, at the end of the line of the chord before, or on a first line.
        $targets = [];
        $last = null;
        foreach ($changes as $c => $change) {
            $until = $changes[$c + 1]['at'] ?? PHP_INT_MAX;
            // The syllable sung when the chord starts; when the song's lyrics do not have it (the score sings other
            // words), the next one sung over the chord that they have: at the start of its line when that line has
            // no chord yet ("[C#m7]Quebrando os vícios" for the score's "que [C#m7]todos vícios").
            $place = null;
            $first = true;
            foreach ($syllables as $k => $syllable) {
                if ($syllable['at'] < $change['at'] - 0.1 || $syllable['at'] >= $until) {
                    continue;
                }
                if (isset($found[$k])) {
                    $place = $found[$k];
                    if (! $first) {
                        $lineStart = mb_strrpos(mb_substr($text, 0, $place), "\n");
                        $lineStart = $lineStart === false ? 0 : $lineStart + 1;
                        if ($last === null || $last < $lineStart) {
                            $place = $lineStart + (mb_strlen(mb_substr($text, $lineStart)) - mb_strlen(ltrim(mb_substr($text, $lineStart))));
                        }
                    }
                    break;
                }
                $first = false;
            }
            $targets[$c] = $place !== null ? ['before', $place] : ($last !== null ? ['after', $last] : ['lead', -1]);
            if ($place !== null) {
                $last = $place;
            }
        }
        // The sheet must have the map's chords in the map's order: a chord that would come out before the one before
        // it goes right after that one.
        $lineEnd = [];
        foreach ($chars as $i => $char) {
            $lineEnd[$i] = mb_strpos($text . "\n", "\n", $i);
        }
        $key = fn (array $target) => match ($target[0]) { 'lead' => -1, 'before' => $target[1], 'after' => $lineEnd[$target[1]] + 0.5 };
        foreach ($targets as $c => $target) {
            if ($c > 0 && $key($target) < $key($targets[$c - 1])) {
                $targets[$c] = $targets[$c - 1];
            }
        }
        $before = [];
        $after = [];
        $lead = [];
        foreach ($targets as $c => [$kind, $place]) {
            match ($kind) {
                'before' => $before[$place][] = $changes[$c]['chord'],
                'after' => $after[$place][] = $changes[$c]['chord'],
                'lead' => $lead[] = $changes[$c]['chord'],
            };
        }

        // The text with the chords before their syllables, then the trailing chords at the end of their lines.
        $out = '';
        $lineOf = [];
        $line = 0;
        foreach ($chars as $i => $char) {
            if (isset($before[$i])) {
                $out .= '[' . implode('][', $before[$i]) . ']';
            }
            $out .= $char;
            $lineOf[$i] = $line;
            if ($char === "\n") {
                $line++;
            }
        }
        $lines = array_map('rtrim', explode("\n", $out));
        // A few chords at the end of their line; more (an instrumental passage) on a line of their own after it.
        $extraLines = [];
        foreach ($after as $place => $chords) {
            $tokens = implode(' ', array_map(fn ($chord) => '[' . $chord . ']', $chords));
            if (count($chords) <= 3) {
                $lines[$lineOf[$place]] .= ' ' . $tokens;
            } else {
                $extraLines[$lineOf[$place]][] = $tokens;
            }
        }
        if ($extraLines) {
            $with = [];
            foreach ($lines as $i => $line) {
                $with[] = $line;
                foreach ($extraLines[$i] ?? [] as $extra) {
                    $with[] = $extra;
                }
            }
            $lines = $with;
        }
        if ($lead) {
            array_unshift($lines, implode(' ', array_map(fn ($chord) => '[' . $chord . ']', $lead)));
        }

        return array_values(array_filter($lines, fn ($line) => trim($line) !== ''));
    }

    /**
     * The lines of $text (one after another, from any line) most like $sung, when they are alike enough (a part sung
     * once more than the song's lyrics write it); null otherwise.
     */
    private function again(string $text, string $sung): ?string
    {
        $lines = array_values(array_filter(explode("\n", $text), fn ($line) => trim($line) !== ''));
        $want = strlen(self::letters($sung));
        $grams = self::grams($sung);
        $best = [0.0, null];
        foreach ($lines as $from => $line) {
            $take = [];
            $length = 0;
            for ($to = $from; $to < count($lines) && $length < $want * 1.1; $to++) {
                $take[] = $lines[$to];
                $length += strlen(self::letters($lines[$to]));
                $score = self::similarity($grams, self::grams(implode(' ', $take)));
                if ($score > $best[0]) {
                    $best = [$score, implode("\n", $take)];
                }
            }
        }

        return $best[0] >= 0.45 ? $best[1] : null;
    }

    /** Chords with no lyrics, BARS_PER_LINE bars a line: "[E] [E4] [E] [C#m7]". */
    private function chordLines(array $changes, int $beatsPerBar): array
    {
        $lines = [];
        foreach ($changes as $change) {
            $lines[(int) floor($change['at'] / ($beatsPerBar * self::BARS_PER_LINE))][] = '[' . $change['chord'] . ']';
        }

        return array_values(array_map(fn ($line) => implode(' ', $line), $lines));
    }

    /** The score's lyrics as lines: a new line after a rest. */
    private function scoreLines(array $timeline): string
    {
        $text = '';
        $previous = null;
        foreach ($timeline['syllables'] as $syllable) {
            if ($previous !== null && collect($timeline['rests'])->contains(fn ($rest) => $rest > $previous['at'] && $rest < $syllable['at'])) {
                $text = rtrim($text) . "\n";
            }
            $text .= str_replace("'", ' ', $syllable['text']) . (in_array($syllable['syllabic'], ['begin', 'middle'], true) ? '' : ' ');
            $previous = $syllable;
        }

        return trim(preg_replace('/[ \t]+/u', ' ', $text));
    }

    private function knownBlocks(Song $song): array
    {
        $known = [];
        foreach (json_decode(json_encode($song->structure ?? []), true) ?? [] as $block) {
            if (is_array($block) && trim((string) ($block['lyrics'] ?? '')) !== '' && ! Song::isReturnMarker($block)) {
                $known[] = [
                    'section' => (string) $block['section'],
                    'lyrics' => (string) $block['lyrics'],
                    'anchor' => (string) ($block['anchor'] ?? Str::before(trim($block['lyrics']), "\n")),
                    'grams' => self::grams($block['lyrics']),
                ];
            }
        }

        return $known;
    }

    /** "[Refrão:]" CHORUS, "Estrofe" VERSE… (null when the words are no section's). */
    private static function sectionOf(string $words): ?string
    {
        $plain = self::plain($words);
        foreach ([
            'PRE_CHORUS' => '/\b(pre ?refrao|pre ?chorus)\b/', 'CHORUS' => '/\b(refrao|coro|chorus)\b/', 'INTRO' => '/\bintro/',
            'BRIDGE' => '/\b(ponte|bridge)\b/', 'INTERLUDE' => '/\b(interludio|interlude|instrumental)\b/', 'SOLO' => '/\bsolo\b/',
            'FINAL' => '/\b(final|outro|coda|ending)\b/', 'VERSE' => '/\b(estrofe|verso|verse|parte)\b/',
        ] as $name => $pattern) {
            if (preg_match($pattern, $plain)) {
                return $name;
            }
        }

        return null;
    }

    /** Syllables as words ("Je"+"sus" Jesus; "nome'é" nome é). */
    private static function words(array $syllables): string
    {
        $text = '';
        foreach ($syllables as $syllable) {
            $text .= str_replace("'", ' ', $syllable['text']) . (in_array($syllable['syllabic'], ['begin', 'middle'], true) ? '' : ' ');
        }

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private static function plain(string $text): string
    {
        return trim(preg_replace('/[^a-z ]+/', '', strtolower(Str::ascii($text))));
    }

    private static function letters(string $text): string
    {
        return str_replace(' ', '', self::plain($text));
    }

    /** Letter trigrams, spaces ignored (a score's syllables are often split or joined wrongly). */
    private static function grams(string $text): array
    {
        $letters = self::letters($text);
        $grams = [];
        for ($i = 0; $i + 3 <= strlen($letters); $i++) {
            $grams[substr($letters, $i, 3)] = true;
        }

        return $grams;
    }

    private static function similarity(array $a, array $b): float
    {
        $union = count($a + $b);

        return $union ? count(array_intersect_key($a, $b)) / $union : 0.0;
    }
}
