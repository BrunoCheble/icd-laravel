<?php

namespace App\Models;

use App\Services\SaveSongAudioService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    protected $perPage = 20;

    protected $fillable = [
        'title',
        'artist',
        'musical_key',
        'bpm',
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
            'reviewed_at' => 'datetime',
            'bpm'         => 'float',
        ];
    }

    /**
     * Setlists with this song, with its key, position and minister in each one.
     */
    public function setlists(): BelongsToMany
    {
        return $this->belongsToMany(Setlist::class, 'setlist_songs')
            ->withPivot('musical_key', 'position', 'minister_name');
    }

    /**
     * Chord corrections suggested on the public setlist page.
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(SongSuggestion::class);
    }

    /**
     * Earlier versions of the chord map, chord sheet, key and tempo (newest first).
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(SongRevision::class)->latest('id');
    }

    /**
     * Chord maps of this song for instruments (see SongVersion), by instrument name.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(SongVersion::class)->orderBy('instrument');
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
     * Whether a chord map block is a "back to the start" marker (no chords, no time; shown between blocks).
     */
    public static function isReturnMarker(mixed $block): bool
    {
        $jump = is_object($block) ? ($block->jump ?? null) : (is_array($block) ? ($block['jump'] ?? null) : null);

        return $jump === 'start';
    }

    /**
     * Section times status: [sections with a start, all sections] of the chord map.
     */
    public function timedSections(): array
    {
        $sections = collect(is_array($this->structure) ? $this->structure : [])
            ->filter(fn ($section) => is_object($section) && ! self::isReturnMarker($section));

        return [
            $sections->filter(fn ($section) => ($section->start ?? '') !== '' && $section->start !== null)->count(),
            $sections->count(),
        ];
    }

    /**
     * Whether the chord map can be shown as a grid of bars: every block with chords has one duration per chord
     * (saved by the study app, or created on the layout page).
     */
    public function hasBars(): bool
    {
        $blocks = array_filter(
            json_decode(json_encode($this->structure ?? []), true) ?? [],
            fn ($block) => is_array($block) && ($block['jump'] ?? null) !== 'start',
        );
        $withChords = array_filter($blocks, fn ($block) => \App\Services\UpdateSongLayoutService::chordSequence([$block]) !== []);

        return $withChords !== [] && collect($withChords)->every(fn ($block) => is_array($block['durations'] ?? null)
            && count($block['durations']) === count(\App\Services\UpdateSongLayoutService::chordSequence([$block])));
    }

    /**
     * Address of the song's audio file (played instead of the YouTube video), or null.
     */
    public function audioUrl(): ?string
    {
        return $this->audio_path && is_file(public_path(SaveSongAudioService::FOLDER . '/' . $this->audio_path))
            ? asset(SaveSongAudioService::FOLDER . '/' . $this->audio_path)
            : null;
    }

    /**
     * Reviewed: every block of the chord map has a start time (section times are only marked once the song was
     * checked against the video).
     */
    public function isReviewed(): bool
    {
        [$timed, $total] = $this->timedSections();

        return $total > 0 && $timed === $total;
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
