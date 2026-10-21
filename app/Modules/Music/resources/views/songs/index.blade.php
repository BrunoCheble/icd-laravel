<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Songs') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        [x-cloak] { display: none !important; }
        /* Large screens: a table; phones and tablets: a card per song */
        .songs-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .songs-table th { padding: .65rem .75rem; border-bottom: 1px solid var(--bd-e5e7eb); font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--tx-6b7280); text-align: left; white-space: nowrap; }
        .songs-table th a { color: inherit; text-decoration: none; }
        .songs-table th a:hover, .songs-table th a.is-sorted { color: var(--tx-111827); }
        .songs-table td { padding: .7rem .75rem; border-bottom: 1px solid var(--bd-f1f2f4); font-size: .875rem; color: var(--tx-4b5563); vertical-align: middle; }
        .songs-table tbody tr { transition: background .1s; }
        .songs-table tbody tr:hover { background: var(--bg-fafafa); }
        .songs-table tbody tr.is-selected { background: var(--bg-fef2f2); }
        .songs-table .song-title { display: block; font-weight: 700; color: var(--tx-111827); text-decoration: none; }
        .songs-table .song-title:hover { color: var(--brand-text); }
        .songs-table .song-artist { display: block; font-size: .8rem; color: var(--tx-6b7280); }
        .songs-table .is-check { width: 2.5rem; padding-right: 0; }
        .songs-media { display: flex; flex-wrap: wrap; gap: .3rem; }

        .song-cards { display: none; }
        .song-card { display: flex; align-items: flex-start; gap: .7rem; padding: .85rem .9rem; border-bottom: 1px solid var(--bd-f1f2f4); }
        .song-card:last-child { border-bottom: 0; }
        .song-card-main { flex: 1; min-width: 0; text-decoration: none; }
        .song-card-title { display: flex; align-items: center; gap: .5rem; font-weight: 700; color: var(--tx-111827); }
        .song-card-title span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .song-card-artist { margin-top: .1rem; font-size: .82rem; color: var(--tx-6b7280); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .song-card-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }
        @media (max-width: 1023px) {
            .songs-table { display: none; }
            .song-cards { display: block; }
            /* Deleting several songs together only on large screens */
            .songs-bulk { display: none !important; }
        }
        .songs-list { overflow: hidden; }
        .songs-empty { padding: 2.5rem 1rem; text-align: center; color: var(--tx-6b7280); }
        .songs-empty i { display: block; margin-bottom: .5rem; font-size: 1.6rem; color: var(--tx-d1d5db); }
        .songs-pages { padding: .75rem 1rem; border-top: 1px solid var(--bd-f1f2f4); }
    </style>

    @php
        $sort = $filters['sort'] ?? 'title';
        $direction = $filters['direction'] ?? 'asc';
        $hasFilters = collect($filters)->except(['sort', 'direction'])->filter()->isNotEmpty();
        // Header link: sorts by the column, reversing the direction when it is already the sort.
        $sortUrl = fn (string $column) => route('songs.index', array_merge(request()->except('page'), [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
        $sortArrow = fn (string $column) => new \Illuminate\Support\HtmlString(' <i class="fa-solid ' . ($sort === $column ? ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort" style="opacity: .35;') . '"></i>');
        $statusFilters = ['reviewed' => __('Reviewed'), 'times' => __('Times'), 'youtube' => 'YouTube', 'mp3' => 'MP3'];
        $sortOptions = [
            'title' => __('Title'), 'artist' => __('Artist'), 'key' => __('Key'), 'reviewed' => __('Reviewed'),
            'times' => __('Times'), 'youtube' => 'YouTube', 'mp3' => 'MP3', 'lessons' => __('Lessons'), 'updated' => __('Updated At'),
        ];
        // Active status filters, each one removable: "Reviewed: Yes".
        $activeFilters = collect($statusFilters)
            ->filter(fn ($label, $name) => in_array($filters[$name] ?? null, ['yes', 'no'], true))
            ->map(fn ($label, $name) => [
                'text' => $label . ': ' . ($filters[$name] === 'yes' ? __('Yes') : __('No')),
                'url' => route('songs.index', request()->except(['page', $name])),
            ]);
        $statusCount = $activeFilters->count();
        // Tags of a song: reviewed, block times, media.
        $tags = function ($song) {
            [$timed, $sections] = $song->timedSections();
            return [
                'reviewed' => $song->isReviewed(),
                'timed' => $timed,
                'sections' => $sections,
                'timesClass' => $sections && $timed === $sections ? 'is-ok' : ($timed ? 'is-part' : 'is-off'),
            ];
        };
        $actions = fn ($song) => [
            'title' => $song->title,
            'artist' => $song->artist,
            'show' => route('songs.show', $song),
            'edit' => route('songs.edit', $song),
            'layout' => route('songs.layout.edit', $song),
            'practice' => route('songs.practice', $song),
            'destroy' => route('songs.destroy', $song),
        ];
    @endphp

    <div class="m-page" x-data="songsIndex(@js(['all' => $allIds, 'page' => $songs->pluck('id')->all()]))" @keydown.escape.window="actions = null; filtersOpen = false">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Songs') }}</h1>
                <p>{{ trans_choice(':count song|:count songs', $songs->total(), ['count' => $songs->total()]) }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('songs.bookmarklet') }}" class="m-btn" title="{{ __('Import from a chord site') }}"><i class="fa-solid fa-file-import"></i> <span class="m-hide-sm">{{ __('Import from a chord site') }}</span></a>
                @can('songs.write')
                    <a href="{{ route('songs.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('Add new') }}</a>
                @endcan
            </div>
        </div>

        {{-- Search (always at hand) and the filters --}}
        <form method="GET" action="{{ route('songs.index') }}" class="m-toolbar">
            @foreach (request()->except(['search', 'page']) as $name => $value)
                @if (is_string($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
            @endforeach
            <label class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Title or artist') }}" aria-label="{{ __('Search') }}">
            </label>
            <button type="button" class="m-btn" @click="filtersOpen = true">
                <i class="fa-solid fa-sliders"></i> <span class="m-hide-sm">{{ __('Filter') }}</span>
                @if ($statusCount)<span class="m-count">{{ $statusCount }}</span>@endif
            </button>
        </form>

        @if ($activeFilters->isNotEmpty() || $hasFilters)
            <div class="m-chips">
                @if (($filters['search'] ?? '') !== '')
                    <a class="m-chip" href="{{ route('songs.index', request()->except(['page', 'search'])) }}" title="{{ __('Remove') }}">{{ __('Search') }}: "{{ $filters['search'] }}" <i class="fa-solid fa-xmark"></i></a>
                @endif
                @foreach ($activeFilters as $chip)
                    <a class="m-chip" href="{{ $chip['url'] }}" title="{{ __('Remove') }}">{{ $chip['text'] }} <i class="fa-solid fa-xmark"></i></a>
                @endforeach
                <a href="{{ route('songs.index', ['sort' => $sort, 'direction' => $direction]) }}" class="m-link">{{ __('Clear Filters') }}</a>
            </div>
        @endif

        {{-- Several songs selected (on any page): deleted together --}}
        <div class="m-card mt-3 songs-bulk" style="display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; padding: .7rem .9rem; border-color: var(--bd-fecaca); background: var(--bg-fef2f2);" x-show="selected.length" x-cloak>
            <strong class="text-sm" x-text="@js(__(':count selected')).replace(':count', selected.length)"></strong>
            <button type="button" class="m-link" x-show="selected.length < all.length" @click="selectAll()" x-text="@js(__('Select all :count songs')).replace(':count', all.length)"></button>
            <button type="button" class="m-link" style="color: var(--tx-6b7280);" @click="clear()">{{ __('Clear selection') }}</button>
            <span style="flex: 1;"></span>
            <template x-if="!bulkConfirm">
                <button type="button" class="m-btn is-danger" @click="bulkConfirm = true"><i class="fa-solid fa-trash"></i> {{ __('Delete selected') }}</button>
            </template>
            <template x-if="bulkConfirm">
                <form method="POST" action="{{ route('songs.bulk-destroy') }}" style="display: flex; flex-wrap: wrap; align-items: center; gap: .5rem;" @submit="forget()">
                    @csrf
                    @method('DELETE')
                    <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <span class="text-sm text-red-800" x-text="@js(__('Delete :count songs? Their versions, suggestions, history and places in setlists go too. This cannot be undone.')).replace(':count', selected.length)"></span>
                    <button type="submit" class="m-btn is-danger">{{ __('Yes, delete') }}</button>
                    <button type="button" class="m-btn is-ghost" @click="bulkConfirm = false">{{ __('Cancel') }}</button>
                </form>
            </template>
        </div>

        <div class="m-card songs-list mt-3">
            @if ($songs->isEmpty())
                <div class="songs-empty"><i class="fa-solid fa-music"></i>{{ $hasFilters ? __('No songs match these filters.') : __('No songs yet.') }}</div>
            @else
                <table class="songs-table">
                    <thead>
                        <tr>
                            <th class="is-check"><input type="checkbox" class="rounded border-gray-300" :checked="pageSelected()" @change="togglePage($event.target.checked)" aria-label="{{ __('Select the songs of this page') }}"></th>
                            <th><a href="{{ $sortUrl('title') }}" @class(['is-sorted' => $sort === 'title'])>{{ __('Title') }}{{ $sortArrow('title') }}</a></th>
                            <th><a href="{{ $sortUrl('key') }}" @class(['is-sorted' => $sort === 'key'])>{{ __('Key') }}{{ $sortArrow('key') }}</a></th>
                            <th><a href="{{ $sortUrl('reviewed') }}" @class(['is-sorted' => $sort === 'reviewed'])>{{ __('Reviewed') }}{{ $sortArrow('reviewed') }}</a></th>
                            <th><a href="{{ $sortUrl('times') }}" @class(['is-sorted' => $sort === 'times'])>{{ __('Times') }}{{ $sortArrow('times') }}</a></th>
                            <th>{{ __('Media') }}</th>
                            <th><a href="{{ $sortUrl('updated') }}" @class(['is-sorted' => $sort === 'updated'])>{{ __('Updated At') }}{{ $sortArrow('updated') }}</a></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($songs as $song)
                            @php $tag = $tags($song); @endphp
                            <tr :class="{ 'is-selected': selected.includes({{ $song->id }}) }">
                                <td class="is-check"><input type="checkbox" class="rounded border-gray-300" :checked="selected.includes({{ $song->id }})" @change="toggle({{ $song->id }})" aria-label="{{ __('Select') }} {{ $song->title }}"></td>
                                <td>
                                    <a href="{{ route('songs.show', $song) }}" class="song-title">{{ $song->title }}</a>
                                    <span class="song-artist">{{ $song->artist ?: '—' }}</span>
                                </td>
                                <td>@if ($song->musical_key)<span class="m-tag is-key">{{ $song->musical_key }}</span>@else<span class="m-tag is-off">—</span>@endif</td>
                                <td>
                                    @if ($tag['reviewed'])
                                        <span class="m-tag is-ok" title="{{ __('Saved as reviewed on :date', ['date' => $song->reviewed_at->format('d/m/Y')]) }}"><i class="fa-solid fa-check"></i> {{ __('Yes') }}</span>
                                    @else
                                        <span class="m-tag is-off">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('songs.layout.edit', ['song' => $song, 'mode' => 'time']) }}" class="m-tag {{ $tag['timesClass'] }}"
                                        title="{{ __(':timed of :total blocks have a start time', ['timed' => $tag['timed'], 'total' => $tag['sections']]) }}">{{ $tag['sections'] ? "{$tag['timed']}/{$tag['sections']}" : '—' }}</a>
                                </td>
                                <td>
                                    <div class="songs-media">
                                        @if ($song->youtube_url)<a href="{{ $song->youtube_url }}" target="_blank" rel="noopener" class="m-tag is-media" title="YouTube"><i class="fa-brands fa-youtube"></i></a>@endif
                                        @if ($song->audio_path)<span class="m-tag is-media" title="MP3: {{ $song->audio_path }}"><i class="fa-solid fa-headphones"></i></span>@endif
                                        @if ($song->lessons_count)<span class="m-tag is-media" title="{{ __('Lessons') }}"><i class="fa-solid fa-chalkboard-user"></i> {{ $song->lessons_count }}</span>@endif
                                        @if (! $song->youtube_url && ! $song->audio_path && ! $song->lessons_count)<span class="m-tag is-off">—</span>@endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">{{ $song->updated_at?->format('d/m/Y H:i') }}</td>
                                <td style="text-align: right;">
                                    <button type="button" class="m-btn is-ghost is-icon" aria-label="{{ __('Actions') }}" @click="actions = @js($actions($song)); confirmDelete = false"><i class="fa-solid fa-ellipsis"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Phones and tablets --}}
                {{-- Phones and tablets: no boxes to select songs for deleting together (no deleting by a slip of the
                     finger); one song is deleted from its menu, which asks first --}}
                <div class="song-cards">
                    @foreach ($songs as $song)
                        @php $tag = $tags($song); @endphp
                        <div class="song-card">
                            <a href="{{ route('songs.show', $song) }}" class="song-card-main">
                                <div class="song-card-title"><span>{{ $song->title }}</span>@if ($song->musical_key)<span class="m-tag is-key">{{ $song->musical_key }}</span>@endif</div>
                                <div class="song-card-artist">{{ $song->artist ?: '—' }}</div>
                                <div class="song-card-tags">
                                    @if ($tag['reviewed'])<span class="m-tag is-ok"><i class="fa-solid fa-check"></i> {{ __('Reviewed') }}</span>@endif
                                    @if ($tag['sections'])<span class="m-tag {{ $tag['timesClass'] }}" title="{{ __('Times') }}"><i class="fa-regular fa-clock"></i> {{ $tag['timed'] }}/{{ $tag['sections'] }}</span>@endif
                                    @if ($song->youtube_url)<span class="m-tag is-media"><i class="fa-brands fa-youtube"></i></span>@endif
                                    @if ($song->audio_path)<span class="m-tag is-media"><i class="fa-solid fa-headphones"></i></span>@endif
                                    @if ($song->lessons_count)<span class="m-tag is-media"><i class="fa-solid fa-chalkboard-user"></i> {{ $song->lessons_count }}</span>@endif
                                </div>
                            </a>
                            <button type="button" class="m-btn is-ghost is-icon" aria-label="{{ __('Actions') }}" @click="actions = @js($actions($song)); confirmDelete = false"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        </div>
                    @endforeach
                </div>

                @if ($songs->hasPages())
                    <div class="songs-pages">{!! $songs->withQueryString()->links() !!}</div>
                @endif
            @endif
        </div>

        {{-- Actions of the song picked in the list --}}
        <div class="m-sheet" x-show="actions" x-cloak role="dialog" aria-modal="true" :aria-label="actions?.title">
            <div class="m-sheet-overlay" @click="actions = null"></div>
            <div class="m-sheet-box" x-show="actions" x-transition.opacity>
                <div class="m-sheet-head">
                    <div>
                        <h2 x-text="actions?.title"></h2>
                        <p x-text="actions?.artist"></p>
                    </div>
                    <button type="button" class="m-btn is-ghost is-icon" @click="actions = null" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="m-menu" x-show="!confirmDelete">
                    <a :href="actions?.show"><i class="fa-solid fa-eye"></i> {{ __('Show') }}</a>
                    <a :href="actions?.edit"><i class="fa-solid fa-pen"></i> {{ __('Edit') }}</a>
                    <a :href="actions?.layout"><i class="fa-solid fa-layer-group"></i> {{ __('Layout') }}</a>
                    <a :href="actions?.practice"><i class="fa-solid fa-graduation-cap"></i> {{ __('Practice') }}</a>
                    <button type="button" class="is-danger" @click="confirmDelete = true"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                </div>
                <form method="POST" :action="actions?.destroy" x-show="confirmDelete">
                    @csrf
                    @method('DELETE')
                    <p class="text-sm text-gray-700">{{ __('Are you sure to delete?') }}</p>
                    <div class="m-sheet-foot">
                        <button type="button" class="m-btn" @click="confirmDelete = false">{{ __('Cancel') }}</button>
                        <button type="submit" class="m-btn is-danger">{{ __('Delete') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Filters and order --}}
        <div class="m-sheet" x-show="filtersOpen" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('Filter') }}">
            <div class="m-sheet-overlay" @click="filtersOpen = false"></div>
            <form method="GET" action="{{ route('songs.index') }}" class="m-sheet-box" x-show="filtersOpen" x-transition.opacity>
                <div class="m-sheet-head">
                    <div>
                        <h2>{{ __('Filter') }}</h2>
                        <p>{{ __('Show only the songs you need, in the order you want.') }}</p>
                    </div>
                    <button type="button" class="m-btn is-ghost is-icon" @click="filtersOpen = false" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="m-grid">
                    <div class="m-field">
                        <label class="m-label" for="filter_search">{{ __('Search') }}</label>
                        <input id="filter_search" name="search" type="search" class="m-input" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Title or artist') }}">
                    </div>
                    <div class="m-grid cols-2">
                        @foreach ($statusFilters as $name => $label)
                            <div class="m-field">
                                <span class="m-label">{{ $label }}</span>
                                <div class="m-seg" role="radiogroup" aria-label="{{ $label }}">
                                    @foreach (['' => __('All'), 'yes' => __('Yes'), 'no' => __('No')] as $value => $text)
                                        <label><input type="radio" name="{{ $name }}" value="{{ $value }}" @checked(($filters[$name] ?? '') === $value)><span>{{ $text }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="m-grid cols-2">
                        <div class="m-field">
                            <label class="m-label" for="filter_sort">{{ __('Sort by') }}</label>
                            <select id="filter_sort" name="sort" class="m-input">
                                @foreach ($sortOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="m-field">
                            <span class="m-label">{{ __('Order by') }}</span>
                            <div class="m-seg">
                                <label><input type="radio" name="direction" value="asc" @checked($direction === 'asc')><span><i class="fa-solid fa-arrow-up-short-wide"></i> {{ __('Ascending') }}</span></label>
                                <label><input type="radio" name="direction" value="desc" @checked($direction === 'desc')><span><i class="fa-solid fa-arrow-down-wide-short"></i> {{ __('Descending') }}</span></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="m-sheet-foot">
                    <a href="{{ route('songs.index') }}" class="m-btn is-ghost">{{ __('Clear Filters') }}</a>
                    <button type="submit" class="m-btn is-primary">{{ __('Apply Filters') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Songs list: the actions menu, and songs selected for deleting together (kept while moving between pages).
        function songsIndex({ all, page }) {
            const KEY = 'songs-selected';
            let saved = [];
            try { saved = JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (error) {}
            return {
                actions: null,
                confirmDelete: false,
                filtersOpen: false,
                bulkConfirm: false,
                all,
                page,
                selected: saved.filter(id => all.includes(id)),
                remember() {
                    this.bulkConfirm = false;
                    try { sessionStorage.setItem(KEY, JSON.stringify(this.selected)); } catch (error) {}
                },
                toggle(id) {
                    this.selected = this.selected.includes(id) ? this.selected.filter(item => item !== id) : [...this.selected, id];
                    this.remember();
                },
                pageSelected() { return this.page.length > 0 && this.page.every(id => this.selected.includes(id)); },
                togglePage(on) {
                    this.selected = on ? [...new Set([...this.selected, ...this.page])] : this.selected.filter(id => !this.page.includes(id));
                    this.remember();
                },
                selectAll() { this.selected = [...this.all]; this.remember(); },
                clear() { this.selected = []; this.remember(); },
                forget() { try { sessionStorage.removeItem(KEY); } catch (error) {} },
            };
        }
    </script>
</x-app-layout>
