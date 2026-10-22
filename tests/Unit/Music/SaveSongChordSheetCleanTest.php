<?php

namespace Tests\Unit\Music;

use App\Modules\Music\Services\SaveSongChordSheetService;
use PHPUnit\Framework\TestCase;

/**
 * A chord sheet as saved: what is pasted from chord sites made clean.
 */
class SaveSongChordSheetCleanTest extends TestCase
{
    public function test_a_blocks_name_is_a_note_and_invisible_characters_go(): void
    {
        $lines = SaveSongChordSheetService::clean([['section' => 'INTRO', 'lines' => [
            "\u{200B}[Intro] [G#m] [A] [G#m]",
            "\u{200B} [A] [G#] [A] [B]",
            "{c: \u{200B}}",
            '[Refrão]',
            '[N.C.]Eu não tenho [?]mais',
        ]]])[0]['lines'];

        $this->assertSame(['{c: Intro}', '[G#m] [A] [G#m]', ' [A] [G#] [A] [B]', '{c: Refrão}', '[N.C.]Eu não tenho [?]mais'], $lines);
    }
}
