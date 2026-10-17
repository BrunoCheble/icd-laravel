<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A song's chord map for one instrument: an independent copy of the song's map (same blocks and times) with its own
 * chords, in the key it was saved in (`musical_key`).
 */
class SongVersion extends Model
{
    // Offered when making a version; any other name can be typed.
    public const INSTRUMENTS = ['Teclado', 'Baixo', 'Guitarra', 'Violão'];

    protected $fillable = ['instrument', 'musical_key', 'structure'];

    protected function casts(): array
    {
        return ['structure' => 'array'];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    // Names of the same instrument (lowercase, without accents): a usual one is not offered when the songs already use
    // one of its names (e.g. "Bass" already there, "Baixo" is not added).
    public const SAME_INSTRUMENT = [
        ['baixo', 'bass', 'bass guitar', 'contrabaixo', 'baixo eletrico'],
        ['teclado', 'teclados', 'keyboard', 'keyboards', 'keys', 'piano'],
        ['guitarra', 'guitar', 'electric guitar', 'guitarra eletrica'],
        ['violao', 'acoustic guitar', 'acoustic', 'violao acustico'],
        ['bateria', 'drums', 'drum', 'drum kit'],
    ];

    /**
     * Names to offer: the ones already used in the songs (versions and lessons), then the usual instruments that are
     * not there yet under another name.
     */
    public static function instrumentNames(): array
    {
        $used = self::query()->distinct()->pluck('instrument');
        try {
            $used = $used->merge(SongLesson::query()->distinct()->pluck('instrument'));
        } catch (\Illuminate\Database\QueryException) {
            // The lessons table is not there yet (before its migration runs).
        }

        return self::withUsualInstruments($used->all());
    }

    /**
     * The names given (without repeats), and each usual instrument none of them names.
     */
    public static function withUsualInstruments(array $names): array
    {
        $names = collect($names)->map(fn ($name) => trim((string) $name))->filter()->unique(fn ($name) => self::instrumentKey($name))->values();
        $taken = $names->map(fn ($name) => self::sameInstrumentGroup($name))->all();
        $usual = collect(self::INSTRUMENTS)->reject(fn ($name) => in_array(self::sameInstrumentGroup($name), $taken, true));

        return $names->merge($usual)->sort(fn ($a, $b) => strcasecmp(self::instrumentKey($a), self::instrumentKey($b)))->values()->all();
    }

    // "Violão " -> "violao": compared without case, accents or extra spaces.
    private static function instrumentKey(string $name): string
    {
        return preg_replace('/\s+/', ' ', trim(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($name))));
    }

    // The group of names of the instrument (its index in SAME_INSTRUMENT), or the name itself when it is in none.
    private static function sameInstrumentGroup(string $name): string
    {
        $key = self::instrumentKey($name);
        foreach (self::SAME_INSTRUMENT as $index => $group) {
            if (in_array($key, $group, true)) {
                return "group:$index";
            }
        }

        return $key;
    }
}
