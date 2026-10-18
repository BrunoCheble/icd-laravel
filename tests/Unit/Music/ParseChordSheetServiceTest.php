<?php

namespace Tests\Unit\Music;

use App\Modules\Music\Services\ParseChordSheetService;
use PHPUnit\Framework\TestCase;

class ParseChordSheetServiceTest extends TestCase
{
    public function test_marked_blocks_get_conventional_names_by_their_lyrics(): void
    {
        $sheet = <<<'TXT'
        [Intro] G D Em C

        [Primeira Parte]
        G          D
        Digno é o Cordeiro que foi morto
        Em         C
        Santo, Santo Ele é

        [Refrão]
        C          G
        Aleluia, aleluia ao Rei
        D          Em
        Glória ao Rei dos reis

        [Segunda Parte]
        G          D
        Um novo cântico ao que se assenta
        Em         C
        Sobre o trono do céu

        [Refrão]
        C          G
        Aleluia, aleluia ao Rei
        D          Em
        Glória ao Rei dos reis

        [Ponte]
        Am         C
        Tu és digno de louvor

        [Refrão Final]
        C          G
        Aleluia, aleluia ao Rei
        D          Em
        Glória ao Rei dos reis!
        TXT;

        $sections = (new ParseChordSheetService())->execute($sheet)['sections'];

        $this->assertSame(['INTRO', 'VERSE_1', 'CHORUS', 'VERSE_2', 'CHORUS', 'BRIDGE', 'CHORUS'], array_column($sections, 'section'));
        $this->assertSame(['INTRO', 'VERSE 1', 'CHORUS', 'VERSE 2', 'CHORUS', 'BRIDGE', 'CHORUS'], array_column($sections, 'label'));
    }

    public function test_unmarked_blocks_are_named_by_how_their_lyrics_repeat(): void
    {
        $sheet = <<<'TXT'
        G D Em C

        G          D
        Digno é o Cordeiro que foi morto

        C          G
        Aleluia, aleluia ao Rei

        G          D
        Um novo cântico ao que se assenta

        C          G
        Aleluia, aleluia ao Rei

        Am         C
        Tu és digno de louvor

        Am         C
        Tu és digno de louvor

        C          G
        Aleluia, aleluia ao Rei

        G D C
        TXT;

        $this->assertSame(['INTRO', 'VERSE_1', 'CHORUS', 'VERSE_2', 'CHORUS', 'BRIDGE', 'BRIDGE', 'CHORUS', 'FINAL'], $this->sectionNames($sheet));
    }

    public function test_blocks_of_the_same_kind_with_different_lyrics_are_numbered(): void
    {
        $sheet = <<<'TXT'
        [Refrão]
        C          G
        Aleluia, aleluia ao Rei

        [Refrão Final]
        D          Em
        Para sempre cantarei
        TXT;

        $this->assertSame(['CHORUS_1', 'CHORUS_2'], $this->sectionNames($sheet));
    }

    public function test_a_last_chorus_sung_longer_is_still_the_chorus(): void
    {
        $sheet = <<<'TXT'
        [Refrão]
        C          G
        Aleluia, aleluia ao Rei
        D          Em
        Glória ao Rei dos reis

        [Refrão Final]
        C          G
        Aleluia, aleluia ao Rei
        D          Em
        Glória ao Rei dos reis
        D          Em
        Glória ao Rei dos reis, para sempre
        TXT;

        $this->assertSame(['CHORUS', 'CHORUS'], $this->sectionNames($sheet));
    }

    public function test_a_solo_over_lyrics_keeps_its_marker(): void
    {
        $sheet = <<<'TXT'
        [Primeira Parte]
        G          D
        Digno é o Cordeiro que foi morto

        [Solo]
        G          D
        Digno é o Cordeiro que foi morto
        TXT;

        $this->assertSame(['VERSE_1', 'SOLO'], $this->sectionNames($sheet));
    }

    public function test_parts_written_twice_with_the_same_lyrics_share_a_name(): void
    {
        $sheet = <<<'TXT'
        [Primeira Parte]
        G          D
        Digno é o Cordeiro que foi morto

        [Segunda Parte]
        G          D
        Digno é o Cordeiro que foi morto
        TXT;

        $this->assertSame(['VERSE_1', 'VERSE_1'], $this->sectionNames($sheet));
    }

