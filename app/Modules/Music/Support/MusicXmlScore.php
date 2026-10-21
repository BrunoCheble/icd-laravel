<?php

namespace App\Modules\Music\Support;

use RuntimeException;
use SimpleXMLElement;

/**
 * A score in MusicXML (".musicxml" / ".xml", or ".mxl": the same zipped, read here without PHP's zip extension),
 * as its first part's bars, in the order they are written: for each bar its number, length in beats, chords (beat
 * and name, in Brazilian chord spelling: E4, A2, C#m7(9), E7M), lyric syllables (with the beat they are sung on),
 * rests, and the marks that end or start a part of the song (repeats, endings, double bars, rehearsal marks and
 * section words such as "[Refrão:]"). Also the title and the tempo.
 */
class MusicXmlScore
{
    private const KINDS = [
        'major' => '', 'minor' => 'm', 'augmented' => '+', 'diminished' => '°', 'dominant' => '7', 'major-seventh' => '7M',
        'minor-seventh' => 'm7', 'diminished-seventh' => '°7', 'augmented-seventh' => '7(#5)', 'half-diminished' => 'm7(b5)',
        'major-minor' => 'm(7M)', 'major-sixth' => '6', 'minor-sixth' => 'm6', 'dominant-ninth' => '7(9)', 'major-ninth' => '7M(9)',
        'minor-ninth' => 'm7(9)', 'dominant-11th' => '7(11)', 'dominant-13th' => '7(13)', 'suspended-second' => '2',
        'suspended-fourth' => '4', 'power' => '5',
    ];
    private const ALTER = ['1' => '#', '-1' => 'b', '2' => '##', '-2' => 'bb'];

    public ?string $title = null;
    public ?float $tempo = null;
    public int $beatsPerBar = 4;
    public int $beatType = 4;
    /** @var array<int, array{number: string, beats: float, chords: array, syllables: array, rests: array, marks: array, words: array}> */
    public array $bars = [];

    public static function fromFile(string $path, ?string $name = null): self
    {
        $data = (string) file_get_contents($path);
        $xml = str_starts_with($data, "PK\x03\x04") ? self::unzip($data) : $data;

        return new self($xml);
    }

    public function __construct(string $xml)
    {
        $previous = libxml_use_internal_errors(true);
        // No external entities (a score is only data).
        $score = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if (! $score || $score->getName() !== 'score-partwise' || ! isset($score->part[0])) {
            throw new RuntimeException(__('This is not a MusicXML score (score-partwise).'));
        }
        $this->title = trim((string) ($score->work->{'work-title'} ?? '')) ?: (trim((string) ($score->{'movement-title'} ?? '')) ?: null);
        $this->read($score->part[0]);
    }

    private function read(SimpleXMLElement $part): void
    {
        $divisions = 1;
        foreach ($part->measure as $measure) {
            foreach ($measure->attributes as $attributes) {
                $divisions = (int) ($attributes->divisions ?? 0) ?: $divisions;
                if (isset($attributes->time->beats)) {
                    $this->beatsPerBar = (int) $attributes->time->beats ?: 4;
                    $this->beatType = (int) $attributes->time->{'beat-type'} ?: 4;
                }
            }
            // A "beat" is the time signature's unit (an eighth in 6/8).
            $perBeat = $divisions * 4 / $this->beatType;
            $position = 0;
            $bar = ['number' => (string) $measure['number'], 'beats' => (float) $this->beatsPerBar, 'chords' => [], 'syllables' => [], 'rests' => [], 'marks' => [], 'words' => []];
            foreach ($measure->children() as $element) {
                switch ($element->getName()) {
                    case 'harmony':
                        $offset = (int) ($element->offset ?? 0);
                        $bar['chords'][] = [($position + $offset) / $perBeat, self::chordName($element)];
                        break;
                    case 'note':
                        if (isset($element->chord)) {
                            break;
                        }
                        $beat = $position / $perBeat;
                        if (isset($element->rest) && (string) ($element->voice ?? '1') === '1') {
                            $bar['rests'][] = $beat;
                        }
                        foreach ($element->lyric as $lyric) {
                            if ((string) ($lyric['number'] ?? '1') !== '1' || ! isset($lyric->text)) {
                                continue;
                            }
                            $bar['syllables'][] = ['text' => (string) $lyric->text, 'syllabic' => (string) ($lyric->syllabic ?? 'single'), 'beat' => $beat];
                        }
                        if (! isset($element->grace)) {
                            $position += (int) ($element->duration ?? 0);
                        }
                        break;
                    case 'backup':
                        $position -= (int) $element->duration;
                        break;
                    case 'forward':
                        $position += (int) $element->duration;
                        break;
                    case 'barline':
                        $this->barline($element, $bar);
                        break;
                    case 'direction':
                        foreach ($element->xpath('.//rehearsal') ?: [] as $mark) {
                            $bar['marks'][] = 'rehearsal';
                            $bar['words'][] = trim((string) $mark);
                        }
                        foreach ($element->xpath('.//words') ?: [] as $words) {
                            if (trim((string) $words) !== '') {
                                $bar['words'][] = trim((string) $words);
                            }
                        }
                        foreach ($element->xpath('.//sound[@tempo]') ?: [] as $sound) {
                            $this->tempo ??= (float) $sound['tempo'];
                        }
                        break;
                    case 'sound':
                        if (isset($element['tempo'])) {
                            $this->tempo ??= (float) $element['tempo'];
                        }
                        break;
                }
            }
            $bar['beats'] = $this->beatsPerBar;
            $this->bars[] = $bar;
        }
    }

