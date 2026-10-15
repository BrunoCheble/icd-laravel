<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SaveSongChordSheetService
{
    /**
     * Saves the chord sheet made on the chord sheet page (sections of ChordPro lines). Refused (409) when the song
     * changed since the page read it. The chord map is not touched (it follows when the bars are made again).
     */
    public function execute(Song $song, array $sections, string $readAt): Song
    {
        if (! $song->updated_at || ! $song->updated_at->equalTo(Carbon::parse($readAt))) {
            throw new ConflictHttpException(__('The song was changed meanwhile. Load it again before saving.'));
        }
        $song->chord_sheet = array_merge($song->chord_sheet ?? [], ['sections' => self::clean($sections)]);
        $song->save();

        return $song;
    }

    /**
     * Sections as stored: a name, an optional label and their lines (trailing blank lines left out).
     */
    public static function clean(array $sections): array
    {
        return array_values(array_map(function (array $section) {
            $lines = array_map(fn ($line) => rtrim(str_replace("\r", '', (string) $line)), $section['lines'] ?? []);
            while ($lines !== [] && trim(end($lines)) === '') {
                array_pop($lines);
            }
            $name = strtoupper(trim(preg_replace('/\s+/', '_', (string) ($section['section'] ?? 'PART')))) ?: 'PART';

            return ['section' => $name, 'label' => $section['label'] ?? null, 'lines' => array_values($lines)];
        }, $sections));
    }
}
