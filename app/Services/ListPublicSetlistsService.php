<?php

namespace App\Services;

use App\Models\Setlist;
use Illuminate\Support\Collection;

class ListPublicSetlistsService
{
    /**
     * Options for the setlist date selector, newest event first.
     */
    public function execute(): Collection
    {
        return Setlist::query()
            ->select('id', 'title', 'event_date')
            ->orderByRaw('event_date IS NULL')
            ->orderByDesc('event_date')
            ->orderBy('title')
            ->get()
            ->map(fn (Setlist $setlist) => [
                'id'    => $setlist->id,
                'label' => $setlist->event_date
                    ? $setlist->event_date->format('d/m/Y') . ' · ' . $setlist->title
                    : $setlist->title,
            ]);
    }

    /**
     * Setlist opened by default: the next upcoming event, otherwise the most recent one.
     */
    public function default(): ?Setlist
    {
        return Setlist::whereDate('event_date', '>=', today())->orderBy('event_date')->first()
            ?? Setlist::whereNotNull('event_date')->orderByDesc('event_date')->first()
            ?? Setlist::latest('updated_at')->first();
    }
}