    private function barline(SimpleXMLElement $barline, array &$bar): void
    {
        $location = (string) ($barline['location'] ?? 'right');
        if (in_array((string) ($barline->{'bar-style'} ?? ''), ['light-light', 'light-heavy', 'heavy-light', 'heavy-heavy'], true)) {
            $bar['marks'][] = $location . '-double';
        }
        if (isset($barline->repeat)) {
            $bar['marks'][] = 'repeat-' . $barline->repeat['direction'];
        }
        if (isset($barline->ending)) {
            $bar['marks'][] = 'ending:' . $barline->ending['number'] . ':' . $barline->ending['type'];
        }
    }

    /** "E4", "A2", "C#m7(9)", "E/G#"… */
    private static function chordName(SimpleXMLElement $harmony): string
    {
        $name = (string) $harmony->root->{'root-step'} . (self::ALTER[(string) ($harmony->root->{'root-alter'} ?? '')] ?? '');
        $kind = (string) ($harmony->kind ?? 'major');
        $symbol = self::KINDS[$kind] ?? null;
        $text = (string) ($harmony->kind['text'] ?? '');
        // The text shown on the score when it says more than the kind (a "minor" written "m79").
        if ($symbol === null || (preg_match('/\d/', $text) && ! preg_match('/\d/', $symbol))) {
            $symbol = self::spell($text);
        }
        $name .= $symbol;
        $added = [];
        foreach ($harmony->degree as $degree) {
            if ((string) $degree->{'degree-type'} === 'add') {
                $added[] = (self::ALTER[(string) ($degree->{'degree-alter'} ?? '')] ?? '') . (string) $degree->{'degree-value'};
            }
        }
        if ($added) {
            $name .= str_contains($name, '(') ? '' : '(' . implode(',', $added) . ')';
        }
        if (isset($harmony->bass)) {
            $name .= '/' . (string) $harmony->bass->{'bass-step'} . (self::ALTER[(string) ($harmony->bass->{'bass-alter'} ?? '')] ?? '');
        }

        return $name;
    }

    /** A chord's text in Brazilian spelling: "maj7" 7M, "sus4" 4, "m79" m7(9), "add9" (9). */
    private static function spell(string $text): string
    {
        $text = str_replace(['maj7', 'Maj7', 'Δ', 'sus4', 'sus2', 'sus', 'min', 'add'], ['7M', '7M', '7M', '4', '2', '4', 'm', ''], $text);

        return preg_replace('/^(m?)(7M|7|6)(9|11|13)$/', '$1$2($3)', preg_replace('/^(m?)(9|11|13)$/', '$1($2)', $text));
    }

    /** The score inside an ".mxl" (a zip): the file META-INF/container.xml points to, or the first .xml. */
    private static function unzip(string $zip): string
    {
        $files = [];
        $end = strrpos($zip, "PK\x05\x06");
        if ($end === false) {
            throw new RuntimeException(__('The .mxl file could not be read.'));
        }
        $count = unpack('v', substr($zip, $end + 10, 2))[1];
        $offset = unpack('V', substr($zip, $end + 16, 4))[1];
        for ($i = 0; $i < $count; $i++) {
            $entry = unpack('Vsig/vmade/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vsize/vnamelen/vextralen/vcommentlen/vdisk/vint/Vext/Vlocal', substr($zip, $offset, 46));
            $name = substr($zip, $offset + 46, $entry['namelen']);
            $local = unpack('Vsig/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vsize/vnamelen/vextralen', substr($zip, $entry['local'], 30));
            $data = substr($zip, $entry['local'] + 30 + $local['namelen'] + $local['extralen'], $entry['csize']);
            $files[$name] = fn () => $entry['method'] === 8 ? gzinflate($data) : $data;
            $offset += 46 + $entry['namelen'] + $entry['extralen'] + $entry['commentlen'];
        }
        $path = null;
        if (isset($files['META-INF/container.xml'])) {
            preg_match('/full-path="([^"]+)"/', (string) $files['META-INF/container.xml'](), $match);
            $path = $match[1] ?? null;
        }
        $path = $path && isset($files[$path]) ? $path : collect(array_keys($files))->first(fn ($file) => ! str_starts_with($file, 'META-INF/') && preg_match('/\.(xml|musicxml)$/i', $file));
        $xml = $path ? $files[$path]() : false;
        if (! is_string($xml) || $xml === '') {
            throw new RuntimeException(__('The .mxl file could not be read.'));
        }

        return $xml;
    }
}
