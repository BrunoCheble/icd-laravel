<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSongsService
{
    // A section start stored in the structure, e.g. "start": "1:05".
    private const TIMED_SECTION = '"start": *"[0-9]';

    // Blocks of the chord map with a start time.
    private const TIMED_COUNT = "(SELECT COUNT(*) FROM JSON_TABLE(songs.structure, '$[*]' COLUMNS (start VARCHAR(20) PATH '$.start')) AS sections"
        . " WHERE sections.start IS NOT NULL AND sections.start <> '')";

    // Blocks of the chord map that can have a start time ("back to the start" markers cannot).
    private const BLOCK_COUNT = "(SELECT COUNT(*) FROM JSON_TABLE(songs.structure, '$[*]' COLUMNS (jump VARCHAR(20) PATH '$.jump')) AS blocks"
        . " WHERE blocks.jump IS NULL OR blocks.jump <> 'start')";

    // Reviewed: every block of the chord map has a start time (see Song::isReviewed).
    private const REVIEWED = '(' . self::BLOCK_COUNT . ' > 0 AND ' . self::TIMED_COUNT . ' = ' . self::BLOCK_COUNT . ')';

    // Sort options (request value => SQL expressions, all in the chosen direction; fixed values, never user input).
    public const SORTS = [
        'title'    => ['title'],
        'artist'   => ['artist'],
        'key'      => ['musical_key'],
        'reviewed' => [self::REVIEWED],
        // Share of blocks with a start time, then how many.
        'times'    => [self::TIMED_COUNT . ' / NULLIF(' . self::BLOCK_COUNT . ', 0)', self::TIMED_COUNT],
        'youtube'  => ["(youtube_url IS NOT NULL AND youtube_url <> '')"],
        'lessons'  => ['COALESCE(JSON_LENGTH(video_lesson), 0)'],
        'updated'  => ['updated_at'],
    ];

    /**
     * Songs filtered by text (title or artist) and status: reviewed, times and youtube ("yes" | "no"),
     * sorted by one of SORTS (title by default), with title as tie-breaker.
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $sort = array_key_exists($filters['sort'] ?? '', self::SORTS) ? $filters['sort'] : 'title';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query = Song::query();
        foreach (self::SORTS[$sort] as $expression) {
            $query->orderByRaw("{$expression} {$direction}");
        }
        if ($sort !== 'title') {
            $query->orderBy('title');
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('artist', 'like', "%{$search}%"));
        }

        $this->filter($query, $filters['reviewed'] ?? null, fn ($q) => $q->whereRaw(self::REVIEWED), fn ($q) => $q->whereRaw('NOT ' . self::REVIEWED));
        $this->filter(
            $query,
            $filters['times'] ?? null,
            fn ($q) => $q->whereRaw('structure REGEXP ?', [self::TIMED_SECTION]),
            fn ($q) => $q->where(fn ($q) => $q->whereNull('structure')->orWhereRaw('structure NOT REGEXP ?', [self::TIMED_SECTION])),
        );
        $this->filter(
            $query,
            $filters['youtube'] ?? null,
            fn ($q) => $q->whereNotNull('youtube_url')->where('youtube_url', '!=', ''),
            fn ($q) => $q->where(fn ($q) => $q->whereNull('youtube_url')->orWhere('youtube_url', '')),
        );

        return $query->paginate();
    }

    private function filter($query, ?string $value, callable $yes, callable $no): void
    {
        match ($value) {
            'yes' => $yes($query),
            'no' => $no($query),
            default => null,
        };
    }
}
