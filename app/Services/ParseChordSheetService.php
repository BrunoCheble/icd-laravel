<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

/**
 * Turns a chord sheet (the HTML of a <pre> from a chord site, or plain text with chords above the lyrics)
 * into sections whose lines use ChordPro notation: each chord is written as [Chord] at the exact
 * column of the lyric it sits on, e.g. "Que não v[C9]im a este mundo".
 *
 * Output: ['sections' => [['section' => 'FIRST_PART', 'label' => 'Primeira Parte', 'lines' => [...]]]]
 * Section times are not stored here: they come from the chord map (structure).
 */
class ParseChordSheetService
{
    // Chord markers used while parsing, so a chord keeps its column when tags are removed.
    private const OPEN = "\x01";
    private const CLOSE = "\x02";

    private const CHORD_PATTERN = '/^[A-G](?:#|b)?(?:m|maj|min|dim|aug|sus|add|M|°|º|\+|-|\d|\(|\)|b|#)*(?:\/[A-G](?:#|b)?)?$/u';

    private const SECTION_KEYS = [
        'intro' => 'INTRO',
        'introducao' => 'INTRO',
        'primeira parte' => 'FIRST_PART',
        'segunda parte' => 'SECOND_PART',
        'terceira parte' => 'THIRD_PART',
        'verso' => 'VERSE',
        'pre-refrao' => 'PRE_CHORUS',
        'pre refrao' => 'PRE_CHORUS',
        'refrao' => 'CHORUS',
        'coro' => 'CHORUS',
        'ponte' => 'BRIDGE',
        'solo' => 'SOLO',
        'interludio' => 'INTERLUDE',
        'riff' => 'RIFF',
        'passagem' => 'TRANSITION',
        'transicao' => 'TRANSITION',
        'final' => 'FINAL',
        'outro' => 'OUTRO',
        'tag' => 'TAG',
    ];

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
                $sections[] = ['section' => $resume['section'] ?? 'PART', 'label' => $resume['label'] ?? null, 'lines' => []];
                $current = array_key_last($sections);
                $riff = null;
            }
            if ($current === null) {
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

            if (trim($line) === '') {
                if (! $hasMarkers && $current !== null && $sections[$current]['lines'] !== []) {
                    $current = $riff = $resume = null;
                }
                continue;
            }

            // "[Primeira Parte]" starts a section; chords on the same line ("[Intro] Dm Bb2") belong to it.
            if (preg_match('/^\s*\[([^\]' . self::OPEN . self::CLOSE . ']+)\]\s*(.*)$/u', $line, $match)) {
                $sections[] = ['section' => $this->sectionKey($match[1]), 'label' => trim($match[1]), 'lines' => []];
                $current = array_key_last($sections);
                $riff = $resume = null;
                $line = $match[2];
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
        $sections = $hasMarkers ? $sections : $this->nameStanzas($sections);

        return [
            'sections' => array_map(function (array $section) {
                unset($section['riff']);
                return $section;
            }, $sections),
        ];
    }

    /**
     * Names unlabeled stanzas by how their lyrics repeat: the most repeated one is the chorus, another
     * repeated one first seen after the chorus is the bridge, the others are parts; chord-only stanzas are
     * intro (first), final (last) or interlude.
     */
    private function nameStanzas(array $sections): array
    {
        $keys = array_map(
            fn (array $section) => ! empty($section['riff']) ? '' : Str::lower(preg_replace('/\s+/u', ' ', BuildStructureFromChordSheetService::lyrics($section['lines']))),
            $sections,
        );
        $counts = array_count_values(array_filter($keys, fn (string $key) => $key !== ''));
        arsort($counts);

        $chorus = ($counts !== [] && reset($counts) > 1) ? array_key_first($counts) : null;
        $firstChorus = $chorus === null ? null : array_search($chorus, $keys, true);
        $bridge = collect($counts)
            ->filter(fn (int $count, string $key) => $count > 1 && $key !== $chorus && array_search($key, $keys, true) > $firstChorus)
            ->keys()
            ->first();

        $partNames = [
            ['FIRST_PART', 'Primeira Parte'],
            ['SECOND_PART', 'Segunda Parte'],
            ['THIRD_PART', 'Terceira Parte'],
        ];
        $parts = [];
        $last = count($sections) - 1;

        foreach ($sections as $index => $section) {
            if (! empty($section['riff'])) {
                continue;
            }
            $key = $keys[$index];

            [$sections[$index]['section'], $sections[$index]['label']] = match (true) {
                $key === '' && $index === 0 => ['INTRO', 'Intro'],
                $key === '' && $index === $last => ['FINAL', 'Final'],
                $key === '' => ['INTERLUDE', 'Interlúdio'],
                $key === $chorus => ['CHORUS', 'Refrão'],
                $key === $bridge => ['BRIDGE', 'Ponte'],
                default => $partNames[$parts[$key] ??= count($parts)] ?? ['PART_' . ($parts[$key] + 1), 'Parte ' . ($parts[$key] + 1)],
            };
        }

        return $sections;
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
                $chords[] = [$column, $name];
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
        $line = preg_replace_callback('/' . self::OPEN . '([^' . self::CLOSE . ']*)' . self::CLOSE . '/u', fn (array $m) => '[' . $m[1] . ']', $line);

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
            && ! preg_match('/^\s*\([^()]*\)\s*$/u', $line);
    }

    private function sectionKey(string $label): string
    {
        $normalized = Str::lower(Str::ascii($label));
        $normalized = trim(preg_replace('/\(.*?\)|\b\d+\s*x\b|\s+/u', ' ', $normalized));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return self::SECTION_KEYS[$normalized]
            ?? (trim(preg_replace('/[^A-Z0-9]+/', '_', Str::upper(Str::ascii($label))), '_') ?: 'PART');
    }
}
