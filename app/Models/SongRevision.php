<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A song's chord map, chord sheet, tempo and key as they were before a change (see RecordSongRevisionService).
 */
class SongRevision extends Model
{
    // Revisions kept per song (the oldest go away).
    public const KEEP = 50;

    protected $fillable = ['user_id', 'source', 'structure', 'chord_sheet', 'musical_key', 'bpm'];

    protected function casts(): array
    {
        return [
            'structure'   => 'array',
            'chord_sheet' => 'array',
            'bpm'         => 'float',
        ];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
