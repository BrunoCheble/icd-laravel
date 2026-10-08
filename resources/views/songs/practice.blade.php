<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Practice') }} · {{ $data['song']['title'] }}@if ($data['setlist']) <span class="text-gray-500 font-normal">· {{ $data['setlist']['title'] }}</span>@endif
        </h2>
    </x-slot>

    <script src="{{ asset('js/chord-transposer.js') }}?v={{ filemtime(public_path('js/chord-transposer.js')) }}"></script>
    <script src="{{ asset('js/chord-lines.js') }}?v={{ filemtime(public_path('js/chord-lines.js')) }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        .practice {
            --surface: #ffffff; --surface-2: #f3f4f6; --border: #d1d5db; --text: #111827; --muted: #6b7280;
            --accent: #4f46e5; --accent-text: #ffffff; --accent-soft: rgba(79, 70, 229, .08); --chord: #111827;
            color: var(--text);
        }

        .counter-button { border: 0; background: none; padding: .35rem .25rem; border-radius: .375rem; cursor: pointer; text-decoration: underline dotted; text-underline-offset: .25em; }
        .counter-button:hover { background: #f3f4f6; color: #4f46e5; }
        .song-pick { display: grid; gap: .25rem; }
        .song-pick-item { display: flex; align-items: center; gap: .6rem; padding: .55rem .6rem; border-radius: .5rem; color: #111827; text-decoration: none; }
        .song-pick-item:hover { background: #f3f4f6; }
        .song-pick-item.is-current { background: #eef2ff; box-shadow: inset 3px 0 0 #4f46e5; }
        .song-pick-number { min-width: 1.5rem; font-size: .8rem; font-weight: 700; color: #9ca3af; text-align: right; font-variant-numeric: tabular-nums; }
        .song-pick-title { flex: 1; min-width: 0; font-weight: 600; font-size: .9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .song-pick-title small { display: block; font-weight: 400; color: #6b7280; overflow: hidden; text-overflow: ellipsis; }
        .song-pick-key { padding: .1rem .5rem; border: 1px solid #c7d2fe; border-radius: 999px; font-size: .75rem; font-weight: 700; color: #4f46e5; }
        .counter { white-space: nowrap; font-weight: 700; font-variant-numeric: tabular-nums; font-size: .85rem; color: var(--muted); min-width: 2.4rem; text-align: center; }
        /* Phones: the toolbar has room (songs are picked from the counter), so it is not squeezed */
        @media (max-width: 639px) {
            .practice .ws-toolbar { gap: .5rem; }
            .practice .ws-toolbar .ws-btn { min-width: 38px; min-height: 38px; }
            .practice .ws-toolbar .ws-seg button { min-height: 38px; padding: 0 .7rem; }
        }
        /* Small phones: play without its time and a little less room between buttons */
        @media (max-width: 379px) {
            .practice .ws-toolbar { gap: .35rem; }
            .practice .ws-toolbar .ws-seg button { padding: 0 .55rem; }
            .practice .ws-play .ws-time { display: none; }
        }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            min-height: 38px; padding: .35rem .75rem; border-radius: .5rem; box-sizing: border-box;
            border: 1px solid var(--border); background: var(--surface-2); color: var(--text);
            font-weight: 600; font-size: .9rem; cursor: pointer; user-select: none; text-decoration: none;
        }
        .btn:hover { filter: brightness(1.1); }
        .btn.is-disabled { opacity: .35; pointer-events: none; }
        .btn.is-active { border-color: var(--accent); color: var(--accent); }
        .percent { display: inline-flex; align-items: center; gap: .4rem; font-size: .85rem; color: var(--muted); }
        .percent input { width: 7rem; accent-color: var(--accent); }
        .percent strong { min-width: 2.6rem; color: var(--text); font-variant-numeric: tabular-nums; }

        .stage { width: 100%; max-width: 60rem; padding: .7rem 0 4rem; box-sizing: border-box; }
        .key-select { width: auto; min-height: 36px; padding: .2rem 2rem .2rem .6rem; border: 1px solid var(--accent); border-radius: 999px; color: var(--accent); font-weight: 700; font-size: .9rem; background-color: #fff; }
        .song-head { display: flex; align-items: baseline; flex-wrap: wrap; gap: .5rem; }
        .song-title { margin: 0; font-size: clamp(1.15rem, 3.4vw, 1.6rem); line-height: 1.15; }
        .meta { margin: .15rem 0 0; color: var(--muted); font-size: .9rem; }
        .hint { margin: .5rem 0 0; font-size: .82rem; color: var(--muted); }

        .section { margin-top: 1rem; padding: .35rem .5rem .5rem; border-radius: .5rem; border-left: 3px solid transparent; scroll-margin-top: 9rem; }
        .section.is-current { background: var(--accent-soft); border-left-color: var(--accent); }
        .section-head { display: flex; align-items: center; gap: .5rem; }
        .loop-btn { width: 30px; height: 30px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface); color: var(--muted); font-size: .75rem; cursor: pointer; }
        .loop-btn.is-active { border-color: var(--accent); background: var(--accent); color: var(--accent-text); }
        .score { font-size: .85rem; color: var(--muted); }
        .score strong { color: var(--text); }
        .section-label { display: inline-flex; gap: .5rem; align-items: baseline; font-size: .78rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--accent); background: none; border: 0; padding: 0; cursor: default; }
        .section-label.can-seek { cursor: pointer; }
        .section-label small { font-weight: 500; letter-spacing: 0; color: var(--muted); }

        /* Chord sheet: each chord over the lyric it starts on */
        .sheet-line { display: flex; flex-wrap: wrap; align-items: flex-start; margin-top: .45rem; font-size: calc(1.02rem * var(--scale, 1)); line-height: 1.35; }
        .sheet-comment { margin-top: .4rem; font-size: .85rem; font-style: italic; color: var(--muted); }
        .seg { display: inline-flex; flex-direction: column; }
        .seg-space { height: 1.35em; }
        .seg-text { min-height: 1.35em; white-space: pre; }
        /* Lines with chords only (intro, riffs) have no lyric row. */
        .sheet-line.only-chords .seg-text { display: none; }
        .sheet-line.only-chords .seg { padding-right: .4em; }

        /* Chord map */
        .map-anchor { margin-top: .15rem; font-size: calc(.95rem * var(--scale, 1)); font-style: italic; color: var(--muted); }
        .map-line { display: flex; flex-wrap: wrap; gap: .25rem 1.1rem; margin-top: .35rem; font-size: calc(1.15rem * var(--scale, 1)); }
        .map-step { display: inline-flex; gap: .45rem; }

        /* A chord; hidden ones keep their width and show a "?" */
        .chord { position: relative; align-self: flex-start; display: inline-block; min-width: 1.5em; padding-right: .35em; font-weight: 700; color: var(--chord); cursor: default; }
        .chord-mask { display: none; }
        .chord.was-hidden { cursor: pointer; }
        .chord.is-hidden .chord-name { visibility: hidden; }
        .chord.is-hidden .chord-mask {
            display: flex; position: absolute; inset: 0 .2em 0 0; align-items: center; justify-content: center;
            border: 1px dashed var(--accent); border-radius: .3rem; background: var(--accent-soft); color: var(--accent); font-size: .85em;
        }
        .chord.is-passing:not(.is-hidden) .chord-name { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .22em; }
        .chord.was-hidden:not(.is-hidden) .chord-name { text-decoration: underline dotted var(--accent); text-underline-offset: .2em; }
        /* Test mode: answers in green / red (showing the right chord); the chord being asked is outlined. */
        .chord.is-right .chord-name { color: #15803d; text-decoration: none; }
        .chord.is-wrong .chord-name { color: #dc2626; text-decoration: line-through dotted #dc2626; }
        .chord.is-asking .chord-mask { background: var(--accent); color: var(--accent-text); border-style: solid; }
        .is-test .chord.is-hidden .chord-mask { cursor: pointer; }
        @media (hover: hover) {
            .practice:not(.is-test) .chord.is-hidden:hover .chord-name { visibility: visible; }
            .practice:not(.is-test) .chord.is-hidden:hover .chord-mask { display: none; }
        }

        /* Test panel: question with 4 options, or the result at the end */
        .test-panel { position: fixed; z-index: 45; left: 0; right: 0; bottom: 0; padding: .8rem max(1rem, env(safe-area-inset-left)) max(.8rem, env(safe-area-inset-bottom)); background: var(--surface); border-top: 1px solid var(--border); box-shadow: 0 -8px 24px rgba(0, 0, 0, .12); }
        .test-panel-inner { max-width: 40rem; margin: 0 auto; }
        .test-title { display: flex; justify-content: space-between; align-items: center; gap: .5rem; font-weight: 700; }
        .test-options { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; margin-top: .6rem; }
        .test-option { min-height: 48px; border: 1px solid var(--border); border-radius: .5rem; background: var(--surface-2); color: var(--text); font-size: 1.15rem; font-weight: 700; cursor: pointer; }
        .test-option:hover { border-color: var(--accent); }
        .test-option small { display: block; font-size: .65rem; font-weight: 500; color: var(--muted); }
        .test-wrong { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
        .test-wrong span { padding: .1rem .5rem; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: .8rem; font-weight: 700; }
        .test-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .7rem; }

        .empty { margin-top: 2rem; text-align: center; color: var(--muted); }
    </style>

    @include('songs.partials.workspace')

    <div class="ws-page">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="ws-card bg-white shadow sm:rounded-lg">
                @include('songs.partials.tabs', ['song' => $data['song']['id'], 'current' => 'practice'])
                @php
                    $song = $data['song'];
                    // Practicing a setlist: its songs and page; otherwise the song list.
                    $setlist = $data['setlist'];
                    $practiceUrl = fn (array $item) => $setlist
                        ? route('setlists.practice', [$setlist['id'], $item['id']])
                        : route('songs.practice', $item['id']);
                @endphp
                <div class="practice" :class="{ 'is-test': settings.mode === 'test' }" x-data="practicePage(@js($data))" @keydown.window="onKey($event)" :style="`--scale: ${settings.scale}`">
                    {{-- Toolbar: songs, mode, view, player, options --}}
                    <div class="ws-toolbar">
                        <a class="ws-btn ws-desktop" href="{{ $setlist ? route('setlists.show', $setlist['id']) : route('songs.index') }}" title="{{ __('Back') }}" aria-label="{{ __('Back') }}"><i class="fa-solid fa-arrow-left"></i></a>
                        <button type="button" class="counter counter-button" @click="songsOpen = true; songSearch = ''; $nextTick(() => $refs.songSearch?.focus())"
                            title="{{ __('Choose the song') }}" aria-label="{{ __('Choose the song') }}">{{ $data['position'] }}/{{ $data['total'] }}</button>
                        <span class="ws-seg" role="group" aria-label="{{ __('Mode') }}">
                            <button type="button" :class="{ 'is-active': settings.mode === 'practice' }" @click="setMode('practice')" title="{{ __('Practice') }}"><i class="fa-solid fa-eye"></i><span class="ws-label"> {{ __('Practice') }}</span></button>
                            <button type="button" :class="{ 'is-active': settings.mode === 'test' }" @click="setMode('test')" title="{{ __('Test') }}"><i class="fa-solid fa-circle-question"></i><span class="ws-label"> {{ __('Test') }}</span></button>
                        </span>
                        <span class="ws-seg" x-show="hasSheet && hasMap" role="group" aria-label="{{ __('View') }}">
                            <button type="button" :class="{ 'is-active': view === 'sheet' }" @click="setView('sheet')" title="{{ __('Chord sheet') }}"><i class="fa-solid fa-align-left"></i><span class="ws-label"> {{ __('Chord sheet') }}</span></button>
                            <button type="button" :class="{ 'is-active': view === 'map' }" @click="setView('map')" title="{{ __('Map') }}"><i class="fa-solid fa-table-cells"></i><span class="ws-label"> {{ __('Map') }}</span></button>
                        </span>
                        <span class="ws-spacer"></span>
                        <span class="ws-note ws-desktop" x-show="settings.mode === 'practice'" :title="`${settings.percent}%`"><i class="fa-solid fa-eye-slash"></i> <span x-text="`${hiddenCount}/${total}`"></span></span>
                        <span class="ws-note ws-desktop" x-show="settings.mode === 'test'">{{ __('Right') }}: <strong x-text="`${rightCount}/${answeredCount}`"></strong> · {{ __('Left') }}: <strong x-text="hiddenCount - answeredCount"></strong></span>
                        <button type="button" class="ws-btn is-active" x-show="loop !== null" @click="loop = null" :title="@js(__('Stop repeating'))">
                            <i class="fa-solid fa-repeat"></i><span class="ws-label" x-text="loop !== null ? sections[loop]?.label : ''"></span><i class="fa-solid fa-xmark"></i>
                        </button>
                        @include('songs.partials.play-button', ['toggle' => 'togglePlay()', 'time' => 'formatTime(time)', 'show' => 'videoId'])
                        <button type="button" class="ws-btn" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" title="{{ __('Options') }}" aria-label="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
                    </div>
                    {{-- Phones: the test score under the toolbar --}}
                    <p class="ws-note" x-show="settings.mode === 'test' && hiddenCount === 0" x-cloak style="padding-top: .35rem; white-space: normal;">
                        <i class="fa-solid fa-circle-info"></i> {{ __('No chord is hidden: choose how many to hide in the options (gear) to start the test.') }}
                    </p>
                    <p class="ws-note ws-mobile" x-show="settings.mode === 'test' && hiddenCount > 0" style="padding-top: .35rem;">
                        {{ __('Right') }}: <strong x-text="`${rightCount}/${answeredCount}`"></strong> · {{ __('Left') }}: <strong x-text="hiddenCount - answeredCount"></strong>
                    </p>

                    {{-- Songs of the setlist (or all songs): a tap opens one --}}
                    <x-song-options :title="$setlist ? $setlist['title'] : __('Songs')" open="songsOpen">
                        @if (count($data['songs']) > 8)
                            <input type="search" x-ref="songSearch" x-model="songSearch" placeholder="{{ __('Title or artist') }}"
                                class="mb-2 w-full border-gray-300 rounded-md shadow-sm text-sm">
                        @endif
                        <div class="song-pick">
                            @foreach ($data['songs'] as $index => $item)
                                <a href="{{ $practiceUrl($item) }}" @class(['song-pick-item', 'is-current' => $item['id'] === $song['id']])
                                    x-show="!songSearch || @js(mb_strtolower($item['title'] . ' ' . $item['artist'])).includes(songSearch.toLowerCase())">
                                    <span class="song-pick-number">{{ $index + 1 }}</span>
                                    <span class="song-pick-title">{{ $item['title'] }}<small>{{ $item['artist'] }}</small></span>
                                    @if ($item['key'])<span class="song-pick-key">{{ $item['key'] }}</span>@endif
                                </a>
                            @endforeach
                        </div>
                    </x-song-options>

                    <x-song-options>
                        <div class="ws-panel-section">
                            <div class="ws-panel-label"><i class="fa-solid fa-eye-slash"></i> {{ __('Hidden chords') }}: <span x-text="`${hiddenCount}/${total}`"></span></div>
                            <div class="ws-panel-row">
                                <span class="ws-seg" role="group" aria-label="{{ __('Difficulty') }}">
                                    <template x-for="level in levels" :key="level.percent">
                                        <button type="button" :class="{ 'is-active': settings.percent === level.percent }" @click="setPercent(level.percent)" x-text="level.label"></button>
                                    </template>
                                </span>
                                <label class="percent">
                                    <input type="range" min="0" max="100" step="5" :value="settings.percent" @input="setPercent(Number($event.target.value))" aria-label="{{ __('Hidden chords') }}">
                                    <strong x-text="`${settings.percent}%`"></strong>
                                </label>
                            </div>
                            <div class="ws-panel-row mt-2" x-show="hiddenCount > 0">
                                <button type="button" class="ws-btn" @click="shuffle()" title="{{ __('Hide other chords') }}"><i class="fa-solid fa-shuffle"></i> {{ __('Shuffle') }}</button>
                                <button type="button" class="ws-btn" :class="{ 'is-active': showAll }" @click="toggleShowAll()" x-show="settings.mode === 'practice'">
                                    <i class="fa-solid" :class="showAll ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    <span x-text="showAll ? @js(__('Hide again')) : @js(__('Show all'))"></span>
                                </button>
                            </div>
                        </div>
                        <div class="ws-panel-section" x-show="hasPassing">
                            <div class="ws-panel-label"><i class="fa-solid fa-water"></i> {{ __('Passing chords') }}</div>
                            <span class="ws-seg" role="group" aria-label="{{ __('Passing chords') }}">
                                <button type="button" :class="{ 'is-active': settings.passing !== 'hide' }" @click="setPassing('show')">{{ __('Show') }}</button>
                                <button type="button" :class="{ 'is-active': settings.passing === 'hide' }" @click="setPassing('hide')">{{ __('Hide') }}</button>
                            </span>
                        </div>
                        <div class="ws-panel-section">
                            <div class="ws-panel-label"><i class="fa-solid fa-text-height"></i> {{ __('Font size') }}</div>
                            <div class="ws-panel-row">
                                <button type="button" class="ws-btn" @click="zoom(-1)" aria-label="{{ __('Smaller') }}">A−</button>
                                <span class="ws-note" x-text="`${Math.round(settings.scale * 100)}%`"></span>
                                <button type="button" class="ws-btn" @click="zoom(1)" aria-label="{{ __('Larger') }}">A+</button>
                            </div>
                        </div>
                        @include('songs.partials.video-options')
                        <div class="ws-panel-section">
                            <div class="ws-panel-label"><i class="fa-solid fa-circle-info"></i> {{ __('How it works') }}</div>
                            <p class="ws-panel-help" x-show="settings.mode === 'practice'">{{ __('Each "?" is a hidden chord: hover it (or tap it on the phone) to see it.') }}</p>
                            <p class="ws-panel-help" x-show="settings.mode === 'test'">{{ __('Tap a "?" and choose the chord (keys 1 to 4 also answer).') }}</p>
                        </div>
                    </x-song-options>

                    <main class="stage">
                        <div class="song-head">
                            <h1 class="song-title ws-desktop">{{ $song['title'] }}</h1>
                            <select class="key-select" x-model="key" aria-label="{{ __('Key') }}" title="{{ __('Change key') }}">
                                <template x-for="option in keyOptions" :key="option">
                                    <option :value="option" x-text="keyLabel(option)" :selected="option === key"></option>
                                </template>
                            </select>
                        </div>
                        <p class="meta">
                            {{ $song['artist'] }}
                            @if ($data['setlist'] && $song['setlist_key'])
                                · {{ $song['minister_name'] ? __('Key of :minister', ['minister' => $song['minister_name']]) : __('Setlist key') }}: <strong>{{ $song['setlist_key'] }}</strong>
                            @endif
                        </p>
                        <p class="hint ws-desktop" x-show="settings.mode === 'practice'">{{ __('Each "?" is a hidden chord: hover it (or tap it on the phone) to see it.') }}</p>
                        <p class="hint ws-desktop" x-show="settings.mode === 'test'" x-cloak>{{ __('Tap a "?" and choose the chord (keys 1 to 4 also answer).') }}</p>

                        <template x-for="(section, s) in sections" :key="`${view}-${s}`">
                            <section class="section" :class="{ 'is-current': s === currentIndex }" :data-section="s">
                                <div class="section-head">
                                    <button type="button" class="section-label" :class="{ 'can-seek': canSeek(section) }" @click="seekTo(section)">
                                        <span x-text="section.label"></span><small x-show="section.start !== null" x-text="formatTime(section.start)"></small>
                                    </button>
                                    <button type="button" class="loop-btn" x-show="canSeek(section)" :class="{ 'is-active': loop === s }" @click="toggleLoop(s)"
                                        :title="loop === s ? @js(__('Stop repeating')) : @js(__('Repeat this block'))"><i class="fa-solid fa-repeat"></i></button>
                                </div>

                                <template x-if="view === 'sheet'">
                                    <div>
                                        <template x-for="(line, l) in section.lines" :key="l">
                                            <div :class="line.comment !== undefined ? 'sheet-comment' : ('sheet-line' + (line.onlyChords ? ' only-chords' : ''))">
                                                <span x-show="line.comment !== undefined" x-text="line.comment"></span>
                                                <template x-for="(unit, u) in (line.units || [])" :key="u">
                                                    <span class="seg">
                                                        <template x-if="unit.g !== null && !hidesPassing(unit.g)">
                                                            <span class="chord" :data-g="unit.g" :class="chordClasses(unit.g)" @click="pick(unit.g)" :title="answers[unit.g] && !answers[unit.g].right ? @js(__('Your answer')) + ': ' + answers[unit.g].chosen : ''">
                                                                <span class="chord-name" x-text="chordName(unit.chord)"></span><span class="chord-mask">?</span>
                                                            </span>
                                                        </template>
                                                        <span class="seg-space" x-show="unit.g === null || hidesPassing(unit.g)"></span>
                                                        <span class="seg-text" x-text="unit.text"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="view === 'map'">
                                    <div>
                                        <div class="map-anchor" x-show="section.anchor" x-text="section.anchor"></div>
                                        <template x-for="(line, l) in section.lines" :key="l">
                                            <div class="map-line">
                                                <template x-for="(step, i) in line" :key="i">
                                                    <span class="map-step">
                                                        <template x-for="item in step.filter(item => !hidesPassing(item.g))" :key="item.g">
                                                            <span class="chord" :data-g="item.g" :class="chordClasses(item.g)" @click="pick(item.g)" :title="answers[item.g] && !answers[item.g].right ? @js(__('Your answer')) + ': ' + answers[item.g].chosen : ''">
                                                                <span class="chord-name" x-text="chordName(item.chord)"></span><span class="chord-mask">?</span>
                                                            </span>
                                                        </template>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </section>
                        </template>
                        <p class="empty" x-show="!sections.length">{{ __('This song has no chords to practice yet.') }}</p>
                    </main>

                    <div class="test-panel" x-show="settings.mode === 'test' && (question || testDone)" x-cloak>
                        <div class="test-panel-inner">
                            <template x-if="question">
                                <div>
                                    <div class="test-title">
                                        <span>{{ __('Which chord is it?') }}</span>
                                        <button type="button" class="ws-btn" @click="question = null" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                    <div class="test-options">
                                        <template x-for="(option, i) in question.options" :key="option">
                                            <button type="button" class="test-option" @click="answer(option)"><span x-text="option"></span><small x-text="i + 1"></small></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!question && testDone">
                                <div>
                                    <div class="test-title">
                                        <span x-text="@js(__('You got :right of :total right.')).replace(':right', rightCount).replace(':total', hiddenCount)"></span>
                                    </div>
                                    <div class="test-wrong" x-show="wrongList.length">
                                        <template x-for="item in wrongList" :key="item.g"><span x-text="item.name"></span></template>
                                    </div>
                                    <div class="test-actions">
                                        <button type="button" class="btn" x-show="wrongList.length" @click="retryWrong()"><i class="fa-solid fa-rotate-left"></i> {{ __('Repeat the ones I missed') }}</button>
                                        <button type="button" class="btn" @click="shuffle(); askNext()"><i class="fa-solid fa-shuffle"></i> {{ __('New test') }}</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    @include('songs.partials.youtube-mini', ['id' => 'yt-practice'])
                </div>
            </div>
        </div>
    </div>

    <script>
        // Practice page: some chords of the song are hidden ("?"); hovering (or tapping) one shows it.
        // Nothing is saved on the server; the chosen amount, view and size stay in this browser.
        const PRACTICE_KEY = 'practice-settings';
        const PRACTICE_DEFAULTS = { percent: 0, view: 'sheet', scale: 1, mode: 'practice', passing: 'show' };

        // A song always opens with every chord shown (study it first), whatever was hidden last time.
        function loadPracticeSettings() {
            try {
                return { ...PRACTICE_DEFAULTS, ...JSON.parse(localStorage.getItem(PRACTICE_KEY) || '{}'), percent: 0 };
            } catch (e) {
                return { ...PRACTICE_DEFAULTS };
            }
        }

        function savePracticeSettings(settings) {
            try {
                localStorage.setItem(PRACTICE_KEY, JSON.stringify(settings));
            } catch (e) {
                // Storage unavailable: the settings last only while the page is open.
            }
        }

        function toSeconds(value) {
            if (typeof value === 'number' && Number.isFinite(value) && value >= 0) return value;
            if (typeof value !== 'string' || !/^\d+(:\d{1,2}){0,2}(\.\d+)?$/.test(value.trim())) return null;
            return value.trim().split(':').reduce((total, part) => total * 60 + Number(part), 0);
        }

        // Chord sheet sections -> [{ label, start, lines: [{ comment } | { units: [{ chord, text, g }] }] }],
        // g numbering every chord of the song in order.
        function practiceSheet(sections) {
            let number = 0;
            // "Back to the start" markers are not practiced.
            return (sections || []).filter(section => section.jump !== 'start').map(section => ({
                label: section.label || String(section.section ?? '').replaceAll('_', ' '),
                start: toSeconds(section.start),
                lines: (section.lines || []).map(line => {
                    const text = String(line ?? '');
                    const comment = /^\{\s*(?:c|comment)\s*:\s*(.*?)\s*\}$/i.exec(text.trim());
                    if (comment) return { comment: comment[1] };
                    if (/^\s*\{/.test(text)) return { comment: '' };
                    const units = [];
                    let last = 0;
                    let chord = null;
                    for (const match of text.matchAll(/\[([^\]]+)\]/g)) {
                        const before = text.slice(last, match.index);
                        if (chord !== null || before) units.push({ chord, text: before });
                        chord = match[1];
                        last = match.index + match[0].length;
                    }
                    units.push({ chord, text: text.slice(last) });
                    units.forEach(unit => unit.g = unit.chord === null ? null : number++);
                    return { units, onlyChords: units.every(unit => unit.text.trim() === '') };
                }),
            }));
        }

        // Chord map sections -> [{ label, start, lines: [[[{ chord, g }]]] }] (lines of steps of chords), split
        // into lines like the public page (see chord-lines.js).
        function practiceMap(structure) {
            const list = (Array.isArray(structure) ? structure : []).filter(section => section && typeof section === 'object');
            const raw = list.map(section => Array.isArray(section.chords) ? section.chords.map(String) : []);
            const passing = list.map(section => Array.isArray(section.passing) ? section.passing : []);
            const cycles = songCyclesOf(raw, passing);
            let offset = 0;
            return list.map((section, index) => {
                const lines = chordSegments(raw[index], cycles, passing[index])
                    .map(line => line.map(step => step.chords.map((chord, i) => ({ chord, g: offset + step.positions[i], passing: passing[index].includes(step.positions[i]) }))));
                offset += chordsOnly(raw[index]).length;
                return {
                    label: String(section.section ?? '').replaceAll('_', ' '),
                    anchor: typeof section.anchor === 'string' ? section.anchor.trim() : '',
                    start: toSeconds(section.start),
                    lines,
                };
            }).filter(section => section.lines.length);
        }

        function practicePage(data) {
            const song = data.song;
            const sheet = practiceSheet(song.chord_sheet);
            const map = practiceMap(song.structure);
            const countSheet = sheet.reduce((total, section) => total + section.lines.reduce((sum, line) => sum + (line.units || []).filter(unit => unit.g !== null).length, 0), 0);
            const countMap = map.reduce((total, section) => total + section.lines.flat(2).length, 0);
            // Chord of each number, per view.
            const sheetChords = [];
            sheet.forEach(section => section.lines.forEach(line => (line.units || []).forEach(unit => { if (unit.g !== null) sheetChords[unit.g] = unit.chord; })));
            const mapChords = [];
            map.forEach(section => section.lines.flat(2).forEach(item => mapChords[item.g] = item.chord));
            const shuffled = (list) => list.map(item => [Math.random(), item]).sort((a, b) => a[0] - b[0]).map(pair => pair[1]);
            // Passing chords by number: in the map they are marked; the chord sheet takes them from the map when both
            // have the same chords (chord n of the sheet is chord n of the map).
            const mapPassing = new Set();
            map.forEach(section => section.lines.flat(2).forEach(item => { if (item.passing) mapPassing.add(item.g); }));
            const sheetPassing = JSON.stringify(sheetChords) === JSON.stringify(mapChords.filter(chord => chord !== undefined)) ? mapPassing : new Set();

            return withYoutubeMini({
                settings: loadPracticeSettings(),
                levels: [
                    { percent: 0, label: @js(__('None')) },
                    { percent: 25, label: @js(__('Easy')) },
                    { percent: 50, label: @js(__('Medium')) },
                    { percent: 75, label: @js(__('Hard')) },
                    { percent: 100, label: @js(__('All')) },
                ],
                hasSheet: countSheet > 0,
                hasMap: countMap > 0,
                view: 'sheet',
                // Chord numbers hidden ({ g: true }) and the hidden ones currently shown by a tap.
                hidden: {},
                revealed: {},
                showAll: false,
                // Test mode: answers ({ g: { right, chosen } }) and the chord being asked ({ g, options, correct }).
                answers: {},
                question: null,
                optionsOpen: false,
                songsOpen: false,
                songSearch: '',
                // Block repeated by the video (its index).
                loop: null,
                loopCooldown: 0,
                originalKey: song.original_key || '',
                // In a setlist: the key chosen for it (the minister's), which the page opens in.
                setlistKey: data.setlist ? (song.setlist_key || '') : '',
                keyLabel(option) {
                    const marks = [];
                    if (option === this.setlistKey) marks.push(song.minister_name || @js(__('setlist')));
                    if (option === this.originalKey) marks.push(@js(__('original')));
                    const pair = data.keys.find(item => item.major === option || item.minor === option);
                    const label = pair ? pair.label : option;
                    return marks.length ? `${label} (${marks.join(', ')})` : label;
                },
                // The setlist's key when practicing a setlist.
                key: song.setlist_key || song.original_key || '',
                // One key per pair of relative keys ("C / Am"), in the song's mode (major or minor), to practice in another key.
                get keyOptions() {
                    const original = ChordTransposer.parseKey(this.originalKey);
                    const keys = data.keys.map(pair => (original?.minor ? pair.minor : pair.major));
                    return this.originalKey && !keys.includes(this.originalKey) ? [this.originalKey, ...keys] : keys;
                },
                time: 0,
                lastCurrent: null,

                init() {
                    this.view = this.hasSheet && (this.settings.view === 'sheet' || !this.hasMap) ? 'sheet' : 'map';
                    this.shuffle();
                    this.$nextTick(() => this.initVideo('yt-practice'));
                    setInterval(() => this.tick(), 250);
                },

                get sections() { return this.view === 'sheet' ? sheet : map; },
                isPassing(g) { return (this.view === 'sheet' ? sheetPassing : mapPassing).has(g); },
                get hasPassing() { return mapPassing.size > 0; },
                // Passing chords left out of the view (settings: show / hide).
                hidesPassing(g) { return this.settings.passing === 'hide' && this.isPassing(g); },
                // Chords that can be hidden ("?") and tested: all, or all but the passing ones when those are left out.
                get eligible() {
                    const count = this.view === 'sheet' ? countSheet : countMap;
                    return Array.from({ length: count }, (_, g) => g).filter(g => !this.hidesPassing(g));
                },
                get total() { return this.eligible.length; },
                get hiddenCount() { return Object.keys(this.hidden).length; },

                chordName(chord) { return ChordTransposer.transposeChord(chord, this.originalKey, this.key); },
                chordAt(g) { return (this.view === 'sheet' ? sheetChords : mapChords)[g]; },
                isHidden(g) {
                    if (!this.hidden[g] || this.showAll) return false;
                    return this.settings.mode === 'test' ? !this.answers[g] : !this.revealed[g];
                },
                chordClasses(g) {
                    const answer = this.answers[g];
                    return {
                        'is-hidden': this.isHidden(g),
                        'was-hidden': !!this.hidden[g],
                        'is-right': !!answer && answer.right,
                        'is-wrong': !!answer && !answer.right,
                        'is-asking': this.question?.g === g,
                        'is-passing': this.isPassing(g),
                    };
                },
                // Practice: a tap (or click) shows a hidden chord until it is tapped again. Test: asks for it.
                pick(g) {
                    if (!this.hidden[g] || this.showAll) return;
                    if (this.settings.mode === 'test') {
                        if (!this.answers[g]) this.ask(g);
                        return;
                    }
                    this.revealed = { ...this.revealed, [g]: !this.revealed[g] };
                },

                // ---- Test mode ----
                setMode(mode) {
                    this.settings.mode = mode;
                    savePracticeSettings(this.settings);
                    this.answers = {};
                    this.question = null;
                    this.revealed = {};
                    this.showAll = false;
                },
                get answeredCount() { return Object.keys(this.answers).length; },
                get rightCount() { return Object.values(this.answers).filter(answer => answer.right).length; },
                get testDone() { return this.hiddenCount > 0 && this.answeredCount === this.hiddenCount; },
                get wrongList() {
                    return Object.keys(this.answers).map(Number).sort((a, b) => a - b)
                        .filter(g => !this.answers[g].right)
                        .map(g => ({ g, name: this.chordName(this.chordAt(g)) }));
                },
                // The right chord and 3 others: chords of this song first, then usual chords of the key.
                ask(g) {
                    const correct = this.chordName(this.chordAt(g));
                    const fromSong = [...new Set((this.view === 'sheet' ? sheetChords : mapChords).map(chord => this.chordName(chord)))];
                    const minor = !!ChordTransposer.parseKey(this.key)?.minor;
                    const fromKey = (minor ? ['Am', 'C', 'Dm', 'Em', 'F', 'G', 'E'] : ['C', 'Dm', 'Em', 'F', 'G', 'Am', 'D'])
                        .map(chord => ChordTransposer.transposeChord(chord, minor ? 'Am' : 'C', this.key));
                    const others = [...shuffled(fromSong), ...shuffled(fromKey)].filter((chord, i, list) => chord !== correct && list.indexOf(chord) === i);
                    this.question = { g, correct, options: shuffled([correct, ...others.slice(0, 3)]) };
                    this.$nextTick(() => this.$root.querySelector(`[data-g="${g}"]`)?.scrollIntoView({ block: 'center', behavior: 'smooth' }));
                },
                // Records the answer and asks the next hidden chord of the song.
                answer(option) {
                    const { g, correct } = this.question;
                    this.answers = { ...this.answers, [g]: { right: option === correct, chosen: option } };
                    this.question = null;
                    this.askNext(g);
                },
                askNext(after = -1) {
                    const pending = Object.keys(this.hidden).map(Number).sort((a, b) => a - b).filter(g => !this.answers[g]);
                    const next = pending.find(g => g > after) ?? pending[0];
                    if (next !== undefined) this.ask(next);
                },
                retryWrong() {
                    this.hidden = Object.fromEntries(this.wrongList.map(item => [item.g, true]));
                    this.answers = {};
                    this.askNext();
                },

                // Spread over the whole song: the chords are split into as many equal stretches as chords to hide,
                // and one chord of each stretch is drawn.
                shuffle() {
                    const eligible = this.eligible;
                    const total = eligible.length;
                    // 0% shows every chord; any other amount hides at least one.
                    const count = this.settings.percent === 0 ? 0
                        : Math.min(total, Math.max(total ? 1 : 0, Math.round(total * this.settings.percent / 100)));
                    const hidden = {};
                    for (let i = 0; i < count; i++) {
                        const from = Math.floor(i * total / count);
                        const to = Math.floor((i + 1) * total / count);
                        hidden[eligible[from + Math.floor(Math.random() * (to - from))]] = true;
                    }
                    this.hidden = hidden;
                    this.revealed = {};
                    this.answers = {};
                    this.question = null;
                    this.showAll = false;
                },
                setPassing(value) {
                    this.settings.passing = value;
                    savePracticeSettings(this.settings);
                    this.shuffle();
                },
                setPercent(percent) {
                    this.settings.percent = percent;
                    savePracticeSettings(this.settings);
                    this.shuffle();
                },
                setView(view) {
                    this.view = view;
                    this.settings.view = view;
                    savePracticeSettings(this.settings);
                    this.lastCurrent = null;
                    this.loop = null;
                    this.shuffle();
                },
                toggleShowAll() {
                    this.showAll = !this.showAll;
                    if (!this.showAll) this.revealed = {};
                },
                zoom(step) {
                    this.settings.scale = Math.min(1.6, Math.max(0.8, Math.round((this.settings.scale + step * 0.1) * 10) / 10));
                    savePracticeSettings(this.settings);
                },

                // ---- YouTube (shared small player, see youtube-mini.js) ----
                togglePlay() { this.toggleVideo(); },
                tick() {
                    if (!this.playerReady) return;
                    this.time = this.videoCurrentTime();
                    // Repeating a block: back to its start when the next block starts (or the video jumped out of it),
                    // also while the video is loading (state 3), which often happens right at the end of a block.
                    const state = this.videoState();
                    const range = this.loop === null ? null : this.loopRange(this.loop);
                    if (range && (state === 1 || state === 3) && performance.now() > this.loopCooldown
                        && (this.time >= range.end - 0.3 || this.time < range.start - 0.5)) {
                        this.restartLoop();
                    }
                    // The current block is kept in view while the video plays.
                    const current = this.currentIndex;
                    if (state === 1 && current !== null && current !== this.lastCurrent) {
                        this.$root.querySelector(`[data-section="${current}"]`)?.scrollIntoView({ block: 'start', behavior: 'smooth' });
                    }
                    this.lastCurrent = current;
                },
                // Block being played: the one with the latest start not after the video time.
                get currentIndex() {
                    if (!this.playerReady) return null;
                    let found = null;
                    this.sections.forEach((section, index) => {
                        if (section.start !== null && section.start <= this.time + 0.2) found = index;
                    });
                    return found;
                },
                canSeek(section) { return this.playerReady && section.start !== null; },
                seekTo(section) {
                    if (this.canSeek(section)) this.seekVideo(section.start);
                },
                // A block is played from its start to the start of the next timed block (or the end of the video).
                loopRange(index) {
                    const start = this.sections[index]?.start;
                    if (start === null || start === undefined) return null;
                    const next = this.sections.slice(index + 1).find(section => section.start !== null && section.start > start);
                    return { start, end: next ? next.start : this.videoDuration() };
                },
                toggleLoop(index) {
                    this.loop = this.loop === index ? null : index;
                    if (this.loop !== null) this.restartLoop();
                },
                restartLoop() {
                    const range = this.loopRange(this.loop);
                    if (!range) return;
                    // The player reports the old time for a moment after a jump: no new jump meanwhile.
                    this.loopCooldown = performance.now() + 1000;
                    this.time = range.start;
                    this.seekVideo(range.start);
                },
                formatTime(seconds) {
                    const total = Math.max(0, Math.round(seconds || 0));
                    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
                },

                // Space plays/pauses; S draws other chords to hide; 1-4 answer the question in test mode.
                onKey(event) {
                    if (event.target.closest?.('input, textarea, select')) return;
                    if (this.question && /^[1-4]$/.test(event.key)) {
                        const option = this.question.options[Number(event.key) - 1];
                        if (option) this.answer(option);
                        return;
                    }
                    if (event.code === 'Space' && this.videoId) { event.preventDefault(); this.togglePlay(); }
                    if (event.key === 's' || event.key === 'S') this.shuffle();
                },
            }, song.youtube_url, {
                // The last block ends with the video: start it again.
                onEnded() { if (this.loop !== null) this.restartLoop(); },
                // Above the test panel when it is open.
                bottomOffset() { return this.settings.mode === 'test' && (this.question || this.testDone) ? '9.5rem' : null; },
            });
        }
    </script>
</x-app-layout>
