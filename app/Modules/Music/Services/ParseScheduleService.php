<?php

namespace App\Modules\Music\Services;

use Illuminate\Support\Str;

class ParseScheduleService
{
    private const MONTHS = ['janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'abril' => 4, 'maio' => 5, 'junho' => 6, 'julho' => 7,
        'agosto' => 8, 'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12];

    /**
     * Reads a schedule shared as text (as LouveApp writes it): the event's title (first line), its date
     * ("Domingo, 18 de outubro de 2026 · 08:00"), and each song ("1. Title — Artist", then "Tom: G · BPM: 120",
     * "Vídeo: …", "Cifra: …"). Returns ['title', 'event_date' (Y-m-d or null), 'songs' => [title, artist, key, bpm,
     * video, chord_sheet_url]].
     */
    public function execute(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn ($line) => $line !== ''));
        $title = $lines[0] ?? '';
        $date = null;
        $songs = [];
        $current = null;

        foreach ($lines as $line) {
            $plain = Str::lower(Str::ascii($line));
            if ($date === null && preg_match('/(\d{1,2}) de ([a-z]+) de (\d{4})/', $plain, $match) && isset(self::MONTHS[$match[2]])) {
                $date = sprintf('%04d-%02d-%02d', $match[3], self::MONTHS[$match[2]], $match[1]);
                continue;
            }
            // "1. Cristo (Ao Vivo) — Som Do Reino"
            if (preg_match('/^\d+\s*[.)]\s*(.+?)\s+[—–-]\s+(.+)$/u', $line, $match)) {
                $songs[] = ['title' => self::cleanTitle($match[1]), 'artist' => trim($match[2]), 'key' => null, 'bpm' => null, 'video' => null, 'chord_sheet_url' => null];
                $current = array_key_last($songs);
                continue;
            }
            if ($current === null) {
                continue;
            }
            if (preg_match('/^tom\s*:\s*([A-G][#b]?m?)/i', Str::ascii($line), $match)) {
                $songs[$current]['key'] = ucfirst($match[1]);
            }
            if (preg_match('/bpm\s*:\s*(\d+(?:[.,]\d+)?)/i', $line, $match)) {
                $songs[$current]['bpm'] = (float) str_replace(',', '.', $match[1]);
            }
            if (preg_match('/^v[ií]deo\s*:\s*(\S+)/iu', $line, $match)) {
                $songs[$current]['video'] = $match[1];
            }
            if (preg_match('/^cifra\s*:\s*(\S+)/iu', $line, $match)) {
                $songs[$current]['chord_sheet_url'] = $match[1];
            }
        }

        return ['title' => $title, 'event_date' => $date, 'songs' => $songs];
    }

    /**
     * "Cristo (Ao Vivo)" -> "Cristo"; "Ao Único (feat. Bené Gomes)" -> "Ao Único" (recording notes, not the song's name).
     */
    public static function cleanTitle(string $title): string
    {
        $clean = preg_replace('/\s*[\(\[](?:ao vivo|live|ac[uú]stic[oa]|feat\.?|ft\.?|part\.?|participa[cç][aã]o)[^\)\]]*[\)\]]/iu', '', $title);

        return trim($clean) !== '' ? trim($clean) : trim($title);
    }
}
