{{-- Pages of a song, always at hand: $song (model or id) and $current (the page being shown) --}}
@php
    $tabs = [
        'show'     => [route('songs.show', $song), 'fa-eye', __('Show')],
        'edit'     => [route('songs.edit', $song), 'fa-pen', __('Edit')],
        'sheet'    => [route('songs.sheet.edit', $song), 'fa-align-left', __('Chord sheet')],
        'layout'   => [route('songs.layout.edit', $song), 'fa-layer-group', __('Layout')],
        'practice' => [route('songs.practice', $song), 'fa-graduation-cap', __('Practice')],
        'lessons'  => [route('songs.lessons.index', $song), 'fa-chalkboard-user', __('Lessons')],
        'history'  => [route('songs.history', $song), 'fa-clock-rotate-left', __('History')],
    ];
@endphp
<div class="song-tabs-wrap">
<nav class="song-tabs" aria-label="{{ __('Song pages') }}">
    @foreach ($tabs as $name => [$url, $icon, $label])
        <a href="{{ $url }}" @class(['song-tab', 'is-current' => $current === $name]) title="{{ $label }}" @if ($current === $name) aria-current="page" @endif>
            <i class="fa-solid {{ $icon }}"></i> <span>{{ $label }}</span>
        </a>
    @endforeach
</nav>
</div>

@once
    <style>
        .song-tabs { display: flex; gap: .25rem; margin-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; overflow-x: auto; scrollbar-width: none; }
        .song-tabs::-webkit-scrollbar { display: none; }
        .song-tab { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem .8rem; margin-bottom: -1px; border-bottom: 2px solid transparent; font-size: .875rem; font-weight: 600; color: #6b7280; white-space: nowrap; text-decoration: none; }
        .song-tab:hover { color: #111827; border-bottom-color: #d1d5db; }
        .song-tab.is-current { color: var(--brand-text); border-bottom-color: var(--brand); }
        /* By the room the tabs have (it changes with the side menu open or folded, not only with the screen): all
           names when they fit, else icons and the name of the page being shown, and only icons when very narrow */
        .song-tabs-wrap { container-type: inline-size; }
        @container (max-width: 46rem) {
            /* The tabs fill the whole width (wide areas to tap); only the page being shown keeps its name */
            .song-tabs { margin-bottom: .75rem; }
            .song-tab { flex: 1 1 0; justify-content: center; min-width: 2.75rem; padding: .7rem .25rem; font-size: 1rem; }
            .song-tab:not(.is-current) span { display: none; }
            .song-tab.is-current { flex: 0 1 auto; min-width: 0; padding: .7rem .6rem; }
            .song-tab.is-current span { min-width: 0; overflow: hidden; text-overflow: ellipsis; font-size: .875rem; }
        }
        /* Phones: a little less room for the icons, so the active name fits whole */
        @container (max-width: 26rem) {
            .song-tab { min-width: 2.4rem; padding: .7rem .1rem; }
            .song-tab.is-current { padding: .4rem .45rem; }
            /* The active one: its name only (no icon), on two lines when long ("Linhas e / blocos") */
            .song-tab.is-current i { display: none; }
            .song-tab.is-current span { font-size: .78rem; line-height: 1.1; white-space: normal; text-align: center; overflow: visible; }
        }
    </style>
@endonce
