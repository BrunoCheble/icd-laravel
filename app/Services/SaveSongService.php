<?php

namespace App\Services;

use App\Models\Song;

class SaveSongService
{
    public function execute(array $data, ?Song $song = null): Song
    {
        $values = [
            'title'        => $data['title'],
            'artist'       => $data['artist'],
            'musical_key'  => $data['musical_key'] ?? null,
            'youtube_url'  => $data['youtube_url'] ?? null,
            'source_url'   => $data['source_url'] ?? null,
            'video_lesson' => $this->videoLessons($data['video_lesson'] ?? []),
            // Decoded as entered (only chords spelled E#, B#, Cb, Fb or F## get their simple name).
            'structure'    => $this->structure(json_decode($data['structure'])),
            'chord_sheet'  => isset($data['chord_sheet']) ? $this->chordSheet(json_decode($data['chord_sheet'], true)) : null,
        ];

        return $song ? tap($song)->update($values) : Song::create($values);
    }

    private function structure(mixed $structure): mixed
    {
        if (! is_array($structure)) {
            return $structure;
        }

        foreach ($structure as $section) {
            if (is_object($section) && is_array($section->chords ?? null)) {
                $section->chords = array_map(fn ($chord) => is_string($chord) ? NormalizeChordSpellingService::chord($chord) : $chord, $section->chords);
            }
        }

        return $structure;
    }

    private function chordSheet(mixed $sheet): mixed
    {
        if (! is_array($sheet['sections'] ?? null)) {
            return $sheet;
        }

        foreach ($sheet['sections'] as $index => $section) {
            if (is_array($section['lines'] ?? null)) {
                $sheet['sections'][$index]['lines'] = array_map(
                    fn ($line) => is_string($line) && ! str_starts_with(ltrim($line), '{') ? NormalizeChordSpellingService::line($line) : $line,
                    $section['lines'],
                );
            }
        }

        return $sheet;
    }

    private function videoLessons(array $lessons): ?array
    {
        $lessons = collect($lessons)
            ->map(fn (array $lesson) => [
                'instrument' => $lesson['instrument'],
                'title'      => $lesson['title'],
                'link'       => $lesson['link'],
            ])
            ->values()
            ->all();

        return $lessons ?: null;
    }
}
