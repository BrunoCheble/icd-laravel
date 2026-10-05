<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $perPage = 20;

    protected $fillable = [
        'title',
        'artist',
        'musical_key',
        'youtube_url',
        'source_url',
        'video_lesson',
        'structure',
        'chord_sheet',
    ];

    protected function casts(): array
    {
        return [
            'video_lesson' => 'array',
            'structure' => 'object',
            'chord_sheet' => 'array',
        ];
    }

    /**
     * Lyrics of a structure section as text: accepts a string or a list of lines; empty when absent.
     */
    public static function sectionLyrics(mixed $section): string
    {
        $lyrics = is_object($section) ? ($section->lyrics ?? null) : null;

        if (is_array($lyrics)) {
            $lyrics = implode("\n", array_map('strval', $lyrics));
        }

        return is_string($lyrics) ? trim($lyrics) : '';
    }

    /**
     * Sections that have lyrics, in order, as [name, lyrics] for reading.
     */
    public function lyricsBlocks(): array
    {
        return collect(is_array($this->structure) ? $this->structure : [])
            ->map(fn ($section) => [
                'name'   => is_object($section) ? str_replace('_', ' ', (string) ($section->section ?? '')) : '',
                'lyrics' => self::sectionLyrics($section),
            ])
            ->filter(fn (array $block) => $block['lyrics'] !== '')
            ->values()
            ->all();
    }

    /**
     * Chord sheet as pretty-printed JSON text for editing; empty when the song has none.
     */
    public function chordSheetAsJson(): string
    {
        return $this->chord_sheet
            ? json_encode($this->chord_sheet, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
    }

    /**
     * Returns the structure as pretty-printed JSON text for editing.
     * Uses the raw database value so empty objects ({}) are not turned into arrays ([]).
     */
    public function structureAsJson(): string
    {
        $raw = $this->getRawOriginal('structure');

        if ($raw === null) {
            return '';
        }

        return json_encode(json_decode($raw), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
