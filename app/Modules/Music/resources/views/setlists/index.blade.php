<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Setlists') }}
        </h2>
    </x-slot>

    <style>
        .sl-head { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }
        .sl-head .grow { flex: 1 1 14rem; }
        .sl-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 38px; padding: 0 .8rem; border: 1px solid #d1d5db; border-radius: .5rem; background: #fff; color: #374151; font-size: .875rem; font-weight: 600; white-space: nowrap; cursor: pointer; text-decoration: none; }
        .sl-btn:hover { background: #f9fafb; }
        .sl-btn.is-primary { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .sl-btn.is-primary:hover { background: #4338ca; }
        .sl-search { display: flex; gap: .5rem; margin-top: 1rem; max-width: 36rem; }
        .sl-search input { flex: 1; min-width: 0; min-height: 38px; padding: 0 .75rem; border: 1px solid #d1d5db; border-radius: .5rem; font-size: .9rem; }

        .sl-list { display: grid; gap: .6rem; margin-top: 1.25rem; }
        .sl-card { border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; overflow: hidden; scroll-margin-top: 5rem; }
        .sl-card.is-next { border-color: #a5b4fc; box-shadow: 0 0 0 1px #a5b4fc; }
        .sl-card.is-past .sl-title, .sl-card.is-past .sl-date { color: #6b7280; }
        .sl-row { display: flex; align-items: center; gap: .75rem; padding: .7rem .9rem; }
        .sl-toggle { display: flex; align-items: center; gap: .75rem; flex: 1; min-width: 0; padding: 0; border: 0; background: none; text-align: left; cursor: pointer; }
        .sl-chevron { flex: none; width: 1.75rem; height: 1.75rem; display: grid; place-items: center; border-radius: 999px; background: #f3f4f6; color: #6b7280; transition: transform .15s; }
        .sl-card.is-open .sl-chevron { transform: rotate(90deg); background: #e0e7ff; color: #4f46e5; }
        .sl-date { flex: none; width: 6.5rem; display: grid; line-height: 1.2; color: #111827; }
        .sl-date strong { font-size: .95rem; }
        .sl-date small { font-size: .72rem; color: #6b7280; }
        .sl-main { flex: 1; min-width: 0; display: grid; gap: .1rem; }
        .sl-title { font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sl-meta { font-size: .8rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sl-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .05rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; letter-spacing: .03em; vertical-align: middle; }
        .sl-badge.is-next { background: #4f46e5; color: #fff; }
        .sl-count { flex: none; min-width: 2.2rem; padding: .15rem .55rem; border-radius: 999px; background: #f3f4f6; color: #374151; font-size: .8rem; font-weight: 700; text-align: center; }
        .sl-actions { display: flex; align-items: center; gap: .25rem; flex: none; }
        .sl-icon { width: 36px; height: 36px; display: grid; place-items: center; border: 1px solid transparent; border-radius: .5rem; background: none; color: #6b7280; cursor: pointer; text-decoration: none; }
        .sl-icon:hover { background: #f3f4f6; color: #111827; }
        .sl-icon.is-danger:hover { background: #fef2f2; color: #dc2626; }

        .sl-songs { margin: 0; padding: .25rem .9rem .8rem 3.4rem; list-style: none; border-top: 1px solid #f3f4f6; }
        .sl-song { display: flex; align-items: center; gap: .6rem; padding: .45rem 0; border-bottom: 1px dashed #f3f4f6; }
        .sl-song:last-child { border-bottom: 0; }
        .sl-n { flex: none; width: 1.5rem; color: #9ca3af; font-size: .8rem; font-weight: 700; text-align: right; }
        .sl-song-main { flex: 1; min-width: 0; display: grid; }
        .sl-song-main a { font-weight: 600; color: #4f46e5; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sl-song-main a:hover { text-decoration: underline; }
        .sl-song-main small { font-size: .78rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sl-key { flex: none; min-width: 2.4rem; padding: .1rem .45rem; border: 1px solid #4f46e5; border-radius: 999px; color: #4f46e5; font-size: .78rem; font-weight: 800; text-align: center; }
        .sl-key small { color: #9ca3af; font-weight: 600; }
        .sl-bpm { flex: none; font-size: .75rem; font-weight: 700; color: #6b7280; font-variant-numeric: tabular-nums; }
        .sl-empty { padding: .6rem 0; font-size: .85rem; color: #6b7280; }

        /* Phones: the date over the title, the actions under them */
        @media (max-width: 640px) {
            .sl-row { flex-wrap: wrap; gap: .5rem .6rem; }
            .sl-date { width: auto; }
            .sl-toggle { flex-basis: 100%; }
            .sl-actions { margin-left: auto; }
            .sl-songs { padding-left: .9rem; }
            .sl-title, .sl-song-main a { white-space: normal; }
        }
    </style>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('layouts.alert')

                <div class="sl-head">
                    <div class="grow">
                        <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Setlists') }}</h1>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Tap a setlist to see its songs.') }}</p>
                    </div>
                    <a href="{{ route('setlists.import') }}" class="sl-btn"><i class="fa-solid fa-paste"></i> {{ __('Import schedule') }}</a>
                    <a href="{{ route('setlists.create') }}" class="sl-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('Add new') }}</a>
                </div>

                <form method="GET" action="{{ route('setlists.index') }}" class="sl-search">
                    <input name="search" type="search" value="{{ $search }}" placeholder="{{ __('Search by title or minister...') }}" aria-label="{{ __('Search') }}">
                    <button type="submit" class="sl-btn">{{ __('Search') }}</button>
                    @if ($search)
                        <a href="{{ route('setlists.index') }}" class="sl-btn">{{ __('Clear') }}</a>
                    @endif
                </form>

                <div x-data="{
                        open: @js($open),
                        ids: @js($setlists->pluck('id')),
                        isOpen(id) { return this.open.includes(id); },
                        toggle(id) { this.open = this.isOpen(id) ? this.open.filter(item => item !== id) : [...this.open, id]; },
                        get allOpen() { return this.ids.length > 0 && this.ids.every(id => this.isOpen(id)); },
                        toggleAll() { this.open = this.allOpen ? [] : [...this.ids]; },
                    }" x-init="open.length && $nextTick(() => document.getElementById(`setlist-${open[0]}`)?.scrollIntoView({ block: 'start', behavior: 'smooth' }))">

                    @if ($setlists->isNotEmpty())
                        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                            <button type="button" class="sl-btn" @click="toggleAll()">
                                <i class="fa-solid" :class="allOpen ? 'fa-angles-up' : 'fa-angles-down'"></i>
                                <span x-text="allOpen ? @js(__('Collapse all')) : @js(__('Expand all'))"></span>
                            </button>
                        </div>
                    @endif

                    <div class="sl-list">
                        @forelse ($setlists as $setlist)
                            @php
                                $date = $setlist->event_date;
                                $isNext = $setlist->id === $nextId;
                                $isPast = $date && $date->lt(today());
                            @endphp
                            <article id="setlist-{{ $setlist->id }}" class="sl-card {{ $isNext ? 'is-next' : '' }} {{ $isPast ? 'is-past' : '' }}" :class="{ 'is-open': isOpen({{ $setlist->id }}) }">
                                <div class="sl-row">
                                    <button type="button" class="sl-toggle" @click="toggle({{ $setlist->id }})" :aria-expanded="isOpen({{ $setlist->id }})"
                                        :title="isOpen({{ $setlist->id }}) ? @js(__('Hide songs')) : @js(__('Show songs'))">
                                        <span class="sl-chevron"><i class="fa-solid fa-chevron-right"></i></span>
                                        <span class="sl-date">
                                            <strong>{{ $date?->format('d/m/Y') ?? '—' }}</strong>
                                            <small>{{ $date ? \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::lower($date->locale(app()->getLocale())->translatedFormat('l'))) : '' }}</small>
                                        </span>
                                        <span class="sl-main">
                                            <span class="sl-title">
                                                @if ($isNext)<span class="sl-badge is-next">{{ $date?->isToday() ? __('Today') : __('Next') }}</span>@endif
                                                {{ $setlist->title }}
                                            </span>
                                            <span class="sl-meta">{{ $setlist->ministers ?: '—' }}</span>
                                        </span>
                                        <span class="sl-count" title="{{ __('Songs') }}">{{ $setlist->songs_count }}</span>
                                    </button>
                                    <div class="sl-actions">
                                        <a href="{{ route('site.setlist.show', $setlist) }}" target="_blank" rel="noopener" class="sl-icon" title="{{ __('Public page') }}" aria-label="{{ __('Public page') }}"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                        @if ($setlist->songs_count)
                                            <a href="{{ route('setlists.practice', $setlist) }}" class="sl-icon" title="{{ __('Practice the setlist') }}" aria-label="{{ __('Practice the setlist') }}"><i class="fa-solid fa-graduation-cap"></i></a>
                                        @endif
                                        <a href="{{ route('setlists.edit', $setlist) }}" class="sl-icon" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><i class="fa-solid fa-pen"></i></a>
                                        <form action="{{ route('setlists.destroy', $setlist) }}" method="POST" onsubmit="return confirm(@js(__('Are you sure to delete?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sl-icon is-danger" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>

                                <ol class="sl-songs" x-show="isOpen({{ $setlist->id }})" x-cloak>
                                    @forelse ($setlist->songs as $song)
                                        @php
                                            $key = $song->pivot->musical_key ?? $song->musical_key;
                                            $changedKey = $song->pivot->musical_key && $song->musical_key && $song->pivot->musical_key !== $song->musical_key;
                                        @endphp
                                        <li class="sl-song">
                                            <span class="sl-n">{{ $loop->iteration }}</span>
                                            <span class="sl-song-main">
                                                <a href="{{ route('songs.show', $song) }}" title="{{ __('Open the song') }}">{{ $song->title }}</a>
                                                <small>{{ collect([$song->artist, $song->pivot->minister_name ? __('Minister') . ': ' . $song->pivot->minister_name : null])->filter()->join(' · ') ?: '—' }}</small>
                                            </span>
                                            @if ($song->pivot->bpm)
                                                <span class="sl-bpm" title="{{ __('Tempo set for the band in this setlist') }}">{{ rtrim(rtrim(number_format((float) $song->pivot->bpm, 1, '.', ''), '0'), '.') }} BPM</span>
                                            @endif
                                            @if ($key)
                                                <span class="sl-key" title="{{ $changedKey ? __('Original key') . ': ' . $song->musical_key : __('Key') }}">{{ $key }}@if ($changedKey) <small>({{ $song->musical_key }})</small>@endif</span>
                                            @endif
                                            <a href="{{ route('setlists.practice', [$setlist, $song]) }}" class="sl-icon" title="{{ __('Practice') }}" aria-label="{{ __('Practice') }}"><i class="fa-solid fa-graduation-cap"></i></a>
                                        </li>
                                    @empty
                                        <li class="sl-empty">{{ __('No songs in this setlist yet.') }} <a href="{{ route('setlists.edit', $setlist) }}" class="font-semibold text-indigo-600">{{ __('Edit') }}</a></li>
                                    @endforelse
                                </ol>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No setlists found.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-6">
                    {!! $setlists->withQueryString()->links() !!}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
