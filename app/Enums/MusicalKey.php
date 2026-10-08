<?php

namespace App\Enums;

class MusicalKey
{
    // Conventional spellings, so chords transposed to these keys are written correctly.
    // MINOR[i] is the relative minor of MAJOR[i] (same chords).
    public const MAJOR = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B'];
    public const MINOR = ['Am', 'Bbm', 'Bm', 'Cm', 'C#m', 'Dm', 'Ebm', 'Em', 'Fm', 'F#m', 'Gm', 'G#m'];

    /**
     * Keys offered by the selectors: one per pair of relative keys, "C / Am", with the major and the minor key.
     * Major songs store the major key of the chosen pair and minor songs the minor one.
     */
    public static function pairs(): array
    {
        return array_map(fn (string $major, string $minor) => [
            'major' => $major,
            'minor' => $minor,
            'label' => "$major / $minor",
        ], self::MAJOR, self::MINOR);
    }

    /**
     * Label of a key in the selectors: its pair ("Am" -> "C / Am"); other keys as they are.
     */
    public static function label(?string $key): string
    {
        foreach (self::pairs() as $pair) {
            if ($key === $pair['major'] || $key === $pair['minor']) {
                return $pair['label'];
            }
        }

        return (string) $key;
    }

    /**
     * Whether a key is minor ("Am", "C#m"); keys without a mode count as major.
     */
    public static function isMinor(?string $key): bool
    {
        return (bool) preg_match('/^[A-G][#b]?m$/', (string) $key);
    }

    /**
     * Flat list of every key the selectors can store.
     */
    public static function values(): array
    {
        return [...self::MAJOR, ...self::MINOR];
    }
}
