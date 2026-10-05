<?php

namespace App\Enums;

class MusicalKey
{
    // Conventional spellings, so chords transposed to these keys are written correctly.
    public const MAJOR = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];
    public const MINOR = ['Cm', 'C#m', 'Dm', 'Ebm', 'Em', 'Fm', 'F#m', 'Gm', 'G#m', 'Am', 'Bbm', 'Bm'];

    /**
     * Major and minor keys grouped for the key selector.
     */
    public static function options(): array
    {
        return [
            __('Major') => self::MAJOR,
            __('Minor') => self::MINOR,
        ];
    }

    /**
     * Flat list of every key in the selector.
     */
    public static function values(): array
    {
        return array_merge(...array_values(self::options()));
    }
}
