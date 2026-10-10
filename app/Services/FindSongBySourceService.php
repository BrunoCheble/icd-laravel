<?php

namespace App\Services;

use App\Models\Song;

/**
 * Songs by the address of their chord sheet (source), to avoid adding the same song twice.
 * Addresses are compared without what does not change the page: http/https, "www.", a trailing slash,
 * "?..." and "#...", and letter case.
 */
class FindSongBySourceService
{
    /**
     * The song (other than $exceptId) with the same source, or null.
     */
    public function execute(?string $url, ?int $exceptId = null): ?Song
    {
        $key = self::normalize($url);
        if ($key === '') {
            return null;
        }

        return Song::query()
            ->whereNotNull('source_url')
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->get(['id', 'title', 'artist', 'source_url'])
            ->first(fn (Song $song) => self::normalize($song->source_url) === $key);
    }

    /**
     * Sources of the songs (other than $exceptId), as normalized address => [id, title, url], for the song form.
     */
    public function all(?int $exceptId = null): array
    {
        return Song::query()
            ->whereNotNull('source_url')
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->get(['id', 'title', 'source_url'])
            ->mapWithKeys(fn (Song $song) => [self::normalize($song->source_url) => [
                'id'    => $song->id,
                'title' => $song->title,
                'url'   => route('songs.show', $song),
                'edit'  => route('songs.edit', $song),
            ]])
            ->except([''])
            ->all();
    }

    /**
     * Songs (other than $exceptId) by their title written plainly (no accents, case or punctuation), as
     * plain title => [id, title, artist, url, edit], so an imported chord sheet of a song already here is noticed.
     */
    public function titles(?int $exceptId = null): array
    {
        return Song::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->get(['id', 'title', 'artist'])
            ->mapWithKeys(fn (Song $song) => [self::plainTitle($song->title) => [
                'id'     => $song->id,
                'title'  => $song->title,
                'artist' => $song->artist,
                'url'    => route('songs.show', $song),
                'edit'   => route('songs.edit', $song),
            ]])
            ->except([''])
            ->all();
    }

    /**
     * "Bendito É o Rei!" -> "benditoeorei" (same rule as the song form's import).
     */
    public static function plainTitle(?string $title): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower(\Illuminate\Support\Str::ascii((string) $title)));
    }

    /**
     * "https://www.CifraClub.com.br/drops-ina/maravilhosa-graca/?tab=1#x" -> "cifraclub.com.br/drops-ina/maravilhosa-graca"
     */
    public static function normalize(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url(preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) ? $url : 'http://' . $url);
        if (! $parts || empty($parts['host'])) {
            return mb_strtolower($url);
        }

        $host = preg_replace('/^www\./', '', mb_strtolower($parts['host']));
        $path = rtrim(mb_strtolower($parts['path'] ?? ''), '/');

        return $host . $path;
    }
}
