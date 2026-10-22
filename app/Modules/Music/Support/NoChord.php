<?php

namespace App\Modules\Music\Support;

/**
 * Silence where a chord would be ("N.C.", no chord): a bar or a part played without one. It is written "N.C." in
 * the map and in the chord sheet; it is never transposed, has no bass note and is no chord to hear in a recording.
 * Typed as "N.C.", "NC", "N/C", "silêncio" or "-".
 */
class NoChord
{
    public const NAME = 'N.C.';

    /** "N.C." for the ways silence is written; null for anything else. */
    public static function from(?string $text): ?string
    {
        $plain = mb_strtolower(trim((string) $text));

        return in_array($plain, ['n.c.', 'n.c', 'nc', 'n/c', 'silêncio', 'silencio', '-', '—'], true) ? self::NAME : null;
    }

    public static function is(?string $chord): bool
    {
        return $chord === self::NAME;
    }
}
