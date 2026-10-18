<?php

namespace App\Modules\Music\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

/**
 * Turns a chord sheet (the HTML of a <pre> from a chord site, or plain text with chords above the lyrics)
 * into sections whose lines use ChordPro notation: each chord is written as [Chord] at the exact
 * column of the lyric it sits on, e.g. "Que não v[C9]im a este mundo".
 *
 * Output: ['sections' => [['section' => 'VERSE_1', 'label' => 'VERSE 1', 'lines' => [...]]]]
 * Section times are not stored here: they come from the chord map (structure).
 */
class ParseChordSheetService
{
    // Chord markers used while parsing, so a chord keeps its column when tags are removed.
    private const OPEN = "\x01";
    private const CLOSE = "\x02";

    private const CHORD_PATTERN = '/^[A-G](?:#|b)?(?:m|maj|min|dim|aug|sus|add|M|°|º|\+|-|\d|\(|\)|b|#)*(?:\/[A-G](?:#|b)?)?$/u';

    // Kind of block by the words of its marker, checked in order ("Pré-refrão" before "Refrão", "Refrão Final" is a
    // chorus, "Parte instrumental" is an interlude).
    private const TYPES = [
        'PRE_CHORUS' => '/\bpre[\s-]*(refrao|chorus|coro)\b|\bprechorus\b/',
        'CHORUS'     => '/\b(refrao|chorus|coro)\b/',
        'BRIDGE'     => '/\b(ponte|bridge)\b/',
        'INTRO'      => '/\bintro(ducao)?\b/',
        'SOLO'       => '/\bsolo\b/',
        'RIFF'       => '/\briff\b/',
        'INTERLUDE'  => '/\b(interludio|interlude|instrumental|passagem|transicao|transition)\b/',
        'TAG'        => '/\btag\b/',
        'FINAL'      => '/\b(final|outro|ending|fim|encerramento)\b/',
        'VERSE'      => '/\b(verso|verse|estrofe|parte|part)\b/',
    ];

    // Blocks whose lyrics are at least this similar are the same block (a word changed, other punctuation).
    private const SAME_LYRICS = 0.85;

    // Blocks with the same marker are the same when the shorter one has at least this share of its words in the other.
    private const SAME_PART = 0.7;

    // How many times a block is played, as written in a sheet: "2x", "x2", "(3x)", "2 vezes", "Bis".
    private const COUNTER = '/\(?\s*(?:(\d+)\s*x\b|\bx\s*(\d+)|(\d+)\s*vezes\b|\b(bis)\b)\s*\)?/iu';

    // Kinds without lyrics of their own: a marker of these is kept even over lyrics.
    private const INSTRUMENTAL = ['INTRO', 'SOLO', 'RIFF', 'INTERLUDE', 'FINAL'];

