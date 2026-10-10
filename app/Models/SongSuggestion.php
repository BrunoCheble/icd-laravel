<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chord corrections suggested on the public setlist page: `changes` is a list of { n (chord n of the song's map,
 * line breaks not counted), from, to (null: remove) }, and after a review also each change's `result` (applied,
 * rejected, outdated). Status: pending, approved (some or all changes applied) or rejected.
 */
class SongSuggestion extends Model
{
    protected $fillable = ['setlist_id', 'author', 'note', 'changes', 'status', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'changes'     => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function setlist(): BelongsTo
    {
        return $this->belongsTo(Setlist::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
