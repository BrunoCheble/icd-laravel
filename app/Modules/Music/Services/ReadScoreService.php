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

        return [
            'title' => $score->title,
            'bpm' => $score->tempo ? round($score->tempo) : null,
            'meter' => $score->beatsPerBar . '/' . $score->beatType,
            'beats_per_bar' => $score->beatsPerBar,
            'bars' => count($this->order),
            'blocks' => array_map(fn ($block) => array_diff_key($block, ['grams' => 1]), $blocks),
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
        foreach ($segment as $j => $p) {
            $bar = $bars[$this->order[$p]];
            if ($j > 0 && $j % self::BARS_PER_LINE === 0) {
                $chords[] = '|';
            }
            $marks = array_map(fn ($chord) => [round($chord[0] / self::STEP) * self::STEP, $chord[1]], $bar['chords']);
            if (! $marks || $marks[0][0] > 0) {
                // The chord before goes on (a "?" when there is none yet).
                array_unshift($marks, [0, $last ?? '?']);
            }
            foreach ($marks as $c => [$beat, $name]) {
                $end = $marks[$c + 1][0] ?? $bar['beats'];
                if ($end - $beat > 0) {
                    // The same chord going on (also over the bar line) is one longer chord, as on the grid.
                    if ($chords && end($chords) === $name) {
                        $durations[array_key_last($durations)] += $end - $beat;
                    } else {
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
        $a['chords'] = array_merge($a['chords'], ['|'], $b['chords']);
        $a['durations'] = array_merge($a['durations'], $b['durations']);
        $a['bars'] += $b['bars'];
        $a['measures'] .= ', ' . $b['measures'];
        if (! $a['known']) {
            $a['lyrics'] = trim(($a['lyrics'] ?? '') . "\n" . ($b['lyrics'] ?? '')) ?: null;
        }

        return $a;
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