    public function execute(string $input): array
    {
        $text = str_contains($input, '<') ? $this->htmlToMarkedText($input) : $this->plainToMarkedText($input);
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));

        // Without "[Section]" markers, stanzas separated by blank lines become blocks named afterwards.
        $hasMarkers = collect($lines)->contains(fn (string $line) => preg_match('/^\s*\[[^\]' . self::OPEN . self::CLOSE . ']+\]/u', $line) === 1);

        $sections = [];
        $current = null;
        // A chord-only line in parentheses, e.g. "( D2 A D2 A )", is a riff: it gets its own block and the
        // interrupted section continues in a new block afterwards.
        $riff = null;
        $resume = null;

        $addLine = function (string $line) use (&$sections, &$current, &$riff, &$resume) {
            if ($current !== null && $current === $riff) {
                $sections[] = ['section' => $resume['section'] ?? 'PART', 'label' => $resume['label'] ?? null, 'lines' => [], 'continues' => true];
                $current = array_key_last($sections);
                $riff = null;
            }
            // Lyrics right after an intro's chords start a new block, named later like an unmarked stanza.
            if ($current === null || ($this->isIntro($sections, $current) && $this->startsLyrics($sections[$current]['lines'], $line))) {
                $sections[] = ['section' => 'PART', 'label' => null, 'lines' => []];
                $current = array_key_last($sections);
            }
            $sections[$current]['lines'][] = $line;
        };

        $addRiffLine = function (string $line) use (&$sections, &$current, &$riff, &$resume) {
            if ($current === null || $current !== $riff) {
                $label = 'Riff';
                $resume = null;

                if ($current !== null) {
                    $resume = ['section' => $sections[$current]['section'], 'label' => $sections[$current]['label']];
                    // A note right before the riff ("( Riff 1 )") names it.
                    $last = end($sections[$current]['lines']);
                    if (is_string($last) && preg_match('/^\{c:\s*(.+?)\s*\}$/u', $last, $note)) {
                        array_pop($sections[$current]['lines']);
                        $label = $note[1];
                    }
                }

                $sections[] = ['section' => 'RIFF', 'label' => $label, 'lines' => [], 'riff' => true];
                $current = $riff = array_key_last($sections);
            }
            $sections[$current]['lines'][] = $line;
        };

        for ($i = 0; $i < count($lines); $i++) {
            $line = rtrim($lines[$i]);

            // A blank line ends a stanza that has no "[Section]" marker of its own (in a sheet without markers, or
            // after a marked intro in a sheet that marks only that); inside a marked section it does not.
            if (trim($line) === '') {
                if ($current !== null && $sections[$current]['lines'] !== [] && (! $hasMarkers || $sections[$current]['label'] === null)) {
                    $current = $riff = $resume = null;
                }
                continue;
            }

            // "[Primeira Parte]" starts a section; chords on the same line ("[Intro] Dm Bb2") belong to it.
            if (preg_match('/^\s*\[([^\]' . self::OPEN . self::CLOSE . ']+)\]\s*(.*)$/u', $line, $match)) {
                // A counter in the marker or after it ("[Refrão] (2x)") repeats the block.
                $repeat = self::repeatCount($match[1]) ?? (self::isCounter($match[2]) ? self::repeatCount($match[2]) : null);
                $sections[] = ['section' => $this->sectionType($match[1]), 'label' => trim($match[1]), 'lines' => [], 'repeat' => $repeat];
                $current = array_key_last($sections);
                $riff = $resume = null;
                $line = self::isCounter($match[2]) ? '' : $match[2];
                if (trim($line) === '') {
                    continue;
                }
            }

            if (! str_contains($line, self::OPEN)) {
                if (! $this->isTablature($line)) {
                    $addLine($this->textLine($line));
                }
                continue;
            }

            [$chords, $annotation] = $this->chordLine($line);
            $next = isset($lines[$i + 1]) ? rtrim($lines[$i + 1]) : '';

            if ($this->isLyric($next)) {
                if ($annotation !== '') {
                    $addLine('{c: ' . $annotation . '}');
                }
                $addLine($this->toChordPro($chords, $next));
                $i++;
            } else {
                $instrumental = $this->instrumentalLine($line);
                $this->isRiffLine($instrumental) ? $addRiffLine($instrumental) : $addLine($instrumental);
            }
        }

        $sections = array_values(array_filter($sections, fn (array $section) => $section['lines'] !== []));
        $sections = $this->nameSections($this->expandRepeats($sections));

        return [
            'sections' => array_map(function (array $section) {
                unset($section['riff'], $section['continues'], $section['repeat']);
                return $section;
            }, $sections),
        ];
    }

    /**
     * Each block written to be played more than once ("[Refrão] (2x)", or a "{c: 2x}" / "(2x)" line in it) becomes
     * that many blocks, one after the other, so the sheet follows the song as played and each time can be picked
     * alone. The counter lines are taken out ("{c: 1x}" too, which repeats nothing).
     */
    private function expandRepeats(array $sections): array
    {
        $expanded = [];

        foreach ($sections as $section) {
            $kept = [];
            foreach ($section['lines'] as $line) {
                if (is_string($line) && preg_match('/^\{c:\s*(.+?)\s*\}$/u', trim($line), $note) && self::isCounter($note[1])) {
                    $section['repeat'] ??= self::repeatCount($note[1]);
                    continue;
                }
                $kept[] = $line;
            }
            $section['lines'] = $kept;
            if ($section['lines'] === []) {
                continue;
            }
            $times = max(1, min(8, $section['repeat'] ?? 1));
            $section['repeat'] = null;
            array_push($expanded, ...array_fill(0, $times, $section));
        }

        return $expanded;
    }

    /**
     * Times written in a counter ("2x", "(3x)", "Bis" is 2), or null without one.
     */
    private static function repeatCount(string $text): ?int
    {
        if (! preg_match(self::COUNTER, Str::ascii($text), $match)) {
            return null;
        }
        $number = (int) ($match[1] ?: ($match[2] ?? '') ?: ($match[3] ?? ''));

        return ! empty($match[4]) ? 2 : ($number > 0 ? $number : null);
    }

    /**
     * True when the text is only a counter, e.g. "(2x)" or "Bis".
     */
    private static function isCounter(string $text): bool
    {
        $text = trim(Str::ascii($text));

        return $text !== '' && trim(preg_replace(self::COUNTER, '', $text)) === '';
    }

    /**
     * Intro block: marked "[Intro]", or the first unmarked block of the sheet.
     */
    private function isIntro(array $sections, int $index): bool
    {
        return $sections[$index]['section'] === 'INTRO'
            || ($sections[$index]['label'] === null && $index === array_key_first($sections));
    }

    /**
     * True when the line brings the first lyrics to a block that so far has only chords.
     */
    private function startsLyrics(array $lines, string $line): bool
    {
        return $lines !== []
            && BuildStructureFromChordSheetService::lyrics($lines) === ''
            && BuildStructureFromChordSheetService::lyrics([$line]) !== '';
    }

    /**
     * Gives every block a conventional name, the same in the chord sheet and in the map: INTRO, VERSE_1, VERSE_2,
     * PRE_CHORUS, CHORUS, BRIDGE, INTERLUDE, SOLO, RIFF, TAG, FINAL.
     *
     * The lyrics decide which blocks are the same: a block with (nearly) the lyrics of an earlier one gets its name,
     * whatever its marker says ("[Refrão Final]" with the chorus lyrics is CHORUS). The kind comes from the marker;
     * unmarked blocks are named by how their lyrics repeat (the most repeated is the chorus, another repeated one
     * first seen after it is the bridge, the others are verses) and chord-only ones by where they are (intro, final,
     * interlude). Verses are always numbered; other kinds only when there is more than one different block of them.
     * A block that continues after a riff keeps the name of the block it continues.
     */
    private function nameSections(array $sections): array
    {
        $last = count($sections) - 1;
        $groups = [];   // each: ['type' => ?string, 'lyrics' => string, 'chords' => string, 'members' => int[]]
        $groupOf = [];

        foreach ($sections as $index => $section) {
            if (! empty($section['continues'])) {
                continue;
            }
            $marked = ! empty($section['riff']) ? 'RIFF' : ($section['label'] !== null ? $section['section'] : null);
            $lyrics = self::plain(BuildStructureFromChordSheetService::lyrics($section['lines']));
            $chords = implode(' ', array_filter(BuildStructureFromChordSheetService::chords($section['lines']), fn (string $chord) => $chord !== UpdateSongLayoutService::LINE_BREAK));

            if ($lyrics === '') {
                // Chord-only blocks are the same when they are of the same kind and have the same chords.
                $marked ??= match ($index) { 0 => 'INTRO', $last => 'FINAL', default => 'INTERLUDE' };
                $found = collect($groups)->search(fn (array $group) => $group['lyrics'] === '' && $group['type'] === $marked && $group['chords'] === $chords);
            } elseif (in_array($marked, self::INSTRUMENTAL, true)) {
                // A solo or interlude written over lyrics stays what its marker says.
                $found = collect($groups)->search(fn (array $group) => $group['type'] === $marked && self::similarity($group['lyrics'], $lyrics) >= self::SAME_LYRICS);
            } else {
                $found = collect($groups)->search(fn (array $group) => $group['lyrics'] !== ''
                    && ! in_array($group['type'], self::INSTRUMENTAL, true)
                    && (self::similarity($group['lyrics'], $lyrics) >= self::SAME_LYRICS
                        // Two choruses (bridges...) where one has most of the other's lyrics, e.g. the last chorus
                        // with a line sung again, are the same block; verses are told apart by their lyrics only.
                        || ($marked !== null && $marked !== 'VERSE' && $group['type'] === $marked && self::containment($group['lyrics'], $lyrics) >= self::SAME_PART)));
            }

            if ($found === false) {
                $groups[] = ['type' => $marked, 'lyrics' => $lyrics, 'chords' => $chords, 'members' => []];
                $found = array_key_last($groups);
            }
            // The first marker found for a block decides its kind.
            $groups[$found]['type'] ??= $marked;
            $groups[$found]['members'][] = $index;
            $groupOf[$index] = $found;
        }

        $this->guessTypes($groups);

        // Numbers per kind, in the order the blocks first appear.
        $byType = [];
        foreach ($groups as $id => $group) {
            $byType[$group['type']][] = $id;
        }
        $names = [];
        foreach ($byType as $type => $ids) {
            foreach ($ids as $position => $id) {
                $names[$id] = ($type === 'VERSE' || count($ids) > 1) ? $type . '_' . ($position + 1) : $type;
            }
        }

        $previous = null;
        foreach ($sections as $index => $section) {
            $name = isset($groupOf[$index]) ? $names[$groupOf[$index]] : ($previous ?? 'VERSE_1');
            $sections[$index]['section'] = $name;
            $sections[$index]['label'] = str_replace('_', ' ', $name);
            if (empty($section['riff'])) {
                $previous = $name;
            }
        }

        return $sections;
    }

    /**
     * Kinds of the blocks without a marker: the lyrics that come back most are the chorus, the bridge is found by
     * where it comes and its chords (see below), the others are verses (only when the sheet has no chorus or bridge
     * yet). A stanza written twice in a row counts as coming once (it is a "2x").
     */
    private function guessTypes(array &$groups): void
    {
        $open = collect($groups)->filter(fn (array $group) => $group['type'] === null);
        $types = collect($groups)->pluck('type')->filter();

        // How many times a block comes back: the same stanza written twice in a row (a "2x") counts once.
        $returns = fn (array $group) => count(array_filter($group['members'], fn (int $member, int $k) => $k === 0 || $group['members'][$k - 1] !== $member - 1, ARRAY_FILTER_USE_BOTH));
        $repeated = $open->filter(fn (array $group) => $returns($group) > 1)
            ->sortByDesc(fn (array $group) => $returns($group));
        $chorus = $types->contains('CHORUS') ? null : $repeated->keys()->first();
        if ($chorus !== null) {
            $groups[$chorus]['type'] = 'CHORUS';
        }

        // The bridge: a block other than the verses, first heard after the second chorus (or coming back after the
        // first one), with chords other than the first verse's (a third verse of a hymn has the verse's chords).
        $chorusStarts = collect($groups)->filter(fn (array $group) => $group['type'] === 'CHORUS')
            ->flatMap(fn (array $group) => array_filter($group['members'], fn (int $member, int $k) => $k === 0 || $group['members'][$k - 1] !== $member - 1, ARRAY_FILTER_USE_BOTH))
            ->sort()->values();
        $firstChorus = $chorusStarts->first();
        $secondChorus = $chorusStarts->get(1);
        if ($firstChorus !== null && ! $types->contains('BRIDGE')) {
            $verse = collect($groups)->first(fn (array $group) => $group['type'] === null && $group['lyrics'] !== '' && $group['members'][0] < $firstChorus);
            $names = fn (array $group) => array_values(array_unique(array_filter(explode(' ', $group['chords']))));
            $differs = function (array $group) use ($verse, $names) {
                if (! $verse) {
                    return true;
                }
                $a = $names($group);
                $b = $names($verse);
                $union = count(array_unique(array_merge($a, $b)));

                return $union === 0 || count(array_intersect($a, $b)) / $union < 0.5;
            };
            $bridge = collect($groups)->keys()->first(fn (int $id) => $id !== $chorus && $groups[$id]['type'] === null && $groups[$id]['lyrics'] !== ''
                && (($secondChorus !== null && $groups[$id]['members'][0] > $secondChorus) || ($returns($groups[$id]) > 1 && $groups[$id]['members'][0] > $firstChorus))
                && $differs($groups[$id]));
            if ($bridge !== null) {
                $groups[$bridge]['type'] = 'BRIDGE';
            }
        }

        foreach ($groups as $id => $group) {
            $groups[$id]['type'] ??= 'VERSE';
        }
    }

    /**
     * Lyrics compared without case, accents, punctuation and spacing.
     */
    private static function plain(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($text))));
    }

    /**
     * Share of the shorter text's words that are also in the other one (each word counted as often as it appears).
     */
    private static function containment(string $a, string $b): float
    {
        [$short, $long] = str_word_count($a) <= str_word_count($b) ? [$a, $b] : [$b, $a];
        $words = explode(' ', $short);
        $other = array_count_values(explode(' ', $long));
        $common = 0;
        foreach ($words as $word) {
            if (($other[$word] ?? 0) > 0) {
                $other[$word]--;
                $common++;
            }
        }

        return $common / count($words);
    }

    /**
     * 1 for the same text, less the more characters must change to turn one into the other.
     */
    private static function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        return 1 - levenshtein($a, $b) / max(strlen($a), strlen($b));
    }

    /**
     * Text of the sheet with every chord wrapped in markers; other markup is dropped.
     */
    private function htmlToMarkedText(string $html): string
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NONET);
        libxml_clear_errors();

        $root = $document->getElementsByTagName('pre')->item(0) ?? $document->getElementsByTagName('body')->item(0);

        return $root ? $this->walk($root) : '';
    }

    private function walk(DOMNode $node): string
    {
        $text = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $text .= $child->nodeValue;
                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if ($tag === 'br') {
                $text .= "\n";
            } elseif (in_array($tag, ['script', 'style'], true)) {
                continue;
            } elseif (($chord = $this->chordFromElement($child)) !== null) {
                $text .= self::OPEN . $chord . self::CLOSE;
            } else {
                $text .= $this->walk($child);
                // Block elements end a line when the sheet does not already have a line break there.
                if (in_array($tag, ['div', 'p', 'li'], true) && ! str_ends_with($text, "\n")) {
                    $text .= "\n";
                }
            }
        }

        return $text;
    }

    private function chordFromElement(DOMElement $element): ?string
    {
        $name = trim($element->getAttribute('data-chord-name'));
        if ($name !== '') {
            return $name;
        }

        $content = trim($element->textContent);

        return in_array(strtolower($element->tagName), ['b', 'strong'], true) && $this->isChord($content) ? $content : null;
    }

    /**
     * Plain text: lines made only of chords (and brackets/bars) get their chords marked.
     */
    private function plainToMarkedText(string $text): string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));

        return implode("\n", array_map(function (string $line) {
            // A section marker may share the line with chords: "[Intro] Dm Bb2 F C9".
            if (preg_match('/^(\s*\[[^\]]+\]\s*)(.*)$/u', $line, $match)) {
                return $match[1] . $this->markChordsInPlainLine($match[2]);
            }

            return $this->markChordsInPlainLine($line);
        }, $lines));
    }

    private function markChordsInPlainLine(string $line): string
    {
        $tokens = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
        $chordTokens = array_filter($tokens, fn (string $token) => $this->isChord($token));
        $otherTokens = array_diff($tokens, $chordTokens, ['(', ')', '|', '-']);

        if ($chordTokens === [] || $otherTokens !== []) {
            return $line;
        }

        return preg_replace_callback('/\S+/', fn (array $m) => $this->isChord($m[0]) ? self::OPEN . $m[0] . self::CLOSE : $m[0], $line);
    }

    private function isChord(string $token): bool
    {
        return $token !== '' && preg_match(self::CHORD_PATTERN, $token) === 1;
    }

    /**
     * Chords of a marked line with their columns, plus any other text on it (e.g. "Riff 2").
     *
     * @return array{0: array<int, array{0: int, 1: string}>, 1: string}
     */
    private function chordLine(string $line): array
    {
        $chords = [];
        $column = 0;

        foreach (preg_split('/(' . self::OPEN . '[^' . self::CLOSE . ']*' . self::CLOSE . ')/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if (str_starts_with($part, self::OPEN)) {
                $name = mb_substr($part, 1, -1);
                // Columns follow the name as written; the chord itself gets an easy spelling (E# -> F).
                $chords[] = [$column, NormalizeChordSpellingService::chord($name)];
                $column += mb_strlen($name);
            } else {
                $column += mb_strlen($part);
            }
        }

        $annotation = trim(preg_replace('/' . self::OPEN . '[^' . self::CLOSE . ']*' . self::CLOSE . '|[()|]/u', ' ', $line));

        return [$chords, preg_replace('/\s+/u', ' ', $annotation)];
    }

    /**
     * Places each chord as [Chord] at its column over the lyric; leading indentation is removed from both.
     */
    private function toChordPro(array $chords, string $lyric): string
    {
        $indent = mb_strlen($lyric) - mb_strlen(ltrim($lyric));
        $lyric = trim($lyric);
        $result = '';
        $last = 0;

        foreach ($chords as [$column, $name]) {
            $position = max($last, $column - $indent);
            if ($position > mb_strlen($lyric)) {
                $lyric .= str_repeat(' ', $position - mb_strlen($lyric));
            }
            $result .= mb_substr($lyric, $last, $position - $last) . '[' . $name . ']';
            $last = $position;
        }

        return $result . mb_substr($lyric, $last);
    }

    /**
     * Chords without lyrics, e.g. "( [Dm] [C/E] [F] [Gm] )".
     */
    private function instrumentalLine(string $line): string
    {
        $line = preg_replace_callback('/' . self::OPEN . '([^' . self::CLOSE . ']*)' . self::CLOSE . '/u', fn (array $m) => '[' . NormalizeChordSpellingService::chord($m[1]) . ']', $line);

        return trim(preg_replace('/\s+/u', ' ', $line));
    }

    /**
     * Chord-only line wrapped in parentheses, e.g. "( [D2] [A] [D2] [A] )".
     */
    private function isRiffLine(string $line): bool
    {
        return preg_match('/^\(.*\[[^\]]+\].*\)$/u', $line) === 1;
    }

    /**
     * A text line: "( Riff 1 )" becomes a comment, anything else is lyric.
     */
    private function textLine(string $line): string
    {
        if (preg_match('/^\s*\(\s*([^()]+?)\s*\)\s*$/u', $line, $match)) {
            return '{c: ' . $match[1] . '}';
        }
        if (self::isCounter($line)) {
            return '{c: ' . trim($line) . '}';
        }

        return trim($line);
    }

    /**
     * Tablature lines such as "E|---3---5-|" or "B|--1--|" are not lyrics.
     */
    private function isTablature(string $line): bool
    {
        return preg_match('/^\s*[EADGBe]\s*\|[-0-9hpbrx\/\\~|().\s]*$/u', $line) === 1;
    }

    private function isLyric(string $line): bool
    {
        return trim($line) !== ''
            && ! $this->isTablature($line)
            && ! str_contains($line, self::OPEN)
            && ! preg_match('/^\s*\[[^\]]+\]/u', $line)
            && ! preg_match('/^\s*\([^()]*\)\s*$/u', $line)
            && ! self::isCounter($line);
    }

    /**
     * Kind of block written in a marker: "[Refrão Final]" is CHORUS, "[Primeira Parte]" is VERSE; a marker of
     * another kind ("[Coda]") keeps its own name, without numbers.
     */
    private function sectionType(string $label): string
    {
        $normalized = Str::lower(Str::ascii($label));
        $normalized = trim(preg_replace('/\s+/', ' ', preg_replace(['/\(.*?\)/u', self::COUNTER], ' ', $normalized)));

        foreach (self::TYPES as $type => $pattern) {
            if (preg_match($pattern, $normalized)) {
                return $type;
            }
        }

        return trim(preg_replace('/[^A-Z]+/', '_', Str::upper($normalized)), '_') ?: 'VERSE';
    }
}
