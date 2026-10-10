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

    /**
     * Names to offer: the usual instruments and the ones already used in any song.
     */
    public static function instrumentNames(): array
    {
        return collect(self::INSTRUMENTS)
            ->merge(self::query()->distinct()->orderBy('instrument')->pluck('instrument'))
            ->unique()
            ->values()
            ->all();
    }
}
