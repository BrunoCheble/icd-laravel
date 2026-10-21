<?php

namespace App\Modules\Music\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Setlist extends Model
{
    // The setlists table only has updated_at.
    const CREATED_AT = null;

    protected $perPage = 20;

    protected $fillable = [
        'title',
        'event_date',
    ];

    /** Its day went by (Lisbon time): the public repertoire only shows it (see EnsureSetlistNotPast). */
    public function isPast(): bool
    {
        return $this->event_date !== null && $this->event_date->toDateString() < now('Europe/Lisbon')->toDateString();
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function songs(): BelongsToMany
    {
        // Pivot holds the setlist-specific settings: key, order and minister of each song.
        return $this->belongsToMany(Song::class, 'setlist_songs')
            ->withPivot('musical_key', 'bpm', 'position', 'minister_name')
            ->orderByPivot('position');
    }
}
