<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Layout') }} · {{ $song->title }}
        </h2>
    </x-slot>

    <script src="{{ asset('js/chord-transposer.js') }}?v={{ filemtime(public_path('js/chord-transposer.js')) }}"></script>
    <script src="{{ asset('js/chord-lines.js') }}?v={{ filemtime(public_path('js/chord-lines.js')) }}"></script>

    <style>
        /* Colors by kind of block, as on the public page (see blockType in chord-lines.js) */
        .layout-page { --type-intro: #0369a1; --type-verse: #047857; --type-prechorus: #b45309; --type-chorus: #be123c; --type-bridge: #6d28d9; --type-instrumental: #4338ca; --type-ending: #475569; --type-other: #111827; }
        .layout-btn { min-height: 2.25rem; padding: 0 .65rem; border: 1px solid #d1d5db; border-radius: .375rem; background: #fff; font-weight: 600; font-size: .8rem; color: #374151; }
        .layout-btn:hover:not(:disabled) { background: #f9fafb; }
        .layout-btn:disabled { opacity: .45; cursor: default; }
        /* Narrow screens: one pane at a time (chord sheet or map), picked in the toolbar */
        @media (min-width: 1024px) { .ws-narrow { display: none !important; } }
        @media (max-width: 1023px) {
            .layout-grid.has-sheet.pane-sheet > .layout-map { display: none; }
            .layout-grid.has-sheet.pane-map > .sheet-col { display: none !important; }
        }
        .layout-grid { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 1024px) {
            .layout-grid.has-sheet { grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); align-items: start; }
            .layout-grid.has-sheet .layout-map { position: sticky; top: 3.5rem; max-height: calc(100vh - 4.5rem); overflow-y: auto; padding-right: .25rem; }
        }
        .layout-block { border: 1px solid #d1d5db; border-radius: .5rem; padding: .6rem .75rem .75rem; }
        .layout-block { cursor: pointer; transition: border-color .15s, background .15s; }
        /* Block being played by the video (amber; the indigo highlight is the one picked by a click) */
        .layout-block.is-playing { box-shadow: inset 4px 0 0 #f59e0b; background: #fffbeb; }
        .layout-time { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .5rem; border: 1px solid #fcd34d; border-radius: 999px; background: #fffbeb; color: #92400e; font-size: .75rem; font-weight: 700; font-variant-numeric: tabular-nums; cursor: pointer; }
        .layout-time:hover { background: #fef3c7; }
        .layout-block.is-selected { border-color: #4f46e5; background: #f5f7ff; box-shadow: 0 0 0 2px #c7d2fe; }
        .key-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .35rem; }
        .key-chip { min-height: 38px; border: 1px solid #d1d5db; border-radius: .5rem; background: #fff; color: #374151; font-weight: 700; font-size: .9rem; cursor: pointer; }
        .key-chip:hover { background: #f3f4f6; }
        .key-chip.is-active { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .layout-timing { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-bottom: .5rem; }
        .layout-time-input { width: 4.75rem; padding: .25rem .4rem; text-align: center; font-variant-numeric: tabular-nums; }
        .layout-block-head { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: .5rem; }
        .layout-name { width: 12rem; max-width: 100%; padding: .3rem .5rem; font-size: .85rem; font-weight: 700; text-transform: uppercase; }
        .layout-suggest { padding: .25rem .55rem; border: 1px dashed #a5b4fc; border-radius: .375rem; background: #eef2ff; font-size: .75rem; font-weight: 700; color: #4338ca; text-transform: uppercase; }
        .layout-suggest:hover { background: #e0e7ff; }
        .layout-tag { font-size: .75rem; color: #6b7280; }
        .layout-lines { display: grid; gap: .35rem; }
        .layout-line { display: flex; flex-wrap: wrap; gap: .3rem; padding-left: .5rem; border-left: 3px solid var(--type-color, #e5e7eb); }
        .layout-chord { position: relative; min-width: 2.5rem; padding: .3rem .55rem; border: 1px solid #e5e7eb; border-radius: .375rem; background: #f9fafb; font-weight: 700; color: var(--type-color, #111827); cursor: pointer; }
        .layout-chord:hover { border-color: #4f46e5; background: #eef2ff; }
        .layout-chord:disabled { cursor: default; }
        .layout-chord:disabled:hover { border-color: #e5e7eb; background: #f9fafb; }
        /* Where a click would act: before the chord (new line or new block). */
        .layout-chord:not(:disabled):hover::before { content: ''; position: absolute; left: -.25rem; top: .15rem; bottom: .15rem; width: 3px; border-radius: 2px; background: #4f46e5; }
        /* Passing chords: dotted underline (here and wherever the map is shown) */
        .layout-chord.is-passing, .sheet-chord.is-passing > span:last-child { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .25em; }
        /* Phones: play without its time, so the toolbar fits in one line */
        @media (max-width: 639px) { .layout-page .ws-play .ws-time { display: none; } }
        .layout-empty { font-size: .85rem; color: #9ca3af; }
        .layout-lyrics { margin-top: .5rem; }
        .layout-lyrics textarea { width: 100%; font-size: .85rem; line-height: 1.4; }
        .layout-link { font-size: .8rem; font-weight: 600; color: #4f46e5; background: none; border: 0; padding: 0; cursor: pointer; }
        .layout-notice { padding: .75rem 1rem; border: 1px solid #fcd34d; border-radius: .5rem; background: #fffbeb; font-size: .875rem; color: #92400e; }

        /* Chord sheet: each chord over the lyric it starts on; clicking a chord acts on the map. */
        .sheet-section + .sheet-section { margin-top: 1.25rem; }
        /* "Back to the start" marker */
        .layout-block.is-return { display: flex; align-items: center; gap: .4rem; padding: .4rem .75rem; border-style: dashed; background: #f9fafb; }
        .return-name { font-size: .8rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        .sheet-return { padding: .25rem .5rem; border-left: 3px dashed #9ca3af; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        .sheet-label { font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        .sheet-line { display: flex; flex-wrap: wrap; align-items: flex-start; margin-top: 1.35rem; font-size: .95rem; line-height: 1.3; }
        /* A line belongs to a block of the map: clicking it (not a chord) highlights both. */
        .sheet-line, .sheet-comment, .sheet-label { cursor: pointer; border-radius: .25rem; margin-left: -.35rem; padding-left: .35rem; transition: background .15s; }
        .sheet-line:hover, .sheet-comment:hover { background: #f9fafb; }
        .sheet-line.is-playing, .sheet-comment.is-playing, .sheet-label.is-playing { background: #fffbeb; box-shadow: inset 3px 0 0 #f59e0b; }
        .sheet-line.is-selected, .sheet-comment.is-selected, .sheet-label.is-selected { background: #eef2ff; box-shadow: inset 3px 0 0 #4f46e5; }
        .sheet-chord.is-selected { background: #c7d2fe; }
        /* Lines with chords only (intro, riffs) have no lyric row */
        .sheet-line.only-chords .sheet-text { display: none; }
        .sheet-line.only-chords .sheet-seg { padding-right: .35em; }
        .sheet-comment { margin-top: .5rem; font-size: .8rem; font-style: italic; color: #6b7280; }
        .sheet-seg { position: relative; display: inline-flex; flex-direction: column; }
        /* Lyrics before the first chord keep an empty chord row, so all the text stays on one row. */
        .sheet-chord-space { height: 1.3em; }
        .sheet-text { min-height: 1.3em; color: #374151; white-space: pre; }
        .sheet-chord { position: relative; align-self: flex-start; padding: 0 .2rem; margin-left: -.2rem; border-radius: .25rem; font-weight: 700; color: var(--type-color, #111827); background: none; border: 0; cursor: pointer; line-height: 1.3; white-space: nowrap; }
        .sheet-chord:hover { background: #eef2ff; }
        .sheet-chord:disabled { cursor: default; background: none; }
        .sheet-chord.is-line-start::before { content: ''; position: absolute; left: -.15rem; top: .1rem; bottom: .1rem; width: 3px; border-radius: 2px; background: var(--type-color, #9ca3af); }
        .sheet-chord.is-block-start::before { width: 4px; background: #4f46e5; }
        .sheet-block-name { position: absolute; bottom: 100%; left: -.2rem; padding: 0 .3rem; border-radius: .25rem; background: #4f46e5; color: #fff; font-size: .65rem; font-weight: 700; line-height: 1.35; white-space: nowrap; text-transform: uppercase; }
    </style>

    @include('songs.partials.workspace')

    <div class="ws-page">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="ws-card bg-white shadow sm:rounded-lg layout-page">
                @include('songs.partials.tabs', ['song' => $song, 'current' => 'layout'])
                @include('layouts.alert')

                <div class="ws-desktop">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">
                        {{ $song->title }}
                        @if ($song->isReviewed())
                            <span class="ml-2 text-sm font-semibold" style="color: #15803d;" title="{{ __('Every block has a start time') }}"><i class="fa-solid fa-check"></i> {{ __('Reviewed') }}</span>
                        @endif
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">{{ __('Choose where the chord map breaks lines and blocks, and fix its chords. The lines are shown as in the public page.') }}</p>
                </div>


                @if ($blocks === [] && $sheet === [])
                    <p class="mt-6 text-sm text-gray-500">{{ __('No structure available for this song.') }}</p>
                @else
                    {{-- Bars: gives every chord a duration (one bar each) so the map is edited as a grid of bars --}}
                    <form id="create-bars-form" method="POST" action="{{ route('songs.bars.create', $song) }}" hidden
                        onsubmit="return confirm(@js(__('Create bars for this map? Each chord gets one bar, to be adjusted on the grid. Unsaved changes here are lost.')))">
                        @csrf
                    </form>

                    <form method="POST" action="{{ route('songs.layout.update', $song) }}" class="mt-2 sm:mt-6 space-y-4"
                        x-data="songLayout(@js(['structure' => $blocks, 'sheet' => $sheet, 'key' => $song->musical_key, 'keyPairs' => $keyOptions, 'structureUrl' => route('songs.chord-sheet.structure'), 'youtubeUrl' => $song->youtube_url]))"
                        @submit="submitting = true">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="structure" :value="output">
                        <input type="hidden" name="mode" :value="mode">
                        <input type="hidden" name="musical_key" :value="key ?? ''">
                        <input type="hidden" name="chord_sheet" :value="sheetChanged ? JSON.stringify({ sections: syncedSheet }) : ''">

                        {{-- Toolbar: what a click on a chord does, undo, player, save, options --}}
                        <div class="ws-toolbar">
                            {{-- Narrow screens show the chord sheet or the map, one at a time --}}
                            <span class="ws-seg ws-narrow" x-show="view === 'sheet'" role="group" aria-label="{{ __('View') }}">
                                <button type="button" :class="{ 'is-active': pane === 'sheet' }" @click="pane = 'sheet'" title="{{ __('Chord sheet') }}"><i class="fa-solid fa-align-left"></i><span class="ws-label"> {{ __('Chord sheet') }}</span></button>
                                <button type="button" :class="{ 'is-active': pane === 'map' }" @click="pane = 'map'" title="{{ __('Map') }}"><i class="fa-solid fa-table-cells"></i><span class="ws-label"> {{ __('Map') }}</span></button>
                            </span>
                            <span class="ws-seg" role="group" aria-label="{{ __('Click on a chord to') }}">
                                <button type="button" :class="{ 'is-active': mode === 'line' }" @click="mode = 'line'" title="{{ __('Break line') }}"><i class="fa-solid fa-scissors"></i><span class="ws-label"> {{ __('Break line') }}</span></button>
                                <button type="button" :class="{ 'is-active': mode === 'block' }" @click="mode = 'block'" title="{{ __('Split block') }}"><i class="fa-solid fa-layer-group"></i><span class="ws-label"> {{ __('Split block') }}</span></button>
                                <button type="button" :class="{ 'is-active': mode === 'passing' }" @click="mode = 'passing'" title="{{ __('Passing chord') }}"><i class="fa-solid fa-water"></i><span class="ws-label"> {{ __('Passing chord') }}</span></button>
                                <button type="button" :class="{ 'is-active': mode === 'edit' }" @click="mode = 'edit'" title="{{ __('Edit chord') }}"><i class="fa-solid fa-pen"></i><span class="ws-label"> {{ __('Edit chord') }}</span></button>
                                <button type="button" :class="{ 'is-active': mode === 'time' }" @click="mode = 'time'" title="{{ __('Times') }}"><i class="fa-solid fa-stopwatch"></i><span class="ws-label"> {{ __('Times') }}</span></button>
                            </span>
                            <button type="button" class="ws-btn" @click="addReturn()" title="{{ __('Add "back to the start" after the selected block (or at the end)') }}" aria-label="{{ __('Back to the start') }}"><i class="fa-solid fa-rotate-left"></i><span class="ws-label"> {{ __('Back to the start') }}</span></button>
                            <button type="button" class="ws-btn" @click="undo()" :disabled="!history.length" title="{{ __('Undo') }}" aria-label="{{ __('Undo') }}"><i class="fa-solid fa-rotate-left"></i><span class="ws-label">{{ __('Undo') }}</span></button>
                            <span class="ws-spacer"></span>
                            {{-- Song key: changing it transposes the chords of the map and of the chord sheet --}}
                            <button type="button" class="ws-btn" :class="{ 'is-active': keyOpen }" @click="keyOpen = !keyOpen" title="{{ __('Key') }}">
                                <i class="fa-solid fa-music"></i><span class="ws-label">{{ __('Key') }}:</span> <strong x-text="keyLabel(key)"></strong>
                            </button>
                            @include('songs.partials.play-button', ['toggle' => 'toggleVideo()', 'time' => 'formatVideoTime(videoTime)', 'show' => 'videoId'])
                            <button type="submit" class="ws-btn ws-btn-primary" title="{{ __('Save') }}"><i class="fa-solid fa-floppy-disk"></i><span class="ws-label">{{ __('Save') }}</span></button>
                            <button type="button" class="ws-btn" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" title="{{ __('Options') }}" aria-label="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
                        </div>
                        <p class="ws-note ws-desktop" style="white-space: normal;" x-text="modeHelp"></p>

                        {{-- Song key: one button per key --}}
                        <x-song-options :title="__('Key')" open="keyOpen">
                            @php
                                // One button per pair of relative keys ("C / Am"), storing the key of the song's mode.
                                $minorSong = \App\Enums\MusicalKey::isMinor($song->musical_key);
                                $isListedKey = collect($keyOptions)->contains(fn ($pair) => $pair[$minorSong ? 'minor' : 'major'] === $song->musical_key);
                            @endphp
                            <div class="ws-panel-section">
                                <div class="key-grid">
                                    @if ($song->musical_key && ! $isListedKey)
                                        <button type="button" class="key-chip" :class="{ 'is-active': key === @js($song->musical_key) }" @click="changeKey(@js($song->musical_key)); keyOpen = false">{{ $song->musical_key }}</button>
                                    @endif
                                    @foreach ($keyOptions as $pair)
                                                                <button type="button" class="key-chip" :class="{ 'is-active': key === @js($pair[$minorSong ? 'minor' : 'major']) }" @click="changeKey(@js($pair[$minorSong ? 'minor' : 'major'])); keyOpen = false">{{ $pair['label'] }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ws-panel-section">
                                <p class="ws-panel-help">{{ __('Changing the key transposes the chords of the map and of the chord sheet. Save to keep it.') }}</p>
                            </div>
                        </x-song-options>

                        {{-- Editing one chord (map and, when they match, chord sheet) --}}
                        <x-song-options :title="__('Edit chord')" open="chordEdit">
                            <template x-if="chordEdit"><div>
                            <div class="ws-panel-section">
                                <input type="text" x-ref="chordInput" x-model="chordEdit.value" class="w-full border-gray-300 rounded-md shadow-sm text-lg font-bold"
                                    autocapitalize="off" autocomplete="off" spellcheck="false" @keydown.enter.prevent="renameChord()" :aria-label="@js(__('Chord'))">
                                <p class="mt-1 text-sm" style="color: #dc2626;" x-show="chordEdit && chordEdit.value.trim() && !validChord(chordEdit.value)">{{ __('This is not a chord name.') }}</p>
                                <p class="mt-1 ws-panel-help" x-show="chordEdit && chordEdit.withSheet">{{ __('The chord sheet changes too.') }}</p>
                            </div>
                            <div class="ws-panel-row">
                                <button type="button" class="ws-btn ws-btn-primary" @click="renameChord()" :disabled="!validChord(chordEdit?.value)"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                                <button type="button" class="ws-btn" @click="insertChord()" :disabled="!validChord(chordEdit?.value)"><i class="fa-solid fa-plus"></i> {{ __('Insert after') }}</button>
                                <button type="button" class="ws-btn" style="color: #dc2626;" @click="removeChord()"><i class="fa-solid fa-trash"></i> {{ __('Remove') }}</button>
                            </div>
                            </div></template>
                        </x-song-options>

                        <x-song-options>
                            <div class="ws-panel-section" x-show="hasSheet">
                                <div class="ws-panel-label"><i class="fa-solid fa-eye"></i> {{ __('View') }}</div>
                                <span class="ws-seg" role="group">
                                    <button type="button" :class="{ 'is-active': view === 'sheet' }" @click="view = 'sheet'">{{ __('Chord sheet') }}</button>
                                    <button type="button" :class="{ 'is-active': view === 'chords' }" @click="view = 'chords'">{{ __('Chords only') }}</button>
                                </span>
                            </div>
                            <div class="ws-panel-section">
                                <div class="ws-panel-label"><i class="fa-solid fa-tag"></i> {{ __('Block names') }}</div>
                                <button type="button" class="ws-btn" @click="suggestAll(); optionsOpen = false" :disabled="!hasSuggestions"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Suggest names') }}</button>
                            </div>
                            <div class="ws-panel-section">
                                <div class="ws-panel-label"><i class="fa-solid fa-table-cells"></i> {{ __('Bars') }}</div>
                                <button type="submit" form="create-bars-form" class="ws-btn"><i class="fa-solid fa-table-cells"></i> {{ __('Create bars') }}</button>
                                <p class="ws-panel-help mt-2">{{ __('Edit this map as a grid of bars (the chord of each beat). Each chord starts with one bar; maps saved by the study app already come with bars.') }}</p>
                            </div>
                            @include('songs.partials.video-options')
                            <div class="ws-panel-section">
                                <div class="ws-panel-label"><i class="fa-solid fa-circle-info"></i> {{ __('How it works') }}</div>
                                <p class="ws-panel-help">{{ __('Choose where the chord map breaks lines and blocks, and fix its chords. The lines are shown as in the public page.') }}</p>
                                <p class="ws-panel-help mt-2" x-text="modeHelp"></p>
                            </div>
                        </x-song-options>

                        <datalist id="block-names">
                            <template x-for="name in commonNames" :key="name"><option :value="name"></option></template>
                        </datalist>

                        <x-input-error :messages="$errors->get('structure')" />
                        <p class="text-sm text-green-700" x-show="message" x-text="message"></p>

                        <div class="layout-grid" :class="{ 'has-sheet': view === 'sheet', 'pane-sheet': pane === 'sheet', 'pane-map': pane === 'map' }">
                            {{-- Chord sheet: read like the song is sung, clicks act on the map --}}
                            <div class="sheet-col" x-show="view === 'sheet'">
                                <div class="layout-notice" x-show="!sheetMatches">
                                    <p>{{ __('The chord map does not have the same chords as the chord sheet (it was changed by hand or the chord sheet was imported later), so it cannot be edited over the chord sheet.') }}</p>
                                    <button type="button" class="layout-btn mt-2" @click="buildFromSheet()" :disabled="building">{{ __('Build the map from the chord sheet') }}</button>
                                    <span class="ml-2" x-show="blocks.length">{{ __('Section times already marked are kept.') }}</span>
                                </div>

                                <div x-show="sheetMatches">
                                    <template x-for="(section, s) in sheetSections" :key="s">
                                        <div class="sheet-section">
                                            <div class="sheet-return" x-show="section.jump"><i class="fa-solid fa-rotate-left"></i> {{ __('Back to the start') }}</div>
                                            <div class="sheet-label" x-show="!section.jump" x-text="section.label" @click="selectFromSheet(section.lines[0], $event)"
                                                :class="{ 'is-selected': selected !== null && lineBlockId(section.lines[0]) === selected, 'is-playing': playingBlockId !== null && lineBlockId(section.lines[0]) === playingBlockId }"></div>
                                            <template x-for="(line, l) in section.lines" :key="l">
                                                <div :class="(line.comment !== undefined ? 'sheet-comment' : 'sheet-line') + (line.onlyChords ? ' only-chords' : '') + (selected !== null && lineBlockId(line) === selected ? ' is-selected' : '') + (playingBlockId !== null && lineBlockId(line) === playingBlockId ? ' is-playing' : '')"
                                                    @click="selectFromSheet(line, $event)">
                                                    <span x-show="line.comment !== undefined" x-text="line.comment"></span>
                                                    <template x-for="(unit, u) in (line.units || [])" :key="u">
                                                        <span class="sheet-seg">
                                                            <template x-if="unit.g !== null">
                                                                <button type="button" class="sheet-chord" x-data="{ get info() { return mapInfo.info[unit.g] || {}; } }"
                                                                    :class="{ 'is-line-start': info.lineStart && info.p > 0, 'is-block-start': info.p === 0, 'is-selected': selected !== null && blocks[info.b]?.id === selected, 'is-passing': info.passing }"
                                                                    :style="typeStyle(blocks[info.b]?.section)"
                                                                    :disabled="!canClick(info.b, info.p)"
                                                                    :title="chordTitle(info.b, info.p, info.lineStart)"
                                                                    @click="clickPosition(info.b, info.p)">
                                                                    <span class="sheet-block-name" x-show="info.p === 0" x-text="blocks[info.b]?.section"></span><span x-text="unit.chord"></span>
                                                                </button>
                                                            </template>
                                                            <span class="sheet-chord-space" x-show="unit.g === null"></span>
                                                            <span class="sheet-text" x-text="unit.text"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Chord map, as the public page shows it --}}
                            <div class="layout-map space-y-3">
                                <template x-for="(block, b) in blocks" :key="`${block.id}:${revision}`">
                                    <div class="layout-block" :style="typeStyle(block.section)" :class="{ 'is-selected': selected === block.id, 'is-playing': playingBlockId === block.id, 'is-return': isReturn(block) }" :data-block-card="block.id" @click="selectFromMap(block, $event)">
                                        <template x-if="isReturn(block)">
                                            <div class="flex items-center gap-1" style="flex: 1;">
                                                <span class="return-name" style="flex: 1;"><i class="fa-solid fa-rotate-left"></i> {{ __('Back to the start') }}</span>
                                                <button type="button" class="layout-btn" :disabled="b === 0" @click="moveBlock(b, -1)"
                                                    :title="@js(__('Move up'))" :aria-label="@js(__('Move up'))"><i class="fa-solid fa-arrow-up"></i></button>
                                                <button type="button" class="layout-btn" :disabled="b === blocks.length - 1" @click="moveBlock(b, 1)"
                                                    :title="@js(__('Move down'))" :aria-label="@js(__('Move down'))"><i class="fa-solid fa-arrow-down"></i></button>
                                                <button type="button" class="layout-btn" style="color: #dc2626;" @click="removeBlock(b)" :title="@js(__('Remove'))" :aria-label="@js(__('Remove'))"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </template>
                                        <template x-if="!isReturn(block)"><div>
                                        <div class="layout-block-head">
                                            <input type="text" class="layout-name border-gray-300 rounded-md shadow-sm" x-model="block.section" required
                                                list="block-names" :aria-label="@js(__('Block name'))">
                                            <button type="button" class="layout-time" x-show="blockStart(block) !== null"
                                                @click="videoId ? seekVideo(blockStart(block)) : null" :title="videoId ? @js(__('Play from here')) : ''">
                                                <i class="fa-solid fa-play" x-show="videoId"></i><span x-text="formatVideoTime(blockStart(block))"></span>
                                            </button>
                                            <button type="button" class="layout-suggest" x-show="suggestedNames[b] && suggestedNames[b] !== block.section"
                                                @click="suggest(b)" :title="@js(__('Use the suggested name'))">
                                                <i class="fa-solid fa-wand-magic-sparkles"></i> <span x-text="suggestedNames[b]"></span>
                                            </button>
                                            <span class="layout-tag" x-show="block.breaks">{{ __('Lines set by hand') }}</span>
                                            <button type="button" class="layout-link" x-show="block.breaks" @click="automatic(b)">{{ __('Back to automatic') }}</button>
                                            <span style="flex: 1"></span>
                                            <button type="button" class="layout-btn" @click="duplicateBlock(b)"
                                                :title="@js(__('Duplicate block'))" :aria-label="@js(__('Duplicate block'))"><i class="fa-solid fa-clone"></i></button>
                                            <button type="button" class="layout-btn" :disabled="b === 0" @click="moveBlock(b, -1)"
                                                :title="@js(__('Move up'))" :aria-label="@js(__('Move up'))"><i class="fa-solid fa-arrow-up"></i></button>
                                            <button type="button" class="layout-btn" :disabled="b === blocks.length - 1" @click="moveBlock(b, 1)"
                                                :title="@js(__('Move down'))" :aria-label="@js(__('Move down'))"><i class="fa-solid fa-arrow-down"></i></button>
                                            <button type="button" class="layout-btn" x-show="b > 0 && !isReturn(blocks[b - 1])" @click="mergeWithPrevious(b)"
                                                :title="@js(__('Join to the block above'))" :aria-label="@js(__('Join to the block above'))"><i class="fa-solid fa-object-group"></i></button>
                                            <button type="button" class="layout-btn" style="color: #dc2626;" @click="removeBlock(b)" :title="@js(__('Remove block'))" :aria-label="@js(__('Remove block'))"><i class="fa-solid fa-trash"></i></button>
                                        </div>

                                        {{-- Times mode: start of the block (from the video) --}}
                                        <div class="layout-timing" x-show="mode === 'time'" @click.stop>
                                            <button type="button" class="layout-btn" @click="markStart(b)" :title="@js(__('Mark the start of this block now'))"><i class="fa-solid fa-stopwatch"></i> {{ __('Mark now') }}</button>
                                            <button type="button" class="layout-btn" @click="nudgeStart(b, -1)" :aria-label="@js(__('Minus one second'))">−1s</button>
                                            <input type="text" inputmode="numeric" class="layout-time-input border-gray-300 rounded-md shadow-sm" placeholder="m:ss"
                                                :value="block.data?.start ?? ''" @change="setStart(b, $event.target.value)" :aria-label="`{{ __('Start') }}: ${block.section}`">
                                            <button type="button" class="layout-btn" @click="nudgeStart(b, 1)" :aria-label="@js(__('Plus one second'))">+1s</button>
                                            <button type="button" class="layout-btn" x-show="videoId" @click="seekVideo(blockStart(block))" :disabled="blockStart(block) === null"
                                                :title="@js(__('Play from here'))" :aria-label="@js(__('Play from here'))"><i class="fa-solid fa-play"></i></button>
                                            <button type="button" class="layout-btn" @click="setStart(b, '')" :disabled="blockStart(block) === null"
                                                :title="@js(__('Clear'))" :aria-label="@js(__('Clear'))"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                        <div class="layout-lines">
                                            <template x-for="(line, l) in lines(block)" :key="line.join(',')">
                                                <div class="layout-line">
                                                    <template x-for="position in line" :key="position">
                                                        <button type="button" class="layout-chord" :class="{ 'is-passing': isPassing(block, position) }" x-text="block.chords[position]"
                                                            :disabled="!canClick(b, position)"
                                                            :title="chordTitle(b, position, line[0] === position)"
                                                            @click="clickPosition(b, position)"></button>
                                                    </template>
                                                </div>
                                            </template>
                                            <span class="layout-empty" x-show="!block.chords.length">{{ __('No chords') }}</span>
                                        </div>

                                        <div class="layout-lyrics">
                                            <button type="button" class="layout-link" @click="block.showLyrics = !block.showLyrics"
                                                x-text="(block.showLyrics ? @js(__('Hide lyrics')) : @js(__('Show lyrics'))) + (block.lyrics.trim() ? '' : ' (' + @js(__('empty')) + ')')"></button>
                                            <textarea x-show="block.showLyrics" rows="4" class="mt-1 border-gray-300 rounded-md shadow-sm" x-model="block.lyrics"
                                                @focus="lyricsBefore = block.lyrics" @change="changeLyrics(block, lyricsBefore); lyricsBefore = block.lyrics"
                                                :aria-label="@js(__('Lyrics'))"></textarea>
                                        </div>
                                        </div></template>
                                    </div>
                                </template>
                                <p class="text-sm text-gray-500" x-show="!blocks.length">{{ __('No structure available for this song.') }}</p>
                            </div>
                        </div>

                        {{-- Video to listen to the song while setting the lines (shared small player) --}}
                        @include('songs.partials.youtube-mini', ['id' => 'yt-layout'])

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                            <a href="{{ route('songs.layout.edit', $song) }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900" x-show="history.length">{{ __('Discard changes') }}</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Line breaks are stored as "|" among the section's chords (see chord-lines.js); here each block keeps
        // its chords without them and `breaks`: positions where a line starts, or null for automatic lines.
        // The chord sheet view relies on the map having the same chords, in order, as the chord sheet: chord n
        // of the sheet is chord n of the map.
        const MODES = ['line', 'block', 'passing', 'edit', 'time'];

        function songLayout(config) {
            let nextId = 0;
            const toBlock = (data) => {
                const raw = Array.isArray(data?.chords) ? data.chords.map(String) : [];
                const chords = chordsOnly(raw);
                const breaks = [];
                let position = 0;
                raw.forEach(chord => chord === LINE_BREAK ? breaks.push(position) : position++);
                const lyrics = Array.isArray(data?.lyrics) ? data.lyrics.join('\n') : (typeof data?.lyrics === 'string' ? data.lyrics : '');
                return {
                    id: nextId++,
                    data: data && typeof data === 'object' ? data : {},
                    section: String(data?.section ?? '').replaceAll('_', ' '),
                    chords,
                    breaks: hasLineBreaks(raw) ? normalize(breaks, chords.length) : null,
                    // Positions of the passing chords (line breaks not counted).
                    passing: [...new Set(Array.isArray(data?.passing) ? data.passing : [])]
                        .filter(p => Number.isInteger(p) && p >= 0 && p < chords.length).sort((a, b) => a - b),
                    lyrics,
                    showLyrics: false,
                };
            };
            // Sorted line starts strictly inside the chords; none left means automatic lines.
            const normalize = (breaks, length) => {
                const list = [...new Set(breaks)].filter(p => p > 0 && p < length).sort((a, b) => a - b);
                return list.length ? list : null;
            };

            const lyricText = (text) => String(text).replace(/\s+/g, ' ').trim();
            const hasLyric = (text) => text !== '' && !/^[()|\s]*$/.test(text);
            const isDirective = (line) => /^\s*\{/.test(String(line ?? ''));

            // Chord sheet sections -> { sections, sequence, lyricLines }:
            // - sections: lines as { comment } or { units: [{ chord, text, g }] }, g being the chord's number in the song;
            // - sequence: all chords, in order;
            // - lyricLines: lyric lines with `at`, the number of their first chord or, for a line without chords,
            //   just after the chord before it (so it goes with the block that chord is in).
            const parseSheet = (sheet) => {
                let chordNumber = 0;
                let lastChord = -1;
                const lyricLines = [];
                const sheetLine = (line) => {
                    const text = String(line ?? '');
                    const comment = /^\{\s*(?:c|comment)\s*:\s*(.*?)\s*\}$/i.exec(text.trim());
                    // `ref`: a chord of the line's block (notes go with the chord after them, lyrics without chords
                    // with the chord before them).
                    if (comment) return { comment: comment[1], ref: chordNumber };
                    if (isDirective(text)) return { comment: '', ref: chordNumber };
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
                    units.forEach(unit => unit.g = unit.chord === null ? null : chordNumber++);

                    const chords = units.filter(unit => unit.g !== null);
                    const lyric = lyricText(units.map(unit => unit.text).join(''));
                    if (hasLyric(lyric)) lyricLines.push({ at: chords.length ? chords[0].g : lastChord + 0.5, text: lyric });
                    const ref = chords.length ? chords[0].g : Math.max(lastChord, 0);
                    if (chords.length) lastChord = chords[chords.length - 1].g;
                    return { units, ref, onlyChords: units.every(unit => unit.text.trim() === '') };
                };
                const sections = (Array.isArray(sheet) ? sheet : []).map(section => ({
                    label: section.label || String(section.section ?? '').replaceAll('_', ' '),
                    lines: (section.lines || []).map(sheetLine),
                }));
                const sequence = sections.flatMap(section => section.lines.flatMap(line => (line.units || []).filter(unit => unit.g !== null).map(unit => unit.chord)));
                return { sections, sequence, lyricLines };
            };
            let sheetCache = { key: null, value: null };
            const parsedSheet = (sheet) => {
                const key = JSON.stringify(sheet);
                if (sheetCache.key !== key) sheetCache = { key, value: parseSheet(sheet) };
                return sheetCache.value;
            };

            // Chord sheet without the chords numbered [start, end) (a removed block): lines with only those chords go,
            // with the lyric lines without chords that follow them and the notes right before them; a line shared
            // with another block only loses those chords. Sections left empty go too.
            // Chord sheet split into the blocks of the map (when both have the same chords): each line goes to the
            // block of its first chord (notes with the chord after them, lyrics without chords with the chord before
            // them). A section that did not change keeps its name in the chord sheet; others take the block's name.
            const sectionKey = (name) => String(name).trim().toUpperCase().replace(/\s+/g, '_');
            const sheetByBlocks = (sheet, blocks) => {
                const ends = [];
                blocks.reduce((total, block) => { ends.push(total + block.chords.length); return total + block.chords.length; }, 0);
                const blockOf = (g) => {
                    const index = ends.findIndex(end => g < end);
                    return index === -1 ? blocks.length - 1 : index;
                };
                let chordNumber = 0;
                let lastChord = 0;
                const groups = [];
                sheet.forEach(section => (section.lines || []).forEach((line, i) => {
                    const text = String(line ?? '');
                    let ref;
                    if (isDirective(text)) {
                        ref = chordNumber;
                    } else {
                        const count = [...text.matchAll(/\[([^\]]+)\]/g)].length;
                        ref = count ? chordNumber : lastChord;
                        if (count) lastChord = chordNumber + count - 1;
                        chordNumber += count;
                    }
                    const b = blockOf(ref);
                    const last = groups[groups.length - 1];
                    if (last && last.b === b) {
                        last.lines.push(line);
                    } else {
                        groups.push({ b, lines: [line], from: i === 0 ? section : null });
                    }
                }));
                return groups.map(group => {
                    const block = blocks[group.b];
                    const original = group.from;
                    const unchanged = original && sectionKey(original.section ?? '') === sectionKey(block.section)
                        && original.lines.length === group.lines.length;
                    return unchanged
                        ? { ...original, lines: group.lines }
                        : { section: sectionKey(block.section), label: block.section, lines: group.lines };
                });
            };

            // Chord sheet with chord number `g` (counted over the whole sheet, notes left out) replaced by what
            // `replace(name)` returns: "[New]" to rename it, "" to remove it, "[Old][New]" to add one after it.
            const editSheetChord = (sheet, g, replace) => {
                let number = 0;
                return sheet.map(section => ({
                    ...section,
                    lines: (section.lines || []).map(line => isDirective(line) ? line
                        : String(line ?? '').replace(/\[([^\]]+)\]/g, (match, name) => (number++ === g ? replace(name) : match))),
                }));
            };

            const removeFromSheet = (sheet, start, end) => {
                const inRange = (n) => n >= start && n < end;
                let chordNumber = 0;
                let lastChord = -1;
                return sheet.map(section => {
                    const marks = (section.lines || []).map(line => {
                        const text = String(line ?? '');
                        if (isDirective(text)) return { comment: true, line: text };
                        const numbers = [...text.matchAll(/\[([^\]]+)\]/g)].map(() => chordNumber++);
                        if (!numbers.length) {
                            const at = lastChord + 0.5;
                            return hasLyric(lyricText(text)) && at > start - 1 && at < end ? { remove: true } : { line: text };
                        }
                        lastChord = numbers[numbers.length - 1];
                        const inside = numbers.filter(inRange).length;
                        if (inside === numbers.length) return { remove: true };
                        let index = 0;
                        return { line: inside ? text.replace(/\[[^\]]+\]/g, match => inRange(numbers[index++]) ? '' : match) : text };
                    });
                    const lines = [];
                    marks.forEach((mark, i) => {
                        if (mark.remove) return;
                        if (mark.comment && marks.slice(i + 1).find(next => !next.comment)?.remove) return;
                        lines.push(mark.line);
                    });
                    return { ...section, lines };
                }).filter(section => section.lines.some(line => !isDirective(line) && String(line).trim() !== ''));
            };

            // A chord sheet line with another lyric: each chord stays over the same part of the text (the text before
            // and after what changed keeps its chords; chords inside the changed part are spread over the new text).
            const relyricLine = (line, lyric) => {
                const marks = [];
                let removed = 0;
                for (const match of line.matchAll(/\[([^\]]+)\]/g)) {
                    marks.push({ chord: match[0], at: match.index - removed });
                    removed += match[0].length;
                }
                const old = line.replace(/\[[^\]]+\]/g, '');
                const text = old.match(/^\s*/)[0] + lyric.trim() + old.match(/\s*$/)[0];
                let prefix = 0;
                while (prefix < old.length && prefix < text.length && old[prefix] === text[prefix]) prefix++;
                let suffix = 0;
                while (suffix < old.length - prefix && suffix < text.length - prefix && old[old.length - 1 - suffix] === text[text.length - 1 - suffix]) suffix++;
                const oldMiddle = old.length - prefix - suffix;
                const newMiddle = text.length - prefix - suffix;
                const moved = marks.map(mark => ({
                    chord: mark.chord,
                    at: mark.at <= prefix ? mark.at
                        : mark.at >= old.length - suffix ? mark.at + text.length - old.length
                        : prefix + Math.round((mark.at - prefix) * newMiddle / Math.max(oldMiddle, 1)),
                }));
                let result = '';
                let last = 0;
                moved.forEach(mark => {
                    const at = Math.min(Math.max(mark.at, last), text.length);
                    result += text.slice(last, at) + mark.chord;
                    last = at;
                });
                return result + text.slice(last);
            };

            // Changes [old, new] between two lists of lyric lines: { kind: 'same' | 'remove' | 'add' | 'change', from, to }
            // (`from` an index in the old list, `to` in the new one); removed and added lines next to each other are
            // changed lines.
            const lyricChanges = (before, after) => {
                const lcs = before.map(() => after.map(() => 0));
                const at = (i, j) => (i < before.length && j < after.length ? lcs[i][j] : 0);
                for (let i = before.length - 1; i >= 0; i--) {
                    for (let j = after.length - 1; j >= 0; j--) {
                        lcs[i][j] = before[i] === after[j] ? at(i + 1, j + 1) + 1 : Math.max(at(i + 1, j), at(i, j + 1));
                    }
                }
                const changes = [];
                let removed = [];
                let added = [];
                const flush = () => {
                    removed.forEach((from, k) => changes.push(k < added.length ? { kind: 'change', from, to: added[k] } : { kind: 'remove', from }));
                    added.slice(removed.length).forEach(to => changes.push({ kind: 'add', from: removed.length ? removed[removed.length - 1] : null, to }));
                    removed = [];
                    added = [];
                };
                let i = 0;
                let j = 0;
                while (i < before.length || j < after.length) {
                    if (i < before.length && j < after.length && before[i] === after[j]) {
                        flush();
                        changes.push({ kind: 'same', from: i, to: j });
                        i++;
                        j++;
                    } else if (j >= after.length || (i < before.length && at(i + 1, j) >= at(i, j + 1))) {
                        removed.push(i++);
                    } else {
                        added.push(j++);
                    }
                }
                flush();
                // Added lines with no removed line before them go after the last unchanged line.
                let previous = null;
                return changes.map(change => {
                    if (change.kind === 'add' && change.from === null) change.from = previous;
                    if (change.kind !== 'add') previous = change.from;
                    return change;
                });
            };

            // Chord sheet with the lyrics of the block of chords [start, end) changed from `before` to `after` (texts
            // with one lyric line per line): changed lines keep their chords, removed lines keep only their chords (or
            // go, without chords), added lines come after the line before them. Each old line is looked for, in order,
            // among the block's lyric lines of the chord sheet; `missed` counts the changes that found no place.
            const editSheetLyrics = (sheet, start, end, before, after) => {
                const lines = (text) => String(text).split('\n').map(lyricText).filter(hasLyric);
                const oldLines = lines(before);
                const newLines = String(after).split('\n').map(line => line.trim()).filter(line => hasLyric(lyricText(line)));

                // The block's lines in the chord sheet, as "section:line" keys.
                let chordNumber = 0;
                let lastChord = -1;
                const inBlock = [];
                sheet.forEach((section, s) => (section.lines || []).forEach((line, i) => {
                    const text = String(line ?? '');
                    if (isDirective(text)) return;
                    const count = [...text.matchAll(/\[([^\]]+)\]/g)].length;
                    const at = count ? chordNumber : lastChord + 0.5;
                    if (at > start - 1 && at < end) inBlock.push({ key: `${s}:${i}`, count, lyric: lyricText(text.replace(/\[[^\]]+\]/g, '')) });
                    chordNumber += count;
                    if (count) lastChord = chordNumber - 1;
                }));
                const lyricLines = inBlock.filter(line => hasLyric(line.lyric));
                let pointer = 0;
                const placeOf = oldLines.map(text => {
                    const index = lyricLines.findIndex((line, k) => k >= pointer && line.lyric === text);
                    if (index === -1) return null;
                    pointer = index + 1;
                    return lyricLines[index];
                });
                // A line added before every old line goes after the block's chord lines before its first lyric line,
                // or, when the block has no lyrics yet, after its last line.
                const firstLyric = inBlock.indexOf(placeOf.find(Boolean) ?? lyricLines[0]);
                const top = firstLyric === -1 ? inBlock[inBlock.length - 1] : (firstLyric > 0 ? inBlock[firstLyric - 1] : null);

                const replaced = {};
                const removed = new Set();
                const added = {};
                let missed = 0;
                lyricChanges(oldLines, newLines.map(lyricText)).forEach(change => {
                    if (change.kind === 'same') return;
                    if (change.kind === 'add') {
                        const place = change.from === null ? top : placeOf[change.from];
                        if (!place) return missed++;
                        (added[place.key] ??= []).push(newLines[change.to]);
                        return;
                    }
                    const place = placeOf[change.from];
                    if (!place) return missed++;
                    if (change.kind === 'change') replaced[place.key] = newLines[change.to];
                    else if (place.count) replaced[place.key] = null;
                    else removed.add(place.key);
                });

                const edited = sheet.map((section, s) => ({
                    ...section,
                    lines: (section.lines || []).flatMap((line, i) => {
                        const key = `${s}:${i}`;
                        const text = String(line ?? '');
                        const result = removed.has(key) ? []
                            : !(key in replaced) ? [line]
                            : replaced[key] === null ? [(text.match(/\[[^\]]+\]/g) || []).join(' ')]
                            : [relyricLine(text, replaced[key])];
                        return [...result, ...(added[key] ?? [])];
                    }),
                })).filter(section => section.lines.length);
                return { sheet: edited, missed };
            };

            // Lines and colors of the whole map, recalculated only when the map changes.
            let mapCache = { key: null, value: null };

            const blocks = (Array.isArray(config.structure) ? config.structure : []).map(toBlock);
            const sheet = Array.isArray(config.sheet) ? config.sheet : [];
            const originalSheet = JSON.stringify(sheet);
            const hasSheet = parsedSheet(sheet).sequence.length > 0;

            return withYoutubeMini({
                blocks,
                // Song key (changing it transposes the chords).
                key: config.key || null,
                keyOpen: false,
                // Chord sheet sections as stored (ChordPro lines); only removing a block changes them.
                sheet,
                hasSheet,
                view: hasSheet ? 'sheet' : 'chords',
                mode: MODES.includes(new URLSearchParams(location.search).get('mode')) ? new URLSearchParams(location.search).get('mode') : 'line',
                history: [],
                // Bumped on undo, so the restored blocks are drawn again instead of reusing the elements.
                revision: 0,
                submitting: false,
                building: false,
                message: '',
                // Usual block names, offered while typing a name.
                commonNames: ['INTRO', 'PRIMEIRA PARTE', 'SEGUNDA PARTE', 'PRÉ-REFRÃO', 'REFRÃO', 'PONTE', 'INTERLÚDIO', 'SOLO', 'RIFF', 'FINAL',
                    'VERSE', 'FIRST PART', 'SECOND PART', 'PRE CHORUS', 'CHORUS', 'BRIDGE', 'INTERLUDE', 'TAG', 'OUTRO'],
                // Block highlighted in the map and in the chord sheet (its id).
                selected: null,
                optionsOpen: false,
                // Lyrics of the block being typed, as they were when the field got focus.
                lyricsBefore: '',
                // Narrow screens: the chord sheet or the map.
                pane: 'sheet',

                init() {
                    window.addEventListener('beforeunload', event => {
                        if (this.history.length && !this.submitting) event.preventDefault();
                    });
                    this.$nextTick(() => this.initVideo('yt-layout'));
                    // While the video plays, the block being played is kept in view.
                    let lastPlaying = null;
                    setInterval(() => {
                        const id = this.playingBlockId;
                        if (this.playing && id !== null && id !== lastPlaying) this.scrollToPlaying(id);
                        lastPlaying = id;
                    }, 250);
                },
                // Start of a block in seconds ("start" of the structure, "m:ss"), or null.
                blockStart(block) {
                    const value = block.data?.start;
                    if (typeof value === 'number') return value;
                    if (typeof value !== 'string' || !/^\d+(:\d{1,2}){0,2}(\.\d+)?$/.test(value.trim())) return null;
                    return value.trim().split(':').reduce((total, part) => total * 60 + Number(part), 0);
                },
                // Block being played: the one with the latest start not after the video time (none before playing).
                get playingBlockId() {
                    if (!this.playerReady || (!this.playing && this.videoTime === 0)) return null;
                    let found = null;
                    this.blocks.forEach(block => {
                        const start = this.blockStart(block);
                        if (start !== null && start <= this.videoTime + 0.2) found = block.id;
                    });
                    return found;
                },
                scrollToPlaying(id) {
                    this.$nextTick(() => {
                        const line = this.$root.querySelector('.sheet-line.is-playing, .sheet-comment.is-playing');
                        if (line && line.offsetParent) line.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        const card = this.$root.querySelector(`[data-block-card="${id}"]`);
                        if (card && card.offsetParent) card.scrollIntoView({ block: line?.offsetParent ? 'nearest' : 'center', behavior: 'smooth' });
                    });
                },
                formatVideoTime(seconds) {
                    const total = Math.max(0, Math.round(seconds || 0));
                    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
                },

                // The chord sheet as it will be saved: following the map's blocks when both have the same chords.
                get syncedSheet() { return this.sheetMatches ? sheetByBlocks(this.sheet, this.blocks) : this.sheet; },
                // Sections shown, with the "back to the start" markers after the blocks before them.
                get sheetSections() {
                    const sections = parsedSheet(this.syncedSheet).sections;
                    if (!this.sheetMatches) return sections;
                    const markers = this.blocks.map((block, b) => (this.isReturn(block) ? b : null)).filter(b => b !== null);
                    const result = [];
                    sections.forEach(section => {
                        const b = this.mapInfo.info[section.lines[0]?.ref]?.b ?? -1;
                        while (markers.length && markers[0] < b) result.push({ jump: 'start', label: '', lines: [], marker: markers.shift() });
                        result.push(section);
                    });
                    markers.forEach(() => result.push({ jump: 'start', label: '', lines: [] }));
                    return result;
                },
                get sheetMatches() {
                    return this.hasSheet && JSON.stringify(this.blocks.flatMap(block => block.chords)) === JSON.stringify(parsedSheet(this.sheet).sequence);
                },
                get sheetChanged() { return JSON.stringify(this.syncedSheet) !== originalSheet; },

                raw(block) {
                    if (!block.breaks) return [...block.chords];
                    const raw = [];
                    block.chords.forEach((chord, position) => {
                        if (block.breaks.includes(position)) raw.push(LINE_BREAK);
                        raw.push(chord);
                    });
                    return raw;
                },

                // { lines: [block][line] -> positions, info: [chord number] -> { b, p, l, lineStart, passing } }
                get mapInfo() {
                    const raw = this.blocks.map(block => this.raw(block));
                    const passing = this.blocks.map(block => block.passing || []);
                    const key = JSON.stringify([raw, passing]);
                    if (mapCache.key === key) return mapCache.value;

                    const cycles = songCyclesOf(raw, passing);
                    const lines = [];
                    const info = [];
                    let offset = 0;
                    raw.forEach((chords, b) => {
                        lines[b] = chordSegments(chords, cycles, passing[b]).map(line => line.flatMap(step => step.positions));
                        lines[b].forEach((line, l) => line.forEach((p, i) => {
                            info[offset + p] = { b, p, l, lineStart: i === 0, passing: passing[b].includes(p) };
                        }));
                        offset += this.blocks[b].chords.length;
                    });
                    mapCache = { key, value: { lines, info } };
                    return mapCache.value;
                },

                // Lines of a block as lists of chord positions, as the public page splits them.
                // Blocks are passed as objects (not indexes), which stay valid while blocks are split or joined.
                lines(block) {
                    const b = this.blocks.indexOf(block);
                    return b === -1 ? [] : (this.mapInfo.lines[b] ?? []);
                },
                // Color of a block by its kind (intro, chorus...), as on the public page.
                typeStyle(name) { return `--type-color: var(--type-${blockType(name)})`; },
                // Current lines turned into breaks set by hand, so a first click keeps what is on screen.
                currentBreaks(block) {
                    return this.lines(block).slice(1).map(line => line[0]);
                },

                get modeHelp() {
                    return {
                        edit: @js(__('Click a chord to rename it, add a chord after it or remove it.')),
                        time: @js(__('Play the video and press "Mark now" on a block when it starts; adjust with −1s / +1s.')),
                        line: @js(__('Click a chord to start a new line on it; click the first chord of a line to join it to the line above.')),
                        block: @js(__('Click a chord to start a new block on it; click the first chord of a block to join it to the block above.')),
                        passing: @js(__('Click a chord to mark it as a passing chord (underlined); click it again to unmark it. Passing chords do not count when looking for repetitions.')),
                    }[this.mode];
                },
                isPassing(block, position) { return (block.passing || []).includes(position); },
                // The first chord of the song does nothing; the first chord of a block only joins blocks (any chord
                // can be marked as passing).
                canClick(b, position) {
                    if (b === undefined || this.mode === 'time') return false;
                    return position > 0 || this.mode === 'passing' || this.mode === 'edit' || (this.mode === 'block' && b > 0 && !this.isReturn(this.blocks[b - 1]));
                },
                chordTitle(b, position, isLineStart) {
                    if (!this.canClick(b, position)) return '';
                    if (this.mode === 'passing') return this.isPassing(this.blocks[b], position) ? @js(__('Not a passing chord')) : @js(__('Mark as passing chord'));
                    if (this.mode === 'edit') return @js(__('Edit chord'));
                    if (this.mode === 'block') return position === 0 ? @js(__('Join to the block above')) : @js(__('Start a new block here'));
                    return isLineStart ? @js(__('Join to the line above')) : @js(__('Start a new line here'));
                },
                clickPosition(b, position) {
                    if (!this.canClick(b, position)) return;
                    if (this.mode === 'edit') return this.openChordEdit(b, position);
                    if (this.mode === 'passing') {
                        this.remember();
                        const block = this.blocks[b];
                        block.passing = this.isPassing(block, position)
                            ? block.passing.filter(p => p !== position)
                            : [...(block.passing || []), position].sort((x, y) => x - y);
                        return;
                    }
                    if (this.mode === 'block') {
                        position === 0 ? this.mergeWithPrevious(b) : this.split(b, position);
                        return;
                    }
                    const isLineStart = this.lines(this.blocks[b]).some(line => line[0] === position);
                    this.toggleBreak(b, position, isLineStart);
                },

                remember() {
                    this.history.push(JSON.stringify({ blocks: this.blocks, sheet: this.sheet, key: this.key }));
                    if (this.history.length > 50) this.history.shift();
                    this.message = '';
                },
                undo() {
                    if (!this.history.length) return;
                    const state = JSON.parse(this.history.pop());
                    this.blocks = state.blocks;
                    this.sheet = state.sheet;
                    this.key = state.key;
                    this.revision++;
                },

                // "C / Am" for C or Am (see MusicalKey::pairs); other keys as they are.
                keyLabel(key) {
                    const pair = config.keyPairs.find(item => item.major === key || item.minor === key);
                    return pair ? pair.label : (key || '—');
                },
                // New key for the song: the chords of the map and the [Chord] marks of the chord sheet are transposed
                // (a minor key counts as its relative major, so Am -> C changes no chord; see chord-transposer.js).
                // Saved with the layout.
                changeKey(to) {
                    const from = this.key;
                    if (!to || to === from) return;
                    this.remember();
                    this.key = to;
                    this.chordEdit = false;
                    if (!from || !ChordTransposer.parseKey(from) || !ChordTransposer.parseKey(to)) {
                        this.message = @js(__('Key set to :key. Save to keep it.')).replace(':key', to);
                        return;
                    }
                    const chord = (name) => ChordTransposer.transposeChord(name, from, to);
                    let inMap = 0;
                    this.blocks.forEach(block => {
                        block.chords = block.chords.map(name => {
                            const moved = chord(name);
                            if (moved !== name) inMap++;
                            return moved;
                        });
                    });
                    let inSheet = 0;
                    this.sheet = this.sheet.map(section => ({
                        ...section,
                        lines: (section.lines || []).map(line => isDirective(line) ? line : String(line ?? '').replace(/\[([^\]]+)\]/g, (match, name) => {
                            const moved = chord(name);
                            if (moved !== name) inSheet++;
                            return `[${moved}]`;
                        })),
                    }));
                    this.revision++;
                    this.message = inMap || inSheet
                        ? @js(__('Chords transposed from :from to :to (structure: :structure, chord sheet: :sheet). Review and save.'))
                            .replace(':from', from).replace(':to', to).replace(':structure', inMap).replace(':sheet', inSheet)
                        : @js(__(':from and :to are relative keys (same chords): only the key changes.')).replace(':from', from).replace(':to', to);
                },

                toggleBreak(b, position, isLineStart) {
                    this.remember();
                    const block = this.blocks[b];
                    const breaks = this.currentBreaks(block).filter(p => p !== position);
                    if (!isLineStart) breaks.push(position);
                    // Joining every line leaves no break to store: the block goes back to automatic lines.
                    block.breaks = normalize(breaks, block.chords.length);
                },
                automatic(b) {
                    this.remember();
                    this.blocks[b].breaks = null;
                },
                // Where the block's lyrics are cut when it is split at `position`: the index of the first lyric line
                // of the new block, null when it gets no lyrics, or false when the lyrics do not match the chord sheet.
                lyricsSplitIndex(b, position) {
                    if (!this.sheetMatches) return false;
                    const block = this.blocks[b];
                    const start = this.blocks.slice(0, b).reduce((total, item) => total + item.chords.length, 0);
                    const end = start + block.chords.length;
                    const split = start + position;
                    const inBlock = parsedSheet(this.sheet).lyricLines.filter(line => line.at > start - 1 && line.at < end);
                    const moving = inBlock.filter(line => line.at >= split);
                    if (!moving.length) return null;

                    const lines = block.lyrics.split('\n');
                    const staying = inBlock.length - moving.length;
                    if (lyricText(lines[staying] ?? '') === moving[0].text) return staying;
                    // The block's lyrics were edited: look for the line where the new block starts.
                    const index = lines.findIndex(line => lyricText(line) === moving[0].text);
                    return index === -1 ? false : index;
                },
                // The new block takes the chords from `position` on and, when the chord sheet shows where they are
                // sung, the lyrics that go with them.
                split(b, position) {
                    this.remember();
                    const block = this.blocks[b];
                    const breaks = block.breaks ?? this.currentBreaks(block);
                    const lyricsAt = block.lyrics.trim() ? this.lyricsSplitIndex(b, position) : null;
                    const lines = block.lyrics.split('\n');
                    const second = {
                        id: nextId++,
                        data: {},
                        section: block.section,
                        chords: block.chords.slice(position),
                        breaks: block.breaks ? normalize(breaks.map(p => p - position), block.chords.length - position) : null,
                        passing: (block.passing || []).filter(p => p >= position).map(p => p - position),
                        lyrics: typeof lyricsAt === 'number' ? lines.slice(lyricsAt).join('\n').trim() : '',
                        showLyrics: false,
                    };
                    block.chords = block.chords.slice(0, position);
                    block.breaks = block.breaks ? normalize(breaks, position) : null;
                    block.passing = (block.passing || []).filter(p => p < position);
                    if (typeof lyricsAt === 'number') {
                        block.lyrics = lines.slice(0, lyricsAt).join('\n').trim();
                        // An anchor taken from lyrics that moved away is recalculated when saved.
                        if (lyricsAt === 0) block.data = { ...block.data, anchor: null };
                    }
                    this.blocks.splice(b + 1, 0, second);
                    if (lyricsAt === false) this.message = @js(__('The lyrics could not be split automatically: they stayed in the first block.'));
                },
                // Block of the map that a chord sheet line belongs to (its id), when the map matches the chord sheet.
                lineBlockId(line) {
                    if (!line || line.ref === undefined || !this.sheetMatches) return null;
                    const b = this.mapInfo.info[line.ref]?.b;
                    return b === undefined ? null : (this.blocks[b]?.id ?? null);
                },
                // Clicking a block (not its buttons or fields) highlights it and shows its part of the chord sheet.
                selectFromMap(block, event) {
                    if (event.target.closest('button, input, textarea, a')) return;
                    this.selected = this.selected === block.id ? null : block.id;
                    if (this.selected === null || this.view !== 'sheet') return;
                    this.$nextTick(() => this.$root.querySelector('.sheet-line.is-selected, .sheet-comment.is-selected')
                        ?.scrollIntoView({ block: 'center', behavior: 'smooth' }));
                },
                // Clicking a chord sheet line (not a chord) highlights the block of the map it belongs to.
                selectFromSheet(line, event) {
                    if (event.target.closest('button')) return;
                    const id = this.lineBlockId(line);
                    if (id === null) return;
                    this.selected = this.selected === id ? null : id;
                    if (this.selected === null) return;
                    this.$nextTick(() => this.$root.querySelector(`[data-block-card="${id}"]`)
                        ?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                },

                // Names that follow the ones already given: a block with the same content (lyrics, or chords when it
                // has no lyrics) as an earlier block gets that block's name; a block keeps its own name unless the block
                // right before it has that name with different content (a block just split). Only those get an automatic name,
                // in the language of the song's names: the most repeated lyrics are the chorus; repeated lyrics always
                // right before it, the pre-chorus; other repeated lyrics first heard after it, the bridge; other lyrics,
                // parts in order; no lyrics: intro (first), final (last) or interlude.
                get suggestedNames() {
                    const all = this.blocks;
                    const blocks = all.filter(block => !this.isReturn(block));
                    const nameKey = (name) => String(name ?? '').trim().toUpperCase();
                    const plain = (name) => String(name ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                    const portuguese = blocks.some(block => /refrao|parte|ponte|estrofe|verso|interludio|primeira|segunda|terceira/.test(plain(block.section)));
                    const words = portuguese
                        ? { intro: 'INTRO', final: 'FINAL', interlude: 'INTERLÚDIO', chorus: 'REFRÃO', prechorus: 'PRÉ-REFRÃO', bridge: 'PONTE',
                            parts: ['PRIMEIRA PARTE', 'SEGUNDA PARTE', 'TERCEIRA PARTE'], part: n => `PARTE ${n}` }
                        : { intro: 'INTRO', final: 'FINAL', interlude: 'INTERLUDE', chorus: 'CHORUS', prechorus: 'PRE CHORUS', bridge: 'BRIDGE',
                            parts: ['FIRST PART', 'SECOND PART', 'THIRD PART'], part: n => `PART ${n}` };

                    // Automatic names by how the lyrics repeat.
                    const lyrics = blocks.map(block => lyricText(block.lyrics).toLowerCase());
                    const counts = {};
                    lyrics.filter(Boolean).forEach(key => counts[key] = (counts[key] ?? 0) + 1);
                    const repeated = Object.keys(counts).filter(key => counts[key] > 1).sort((a, b) => counts[b] - counts[a]);
                    const chorus = repeated[0] ?? null;
                    const firstChorus = chorus === null ? -1 : lyrics.indexOf(chorus);
                    const preChorus = repeated.find(key => key !== chorus
                        && lyrics.every((item, i) => item !== key || lyrics[i + 1] === chorus)) ?? null;
                    const bridge = repeated.find(key => key !== chorus && key !== preChorus && lyrics.indexOf(key) > firstChorus) ?? null;
                    const parts = [];
                    const last = blocks.length - 1;
                    const automatic = lyrics.map((key, i) => {
                        if (key === '') return i === 0 ? words.intro : (i === last ? words.final : words.interlude);
                        if (key === chorus) return words.chorus;
                        if (key === preChorus) return words.prechorus;
                        if (key === bridge) return words.bridge;
                        if (!parts.includes(key)) parts.push(key);
                        const n = parts.indexOf(key);
                        return words.parts[n] ?? words.part(n + 1);
                    });

                    const contents = blocks.map((block, i) => lyrics[i] ? 'l:' + lyrics[i] : (block.chords.length ? 'c:' + block.chords.join(' ') : ''));
                    const result = [];
                    blocks.forEach((block, i) => {
                        const content = contents[i];
                        const first = content ? contents.indexOf(content) : -1;
                        if (first !== -1 && first < i) {
                            result.push(result[first]);
                            return;
                        }
                        // A name repeated right after a block with different content (a block just split in two).
                        const own = String(block.section ?? '').trim();
                        const taken = own && i > 0 && nameKey(blocks[i - 1].section) === nameKey(own) && contents[i - 1] !== content;
                        result.push(!own || taken ? automatic[i] : own);
                    });
                    let next = 0;
                    return all.map(block => (this.isReturn(block) ? block.section : result[next++]));
                },
                get hasSuggestions() { return this.suggestedNames.some((name, i) => name !== this.blocks[i].section); },
                suggest(b) {
                    this.remember();
                    this.blocks[b].section = this.suggestedNames[b];
                },
                suggestAll() {
                    this.remember();
                    const names = this.suggestedNames;
                    this.blocks.forEach((block, i) => block.section = names[i]);
                },

                // ---- Editing a chord: rename, add one after it, remove it ----
                // When the map and the chord sheet have the same chords, the chord sheet changes in the same place.
                chordEdit: false,
                validChord(name) {
                    const text = String(name ?? '').trim();
                    return text !== '' && !/[\s\[\]|]/.test(text) && ChordTransposer.chordFamily(text) !== null;
                },
                openChordEdit(b, position) {
                    const g = this.blocks.slice(0, b).reduce((total, item) => total + item.chords.length, 0) + position;
                    this.chordEdit = { b, position, g, value: this.blocks[b].chords[position], withSheet: this.sheetMatches };
                    this.$nextTick(() => this.$refs.chordInput?.select());
                },
                // Positions after `position` move by `delta` (line breaks and passing chords follow their chords).
                shiftMarks(block, position, delta) {
                    const shift = (list) => list.map(p => p > position ? p + delta : p);
                    if (block.breaks) block.breaks = normalize(shift(block.breaks), block.chords.length);
                    block.passing = shift(block.passing || []).filter(p => p >= 0 && p < block.chords.length);
                },
                renameChord() {
                    const edit = this.chordEdit;
                    const name = String(edit?.value ?? '').trim();
                    if (!this.validChord(name)) return;
                    this.remember();
                    this.blocks[edit.b].chords[edit.position] = name;
                    if (edit.withSheet) this.sheet = editSheetChord(this.sheet, edit.g, () => `[${name}]`);
                    this.chordEdit = false;
                },
                insertChord() {
                    const edit = this.chordEdit;
                    const name = String(edit?.value ?? '').trim();
                    if (!this.validChord(name)) return;
                    this.remember();
                    const block = this.blocks[edit.b];
                    block.chords.splice(edit.position + 1, 0, name);
                    this.shiftMarks(block, edit.position, 1);
                    if (edit.withSheet) this.sheet = editSheetChord(this.sheet, edit.g, (old) => `[${old}][${name}]`);
                    this.chordEdit = false;
                },
                removeChord() {
                    const edit = this.chordEdit;
                    const block = this.blocks[edit.b];
                    if (block.chords.length === 1) {
                        this.message = @js(__('A block needs at least one chord: remove the block instead.'));
                        this.chordEdit = false;
                        return;
                    }
                    this.remember();
                    block.passing = (block.passing || []).filter(p => p !== edit.position);
                    block.chords.splice(edit.position, 1);
                    this.shiftMarks(block, edit.position, -1);
                    if (edit.withSheet) this.sheet = editSheetChord(this.sheet, edit.g, () => '');
                    this.chordEdit = false;
                },

                // ---- Duplicating a block (e.g. a chorus played again), then moving it with the arrows ----
                // Chord sheet split into the map's blocks (one section per block), or null when that is not possible.
                // Blocks without chords (as a "back to the start" marker) have no lines: null.
                sheetPerBlock() {
                    if (!this.sheetMatches) return null;
                    const sections = sheetByBlocks(this.sheet, this.blocks);
                    if (sections.length !== this.blocks.filter(block => block.chords.length).length) return null;
                    let next = 0;
                    return this.blocks.map(block => (block.chords.length ? sections[next++] : null));
                },
                // The copy goes right below: chords, lines, passing chords, name and lyrics (not the start time) and, when
                // the chord sheet has the same chords as the map, the chord sheet lines of the block too.
                duplicateBlock(b) {
                    const block = this.blocks[b];
                    const sheet = this.sheetPerBlock();
                    this.remember();
                    if (sheet) {
                        sheet.splice(b + 1, 0, JSON.parse(JSON.stringify(sheet[b])));
                        this.sheet = sheet.filter(Boolean);
                    }
                    this.blocks.splice(b + 1, 0, {
                        id: nextId++,
                        data: { anchor: block.data?.anchor ?? null },
                        section: block.section,
                        chords: [...block.chords],
                        breaks: block.breaks ? [...block.breaks] : null,
                        passing: [...(block.passing || [])],
                        lyrics: block.lyrics,
                        showLyrics: false,
                    });
                    this.revision++;
                    this.message = sheet || !this.hasSheet ? @js(__('Block duplicated below: move it with the arrows.'))
                        : @js(__('Block duplicated in the map only: the chord sheet did not change.'));
                },

                // Moves a block up (-1) or down (1), with its chord sheet lines when map and chord sheet match block by block.
                moveBlock(b, delta) {
                    const to = b + delta;
                    if (to < 0 || to >= this.blocks.length) return;
                    const sheet = this.sheetPerBlock();
                    this.remember();
                    if (sheet) {
                        [sheet[b], sheet[to]] = [sheet[to], sheet[b]];
                        this.sheet = sheet.filter(Boolean);
                    }
                    [this.blocks[b], this.blocks[to]] = [this.blocks[to], this.blocks[b]];
                    this.revision++;
                    this.message = sheet || !this.hasSheet ? '' : @js(__('Block moved in the map only: the chord sheet did not change.'));
                    const id = this.blocks[to].id;
                    this.$nextTick(() => document.querySelector(`[data-block-card="${id}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                },

                // Removes the block from the map and, when the chord sheet has the same chords, its part of the chord sheet.
                removeBlock(b) {
                    const block = this.blocks[b];
                    if (this.isReturn(block)) {
                        this.remember();
                        this.blocks.splice(b, 1);
                        this.revision++;
                        return;
                    }
                    const withSheet = this.sheetMatches && block.chords.length > 0;
                    const question = withSheet
                        ? @js(__('Remove the block ":name" from the map and the chord sheet?'))
                        : @js(__('Remove the block ":name" from the map?'));
                    if (!confirm(question.replace(':name', block.section))) return;

                    this.remember();
                    if (withSheet) {
                        const start = this.blocks.slice(0, b).reduce((total, item) => total + item.chords.length, 0);
                        this.sheet = removeFromSheet(this.sheet, start, start + block.chords.length);
                    }
                    this.blocks.splice(b, 1);
                    this.revision++;
                },
                // ---- Times mode: the start of each block ("start" of the structure, "m:ss"), as on the Times page ----
                // Marks the block's start at the video time.
                markStart(b) {
                    if (b < 0 || !this.blocks[b] || this.isReturn(this.blocks[b])) return;
                    if (!this.playerReady) {
                        this.message = @js(__('Turn on the video to mark the times.'));
                        return;
                    }
                    this.setStart(b, this.formatVideoTime(this.videoCurrentTime()));
                },
                nudgeStart(b, delta) {
                    const current = this.blockStart(this.blocks[b]) ?? (this.playerReady ? this.videoCurrentTime() : 0);
                    this.setStart(b, this.formatVideoTime(Math.max(0, current + delta)));
                },
                // Typed or marked start: "m:ss" (or seconds), empty to clear it.
                setStart(b, value) {
                    const block = this.blocks[b];
                    const text = String(value ?? '').trim();
                    const valid = /^\d+(:\d{1,2}){0,2}(\.\d+)?$/.test(text);
                    if (text !== '' && !valid) {
                        this.message = @js(__('Use the m:ss format (e.g. 1:05).'));
                        this.revision++;
                        return;
                    }
                    const start = text === '' ? null : this.formatVideoTime(text.split(':').reduce((total, part) => total * 60 + Number(part), 0));
                    if ((block.data?.start ?? null) === start) return;
                    this.remember();
                    const data = { ...block.data };
                    if (start === null) delete data.start; else data.start = start;
                    block.data = data;
                },
                // "Back to the start": a block without chords marking that the song goes back to its first block.
                isReturn(block) { return block?.data?.jump === 'start'; },
                addReturn() {
                    const selected = this.blocks.findIndex(block => block.id === this.selected);
                    const at = selected === -1 ? this.blocks.length : selected + 1;
                    this.remember();
                    const marker = toBlock({ section: @js(mb_strtoupper(__('Back to the start'))), jump: 'start', chords: [] });
                    this.blocks.splice(at, 0, marker);
                    this.revision++;
                    this.$nextTick(() => document.querySelector(`[data-block-card="${marker.id}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                },
                // Lyrics typed in a block (applied when the field loses focus): the block's lines of the chord sheet
                // get the same lyrics, with their chords, when the chord sheet has the same chords as the map. An anchor
                // that was the first lyric line follows it.
                changeLyrics(block, before) {
                    const after = block.lyrics;
                    if (after === before) return;
                    block.lyrics = before;
                    this.remember();
                    block.lyrics = after;

                    const firstLine = (text) => String(text).split('\n').map(lyricText).find(hasLyric) ?? null;
                    if ((block.data?.anchor ?? null) !== null && lyricText(block.data.anchor) === firstLine(before)) {
                        block.data = { ...block.data, anchor: firstLine(after) };
                    }

                    if (!this.hasSheet) return;
                    if (!this.sheetMatches) {
                        this.message = @js(__('Lyrics changed in the map only: the chord sheet has other chords.'));
                        return;
                    }
                    const b = this.blocks.indexOf(block);
                    const start = this.blocks.slice(0, b).reduce((total, item) => total + item.chords.length, 0);
                    const { sheet, missed } = editSheetLyrics(this.sheet, start, start + block.chords.length, before, after);
                    this.sheet = sheet;
                    this.message = missed ? @js(__('Some lyric lines were not found in the chord sheet: change them there by hand.')) : '';
                },
                // The joined block keeps the lines both had, with a line break where they meet.
                mergeWithPrevious(b) {
                    if (this.isReturn(this.blocks[b]) || this.isReturn(this.blocks[b - 1])) return;
                    this.remember();
                    const first = this.blocks[b - 1];
                    const second = this.blocks[b];
                    const offset = first.chords.length;
                    const breaks = [...this.currentBreaks(first), offset, ...this.currentBreaks(second).map(p => p + offset)];
                    first.chords = [...first.chords, ...second.chords];
                    first.breaks = normalize(breaks, first.chords.length);
                    first.passing = [...(first.passing || []), ...(second.passing || []).map(p => p + offset)];
                    first.lyrics = [first.lyrics.trim(), second.lyrics.trim()].filter(Boolean).join('\n');
                    this.blocks.splice(b, 1);
                },

                // New map from the chord sheet (one block per section, one line per chord sheet line), keeping the
                // section times already marked; it is only stored when saved.
                async buildFromSheet() {
                    this.building = true;
                    try {
                        const response = await fetch(config.structureUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': this.$root.querySelector('input[name=_token]').value,
                            },
                            body: JSON.stringify({ chord_sheet: JSON.stringify({ sections: this.sheet }), structure: this.output }),
                        });
                        if (!response.ok) throw new Error();
                        const { structure } = await response.json();
                        this.remember();
                        this.blocks = structure.map(toBlock);
                        this.revision++;
                        this.message = @js(__('Map built from the chord sheet. Review it and save.'));
                    } catch (error) {
                        this.message = '';
                        alert(@js(__('Something went wrong')));
                    } finally {
                        this.building = false;
                    }
                },

                get output() {
                    return JSON.stringify(this.blocks.map((block, index) => ({
                        ...block.data,
                        order: index + 1,
                        section: block.section,
                        chords: this.raw(block),
                        passing: block.passing || [],
                        lyrics: block.lyrics,
                    })));
                },
            }, config.youtubeUrl);
        }
    </script>
</x-app-layout>
