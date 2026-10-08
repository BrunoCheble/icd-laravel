<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $song->title }}
        </h2>
    </x-slot>

    <script src="{{ asset('js/chord-transposer.js') }}?v={{ filemtime(public_path('js/chord-transposer.js')) }}"></script>
    <script src="{{ asset('js/chord-lines.js') }}?v={{ filemtime(public_path('js/chord-lines.js')) }}"></script>

    <style>
        .song-overview { --type-intro: #0369a1; --type-verse: #047857; --type-prechorus: #b45309; --type-chorus: #be123c; --type-bridge: #6d28d9; --type-instrumental: #4338ca; --type-ending: #475569; --type-other: #111827; }
        .overview-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: .35rem .75rem; }
        .overview-key { padding: .05rem .6rem; border: 1px solid #4f46e5; border-radius: 999px; color: #4f46e5; font-weight: 700; font-size: .9rem; }
        .overview-links { display: flex; flex-wrap: wrap; gap: .35rem 1rem; margin-top: .35rem; font-size: .85rem; }
        .overview-links a { color: #4f46e5; font-weight: 600; }
        .overview-title { margin: 1.5rem 0 .6rem; font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }

        /* What is done / missing: each card opens the page where it is done */
        .status-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        @media (min-width: 768px) { .status-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { .status-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
        .status-card { display: flex; flex-direction: column; gap: .2rem; padding: .7rem .8rem; border: 1px solid #e5e7eb; border-left: 4px solid #d1d5db; border-radius: .5rem; background: #fff; color: #111827; text-align: left; text-decoration: none; cursor: pointer; }
        .status-card:hover { background: #f9fafb; }
        .status-card.is-done { border-left-color: #16a34a; }
        .status-card.is-partial { border-left-color: #f59e0b; }
        .status-card .status-label { font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        .status-card .status-value { font-size: .95rem; font-weight: 700; }
        .status-card .status-action { font-size: .78rem; color: #4f46e5; font-weight: 600; }

        .setlist-row { display: flex; align-items: center; gap: .75rem; padding: .55rem 0; border-top: 1px solid #f3f4f6; }
        .setlist-row:first-child { border-top: 0; }
        .setlist-row .setlist-title { flex: 1; min-width: 0; }
        .setlist-row .setlist-title a { font-weight: 600; color: #111827; }
        .setlist-row .setlist-title small { display: block; color: #6b7280; }

        /* Chord map preview, as the public page splits and colors it */
        .preview-block { padding: .5rem 0 .6rem; border-top: 1px solid #f3f4f6; }
        .preview-name { font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--type-color, #4f46e5); }
        .preview-block { border-left: 3px solid var(--type-color, transparent); padding-left: .6rem; }
        .preview-name small { margin-left: .4rem; font-weight: 500; letter-spacing: 0; color: #9ca3af; }
        .preview-anchor { font-size: .85rem; font-style: italic; color: #6b7280; }
        .preview-line .is-passing { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .22em; }
        .preview-return { padding: .35rem .6rem; border-top: 1px solid #f3f4f6; font-size: .8rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        .preview-line { white-space: pre; display: flex; flex-wrap: wrap; gap: .1rem 1rem; font-weight: 700; color: var(--type-color, #111827); }
    </style>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg song-overview">
                @include('songs.partials.tabs', ['song' => $song, 'current' => 'show'])
                @include('layouts.alert')

                @php
                    $status = $overview['status'];
                    $timesState = $status['blocks'] && $status['timed'] === $status['blocks'] ? 'is-done' : ($status['timed'] ? 'is-partial' : '');
                @endphp

                <div class="overview-head">
                    <h1 class="text-lg font-semibold text-gray-900">{{ $song->title }}</h1>
                    @if ($song->musical_key)<span class="overview-key">{{ $song->musical_key }}</span>@endif
                    <span class="text-gray-500">{{ $song->artist }}</span>
                </div>
                <div class="overview-links">
                    @if ($song->youtube_url)<a href="{{ $song->youtube_url }}" target="_blank" rel="noopener"><i class="fa-brands fa-youtube"></i> YouTube</a>@endif
                    @if ($song->source_url)<a href="{{ $song->source_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> {{ __('Source') }}</a>@endif
                    <a href="{{ route('songs.index') }}"><i class="fa-solid fa-list"></i> {{ __('Songs') }}</a>
                </div>

                {{-- Status: what is done and what is missing --}}
                <h3 class="overview-title">{{ __('Status') }}</h3>
                <div class="status-grid">
                    {{-- Reviewed once every block has a start time --}}
                    <a href="{{ route('songs.layout.edit', ['song' => $song, 'mode' => 'time']) }}" @class(['status-card', 'is-done' => $status['reviewed']])>
                        <span class="status-label">{{ __('Reviewed') }}</span>
                        <span class="status-value">{{ $status['reviewed'] ? __('Yes') : __('No') }}</span>
                        <span class="status-action">{{ $status['reviewed'] ? __('Every block has a start time') : __('Mark the times of every block') }}</span>
                    </a>
                    <a href="{{ route('songs.layout.edit', ['song' => $song, 'mode' => 'time']) }}" class="status-card {{ $timesState }}">
                        <span class="status-label">{{ __('Times') }}</span>
                        <span class="status-value">{{ $status['blocks'] ? $status['timed'] . '/' . $status['blocks'] : '—' }}</span>
                        <span class="status-action">{{ __('Section times') }}</span>
                    </a>
                    <a href="{{ $song->youtube_url ? route('songs.layout.edit', ['song' => $song, 'mode' => 'time']) : route('songs.edit', $song) }}" @class(['status-card', 'is-done' => $status['youtube']])>
                        <span class="status-label">YouTube</span>
                        <span class="status-value">{{ $status['youtube'] ? __('Yes') : __('No') }}</span>
                        <span class="status-action">{{ $status['youtube'] ? __('Listen on YouTube') : __('Add the link') }}</span>
                    </a>
                    <a href="{{ $status['sheetSections'] ? route('songs.layout.edit', $song) : route('songs.edit', $song) }}" @class(['status-card', 'is-done' => $status['sheetSections']])>
                        <span class="status-label">{{ __('Chord sheet') }}</span>
                        <span class="status-value">{{ $status['sheetSections'] ? trans_choice(':count block|:count blocks', $status['sheetSections'], ['count' => $status['sheetSections']]) : __('No') }}</span>
                        <span class="status-action">{{ $status['sheetSections'] ? __('Layout') : __('Import the chord sheet') }}</span>
                    </a>
                    <a href="{{ route('songs.layout.edit', $song) }}" @class(['status-card', 'is-done' => $status['manualBlocks']])>
                        <span class="status-label">{{ __('Lines set by hand') }}</span>
                        <span class="status-value">{{ $status['blocks'] ? $status['manualBlocks'] . '/' . $status['blocks'] : '—' }}</span>
                        <span class="status-action">{{ __('Layout') }}</span>
                    </a>
                    <a href="{{ route('songs.edit', $song) }}" @class(['status-card', 'is-done' => $status['lessons']])>
                        <span class="status-label">{{ __('Video Lessons') }}</span>
                        <span class="status-value">{{ $status['lessons'] }}</span>
                        <span class="status-action">{{ __('Edit') }}</span>
                    </a>
                </div>

                {{-- Setlists with the song --}}
                <h3 class="overview-title">{{ __('Setlists') }}</h3>
                @forelse ($overview['setlists'] as $setlist)
                    <div class="setlist-row">
                        <span class="setlist-title">
                            <a href="{{ route('setlists.show', $setlist['id']) }}">{{ $setlist['title'] }}</a>
                            <small>{{ $setlist['date'] ?? '—' }} · {{ $setlist['position'] }}ª {{ __('song') }}@if ($setlist['minister']) · {{ __('Minister') }}: {{ $setlist['minister'] }}@endif</small>
                        </span>
                        @if ($setlist['key'])<span class="overview-key">{{ $setlist['key'] }}</span>@endif
                        <a href="{{ route('setlists.practice', [$setlist['id'], $song->id]) }}" class="text-sm font-semibold text-indigo-600" title="{{ __('Practice') }}"><i class="fa-solid fa-graduation-cap"></i><span class="ws-label"> {{ __('Practice') }}</span></a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('This song is not in any setlist yet.') }}</p>
                @endforelse

                {{-- Video lessons --}}
                @if (! empty($song->video_lesson))
                    <h3 class="overview-title">{{ __('Video Lessons') }}</h3>
                    @foreach ($song->video_lesson as $lesson)
                        <div class="py-1 text-sm">
                            <span class="font-semibold">{{ $instrumentOptions[$lesson['instrument']] ?? $lesson['instrument'] }}</span> —
                            <a href="{{ $lesson['link'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">{{ $lesson['title'] }}</a>
                        </div>
                    @endforeach
                @endif

                {{-- Chord map, as the musicians see it --}}
                <h3 class="overview-title">{{ __('Map') }}</h3>
                @if ($overview['blocks'] === [])
                    <p class="text-sm text-gray-500">{{ __('No structure available for this song.') }}</p>
                @else
                    <div x-data="songPreview(@js($overview['blocks']))">
                        <template x-for="(block, b) in blocks" :key="b">
                            <div>
                            <div class="preview-return" x-show="block.return"><i class="fa-solid fa-rotate-left"></i> {{ __('Back to the start') }}</div>
                            <div class="preview-block" x-show="!block.return" :style="`--type-color: var(--type-${blockType(block.name)})`">
                                <div class="preview-name"><span x-text="block.name"></span><small x-show="block.start" x-text="block.start"></small></div>
                                <div class="preview-anchor" x-show="block.anchor" x-text="block.anchor"></div>
                                <template x-for="(line, l) in lines[b]" :key="l">
                                    <div class="preview-line">
                                        <template x-for="(cell, c) in line" :key="c">
                                            <span><template x-for="(chord, i) in cell" :key="i"><span :class="{ 'is-passing': chord.passing }" x-text="(i ? ' ' : '') + chord.name"></span></template></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            </div>
                        </template>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Chord map preview: lines and block colors as on the public page (see chord-lines.js);
        // an identical chord repeated in a line is shown once (G G -> G).
        function songPreview(blocks) {
            const raw = blocks.map(block => block.chords);
            const passing = blocks.map(block => block.passing || []);
            const cycles = songCyclesOf(raw, passing);
            const lines = raw.map((chords, b) => chordSegments(chords, cycles, passing[b]).map(line => {
                const cells = [];
                line.forEach(step => {
                    const previous = cells[cells.length - 1];
                    const items = step.chords
                        .map((name, i) => ({ name, passing: passing[b].includes(step.positions[i]) }))
                        .filter((chord, i, list) => chord.name !== (i === 0 ? previous?.[previous.length - 1]?.name : list[i - 1].name));
                    if (items.length) cells.push(items);
                });
                return cells;
            }).filter(line => line.length));

            return {
                blocks,
                lines,
            };
        }
    </script>
</x-app-layout>
