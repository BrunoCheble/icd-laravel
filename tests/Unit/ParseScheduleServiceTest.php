<?php

namespace Tests\Unit;

use App\Services\ParseScheduleService;
use PHPUnit\Framework\TestCase;

class ParseScheduleServiceTest extends TestCase
{
    public function test_a_louveapp_schedule_is_read(): void
    {
        $text = <<<'TXT'
        Culto de domingo
        Domingo, 18 de outubro de 2026 · 08:00

        Músicas
        1. Cristo (Ao Vivo) — Som Do Reino
           Tom: G · BPM: 120
           Vídeo: https://youtu.be/iWYcaIeo9E8?si=KM7aVfO5OMc20ktR
           Letra: https://www.letras.mus.br/som-do-reino/cristo/
           Cifra: https://www.cifraclub.com.br/som-do-reino/cristo/
           Áudio: https://open.spotify.com/track/6PVI5kB1sQdPcBhIUu1oBT
        2. Ao Único (feat. Bené Gomes) — Ministério Koinonya de Louvor
           Tom: C · BPM: 106
           Vídeo: https://youtu.be/tRcTodrN3_k
           Cifra: https://www.cifraclub.com.br/ministerio-koinonya-de-louvor/ao-unico
        3. Alem do rio azul — ICD MUSIC
           Tom: C · BPM: 140
           Vídeo: https://youtu.be/w8tYRc5AIlg

        Escala criada no LouveApp · https://louveapp.com.br
        TXT;

        $schedule = (new ParseScheduleService())->execute($text);

        $this->assertSame('Culto de domingo', $schedule['title']);
        $this->assertSame('2026-10-18', $schedule['event_date']);
        $this->assertSame(['Cristo', 'Ao Único', 'Alem do rio azul'], array_column($schedule['songs'], 'title'));
        $this->assertSame(['Som Do Reino', 'Ministério Koinonya de Louvor', 'ICD MUSIC'], array_column($schedule['songs'], 'artist'));
        $this->assertSame(['G', 'C', 'C'], array_column($schedule['songs'], 'key'));
        $this->assertSame([120.0, 106.0, 140.0], array_column($schedule['songs'], 'bpm'));
        $this->assertSame('https://www.cifraclub.com.br/som-do-reino/cristo/', $schedule['songs'][0]['chord_sheet_url']);
        $this->assertNull($schedule['songs'][2]['chord_sheet_url']);
        $this->assertSame('https://youtu.be/w8tYRc5AIlg', $schedule['songs'][2]['video']);
    }
}
