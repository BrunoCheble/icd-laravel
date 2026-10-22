<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Song;
use App\Modules\Music\Support\NoChord;
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
    /** A chord, "?" (not known yet) or "N.C." (silence): what a chord sheet may have in brackets. */
    private static function isChordName(string $name): bool
    {
        return $name === '?' || NoChord::is($name) || preg_match('/^[A-G][#b]?/u', $name) === 1;
    }

    public static function clean(array $sections): array
    {
        return array_values(array_map(function (array $section) {
            // Without the invisible characters chord sites put in what is copied (zero-width spaces…).
            $lines = array_map(fn ($line) => rtrim(str_replace("\u{00A0}", ' ', preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}\r]/u', '', (string) $line))), $section['lines'] ?? []);
            // An empty note ("{c: }") says nothing.
            $lines = array_values(array_filter($lines, fn ($line) => ! preg_match('/^\s*\{\s*(?:c|comment)\s*:\s*\}\s*$/iu', $line)));
            // A block's name in brackets ("[Intro]", also before chords: "[Intro] [G#m] [A]") is a note, not a chord.
            $lines = collect($lines)->flatMap(function (string $line) {
                if (! preg_match('/^\s*\[([^\]]+)\](?:\s+(\S.*))?$/u', $line, $match) || self::isChordName(trim($match[1]))) {
                    return [$line];
                }

                return array_values(array_filter(['{c: ' . trim($match[1]) . '}', $match[2] ?? null], fn ($part) => $part !== null && $part !== ''));
            })->all();
            while ($lines !== [] && trim(end($lines)) === '') {
                array_pop($lines);
            }
            $name = strtoupper(trim(preg_replace('/\s+/', '_', (string) ($section['section'] ?? 'PART')))) ?: 'PART';

            return ['section' => $name, 'label' => $section['label'] ?? null, 'lines' => array_values($lines)];
        }, $sections));
    }
}
