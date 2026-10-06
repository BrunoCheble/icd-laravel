<?php

namespace App\Services;

/**
 * Chord names easy to read: notes spelled E#, B#, Cb, Fb or with double accidentals (F##, Bbb, Cx) are written with
 * their simple name (F, C, B, E, G, A, D). Same sound, only the spelling changes; same rule as chord-transposer.js.
 */
class NormalizeChordSpellingService
{
    private const NOTE = '([A-G])(##|bb|x|#|b|♯|♭)?';
    private const NATURAL_PC = ['C' => 0, 'D' => 2, 'E' => 4, 'F' => 5, 'G' => 7, 'A' => 9, 'B' => 11];
    private const ACCIDENTALS = ['' => 0, '#' => 1, '♯' => 1, '##' => 2, 'x' => 2, 'b' => -1, '♭' => -1, 'bb' => -2];
    private const SIMPLE = ['E#' => 'F', 'B#' => 'C', 'Cb' => 'B', 'Fb' => 'E'];
    private const SHARP_NAMES = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    private const FLAT_NAMES = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

    /**
     * "E#m7/B#" -> "Fm7/C"; anything that is not a chord is returned as it is.
     */
    public static function chord(string $chord): string
    {
        $pattern = '/^([(\[]?)' . self::NOTE . '(.*?)(?:\/' . self::NOTE . ')?([)\]]?)$/u';

        if (! preg_match($pattern, $chord, $match)) {
            return $chord;
        }

        $root = self::note($match[2], $match[3] ?? '');
        $bass = ($match[5] ?? '') !== '' ? '/' . self::note($match[5], $match[6] ?? '') : '';

        return $match[1] . $root . $match[4] . $bass . ($match[7] ?? '');
    }

    /**
     * The [Chord] marks of a ChordPro line.
     */
    public static function line(string $line): string
    {
        return preg_replace_callback('/\[([^\]]+)\]/u', fn (array $m) => '[' . self::chord($m[1]) . ']', $line);
    }

    private static function note(string $letter, string $accidental): string
    {
        $name = $letter . $accidental;
        if (isset(self::SIMPLE[$name])) {
            return self::SIMPLE[$name];
        }
        if (in_array($accidental, ['##', 'x', 'bb'], true)) {
            $pc = (self::NATURAL_PC[$letter] + self::ACCIDENTALS[$accidental] + 12) % 12;
            return ($accidental === 'bb' ? self::FLAT_NAMES : self::SHARP_NAMES)[$pc];
        }

        return $name;
    }
}
