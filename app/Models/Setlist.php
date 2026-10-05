<?php

namespace App\Models;

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
            ->withPivot('musical_key', 'position', 'minister_name')
            ->orderByPivot('position');
    }
}
