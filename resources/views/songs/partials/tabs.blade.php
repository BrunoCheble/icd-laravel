{{-- Pages of a song, always at hand: $song (model or id) and $current (the page being shown) --}}
@php
    $tabs = [
        'show'     => [route('songs.show', $song), 'fa-eye', __('Show')],
        'edit'     => [route('songs.edit', $song), 'fa-pen', __('Edit')],
        'timing'   => [route('songs.timing.edit', $song), 'fa-stopwatch', __('Section times')],
        'layout'   => [route('songs.layout.edit', $song), 'fa-layer-group', __('Layout')],
        'practice' => [route('songs.practice', $song), 'fa-graduation-cap', __('Practice')],
    ];
@endphp
<nav class="song-tabs" aria-label="{{ __('Song pages') }}">
    @foreach ($tabs as $name => [$url, $icon, $label])
        <a href="{{ $url }}" @class(['song-tab', 'is-current' => $current === $name]) title="{{ $label }}" @if ($current === $name) aria-current="page" @endif>
            <i class="fa-solid {{ $icon }}"></i> <span>{{ $label }}</span>
        </a>
    @endforeach
</nav>

@once
    <style>
        .song-tabs { display: flex; gap: .25rem; margin-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; overflow-x: auto; scrollbar-width: none; }
        .song-tabs::-webkit-scrollbar { display: none; }
        .song-tab { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem .8rem; margin-bottom: -1px; border-bottom: 2px solid transparent; font-size: .875rem; font-weight: 600; color: #6b7280; white-space: nowrap; text-decoration: none; }
        .song-tab:hover { color: #111827; border-bottom-color: #d1d5db; }
        .song-tab.is-current { color: #4f46e5; border-bottom-color: #4f46e5; }
        /* Phones: icons only, so the five tabs fit */
        @media (max-width: 639px) {
            .song-tabs { justify-content: space-between; margin-bottom: .5rem; }
            .song-tab { padding: .6rem .9rem; font-size: 1rem; }
            .song-tab span { display: none; }
        }
    </style>
@endonce