    public function test_verses_are_always_numbered_and_other_markers_keep_their_name(): void
    {
        $sheet = <<<'TXT'
        [Verso]
        G          D
        Digno é o Cordeiro que foi morto

        [Coda (2x)]
        G          D
        Para sempre
        TXT;

        $this->assertSame(['VERSE_1', 'CODA', 'CODA'], $this->sectionNames($sheet));
    }

    public function test_a_block_written_to_repeat_becomes_one_block_per_time(): void
    {
        $sheet = <<<'TXT'
        [Primeira Parte]
        G          D
        Digno é o Cordeiro que foi morto

        [Refrão] (2x)
        C          G
        Aleluia, aleluia ao Rei

        [Ponte 3x]
        Am         C
        Tu és digno de louvor

        [Final]
        G D C
        Bis
        TXT;

        $sections = (new ParseChordSheetService())->execute($sheet)['sections'];

        $this->assertSame(['VERSE_1', 'CHORUS', 'CHORUS', 'BRIDGE', 'BRIDGE', 'BRIDGE', 'FINAL', 'FINAL'], array_column($sections, 'section'));
        $this->assertSame($sections[1]['lines'], $sections[2]['lines']);
        $this->assertSame(['[G] [D] [C]'], $sections[7]['lines']);
        $this->assertArrayNotHasKey('repeat', $sections[1]);
    }

    public function test_a_counter_line_at_the_end_of_a_block_repeats_it(): void
    {
        $sheet = <<<'TXT'
        [Refrão]
        C          G
        Aleluia, aleluia ao Rei
        (2x)

        [Ponte]
        Am         C
        Tu és digno de louvor
        TXT;

        $this->assertSame(['CHORUS', 'CHORUS', 'BRIDGE'], $this->sectionNames($sheet));
    }

    public function test_a_counter_note_anywhere_in_a_block_repeats_it_and_is_taken_out(): void
    {
        $sheet = <<<'TXT'
        [Refrão]
        {c: 3x}
        C          G
        Aleluia, aleluia ao Rei

        [Ponte]
        {c: 1x}
        Am         C
        Tu és digno de louvor
        TXT;

        $sections = (new ParseChordSheetService())->execute($sheet)['sections'];

        $this->assertSame(['CHORUS', 'CHORUS', 'CHORUS', 'BRIDGE'], array_column($sections, 'section'));
        $this->assertStringNotContainsString('{c:', json_encode(array_column($sections, 'lines')));
    }

    public function test_stanzas_after_a_marked_intro_are_split_by_blank_lines(): void
    {
        $sheet = <<<'TXT'
        [Intro] G G/B G G/B Em

        G              G/B
        Se passam os anos
        G                   G/B
        Eu não posso deixar de amá-lo

        Am             G/B             G
        Oh Espírito, abre os meus olhos outra vez
        Am             G/B             Em
        Quero ver a beleza de Deus no Filho

        G                  G/B
        O segredo de Deus e o seu saber
        G               Em
        Persegui sua glória, achei o Filho

        G                  G/B
        O segredo de Deus e o seu saber
        G               Em
        Persegui sua glória, achei o Filho

        Am             G/B             G
        Oh Espírito, abre os meus olhos outra vez
        Am             G/B             Em
        Quero ver a beleza de Deus no Filho

        C7M            G/B
        Cristo, beleza de Deus
        C7M            G/B
        Cristo, fonte de prazer
        TXT;

        // The stanza written twice in a row after the first chorus is a verse sung twice; the one with other chords
        // after the second chorus is the bridge.
        $this->assertSame(['INTRO', 'VERSE_1', 'CHORUS', 'VERSE_2', 'VERSE_2', 'CHORUS', 'BRIDGE'], $this->sectionNames($sheet));
    }

    public function test_a_third_verse_with_the_verse_chords_is_not_a_bridge(): void
    {
        $sheet = <<<'TXT'
        G          D
        Primeira estrofe do hino

        C          G
        Aleluia, aleluia ao Rei

        G          D
        Segunda estrofe do hino

        C          G
        Aleluia, aleluia ao Rei

        G          D
        Terceira estrofe do hino

        C          G
        Aleluia, aleluia ao Rei
        TXT;

        $this->assertSame(['VERSE_1', 'CHORUS', 'VERSE_2', 'CHORUS', 'VERSE_3', 'CHORUS'], $this->sectionNames($sheet));
    }

    private function sectionNames(string $sheet): array
    {
        return array_column((new ParseChordSheetService())->execute($sheet)['sections'], 'section');
    }
}
