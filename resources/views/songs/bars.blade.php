<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Layout') }} · {{ $song->title }}
        </h2>
    </x-slot>

    <script src="{{ asset('js/chord-transposer.js') }}?v={{ filemtime(public_path('js/chord-transposer.js')) }}"></script>
    <script src="{{ asset('js/chord-lines.js') }}?v={{ filemtime(public_path('js/chord-lines.js')) }}"></script>

    <style>
        .bars-page { --type-intro: #0369a1; --type-verse: #047857; --type-prechorus: #b45309; --type-chorus: #be123c; --type-bridge: #6d28d9; --type-instrumental: #4338ca; --type-ending: #475569; --type-other: #111827; }
        /* Grid of bars next to the chord sheet; on phones one of them at a time (tabs) */
        .bars-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; margin-top: .75rem; }
        @media (min-width: 1024px) { .bars-layout { grid-template-columns: minmax(0, 1fr) minmax(18rem, 26rem); } }
        @media (max-width: 1023px) {
            .bars-layout.pane-bars .sheet-col, .bars-layout.pane-sheet .bars-col { display: none; }
        }
        .bars-col { container-type: inline-size; }
        @media (min-width: 1024px) { .pane-tabs { display: none !important; } }
        .bars-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem; }
        @container (min-width: 34rem) { .bars-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @container (min-width: 46rem) { .bars-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

        .bar { position: relative; min-width: 0; padding: .3rem .4rem .45rem; border: 1px solid #e5e7eb; border-radius: .5rem; background: #fff; cursor: pointer; user-select: none; -webkit-touch-callout: none; }
        .bar:hover { border-color: #c7d2fe; }
        .bar.is-current { border-color: #f59e0b; box-shadow: 0 0 0 2px #f59e0b inset; background: #fffbeb; }
        .bar.has-block { border-top: 3px solid var(--type-color, #4f46e5); }
        .bar .number { position: absolute; top: .15rem; right: .35rem; font-size: .62rem; color: #9ca3af; }
        .bar-blocks { display: flex; flex-wrap: wrap; gap: .25rem; min-height: 1.1rem; margin-right: 1.3rem; }
        .block-chip { display: inline-flex; align-items: center; gap: .2rem; max-width: 100%; padding: 0 .3rem; border-radius: .3rem; font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--type-color, #4f46e5); background: #f9fafb; border: 0; cursor: pointer; }
        .block-chip .play { color: #6b7280; }
        .block-chip.is-marker { color: #6b7280; }
        .bar-beats { display: grid; gap: .15rem; margin-top: .25rem; }
        .beat { display: flex; flex-direction: column; min-width: 0; padding: .1rem .2rem; border-radius: .3rem; border-left: 1px solid #f3f4f6; }
        .beat:first-child { border-left: 0; }
        .beat .halves { display: flex; gap: .2rem; align-items: baseline; min-width: 0; }
        .beat .chord { font-weight: 800; font-size: 1.02rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; color: #111827; }
        .beat .chord.is-long { font-size: .8rem; }
        .beat .chord.is-repeat { color: #9ca3af; font-weight: 400; }
        .beat .chord.is-and { font-size: .85rem; border-left: 1px dashed #9ca3af; padding-left: .2rem; }
        .beat .count { font-size: .6rem; color: #9ca3af; }
        /* Narrow grid (phones, 2 bars per line): smaller chords so names like Am7 fit in a beat */
        @container (max-width: 34rem) {
            .beat { padding: .1rem; }
            .beat .chord { font-size: .82rem; letter-spacing: -.02em; }
            .beat .chord.is-long, .beat .chord.is-and { font-size: .66rem; }
            .bar { padding: .25rem .3rem .4rem; }
        }
        /* Marked bars (click, then Shift + click) and the passage being repeated */
        .bar.is-selected { background: #eef2ff; border-color: #a5b4fc; }
        .bar.is-looped { box-shadow: 0 0 0 2px #6366f1 inset; }
        .bar.is-current.is-looped { box-shadow: 0 0 0 2px #f59e0b inset, 0 0 0 4px #6366f1 inset; }
        .passage-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-top: .4rem; font-size: .8rem; color: #4b5563; }
        .passage-bar .ws-btn { min-height: 32px; padding: 0 .55rem; font-size: .8rem; }
        .popover hr { margin: .5rem 0 .1rem; border: 0; border-top: 1px solid #e5e7eb; }
        /* Lines view: the map's chords in lines as on the public page; clicking a chord breaks the line there or
           marks it as a passing chord (the bars do not change) */
        .lines-view { display: grid; gap: .6rem; }
        .line-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .line-block { padding: .45rem .6rem; border: 1px solid #e5e7eb; border-left: 3px solid var(--type-color, #4f46e5); border-radius: .5rem; background: #fff; }
        .line-head { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .line-name { font-size: .75rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--type-color, #4f46e5); }
        .line-state { font-size: .72rem; color: #9ca3af; }
        .line-head .ws-btn { min-height: 28px; padding: 0 .5rem; font-size: .75rem; }
        .line-row { display: flex; flex-wrap: wrap; column-gap: 1rem; margin-top: .25rem; }
        .line-step { white-space: nowrap; }
        .line-chord { border: 0; background: none; padding: .05rem .15rem; border-radius: .3rem; font-weight: 800; font-size: 1.05rem; color: #111827; cursor: pointer; }
        .line-chord:hover { background: #eef2ff; color: #4f46e5; }
        .line-chord.is-passing { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .22em; }
        .line-chord.is-playing, .line-chord.is-playing:hover { background: #f59e0b; color: #fff; }
        .line-block.is-current { background: #fffbeb; }
        .beat.is-on { background: #f59e0b; }
        .beat.is-on .chord, .beat.is-on .count { color: #fff; }
        .bars-page.is-editing .beat { cursor: text; }
        .bars-page.is-editing .beat:hover { outline: 1px dashed #f59e0b; }

        .sheet-col { min-width: 0; }
        .sheet-box { position: sticky; top: 4rem; max-height: calc(100vh - 6rem); overflow-y: auto; padding: .75rem; border: 1px solid #e5e7eb; border-radius: .5rem; background: #fafafa; }
        @media (max-width: 1023px) { .sheet-box { position: static; max-height: none; } }
        .sheet-section { padding: .35rem .5rem; border-radius: .4rem; border-left: 3px solid transparent; }
        .sheet-section + .sheet-section { margin-top: .6rem; }
        .sheet-section.is-current { background: #fffbeb; border-left-color: #f59e0b; }
        .sheet-label { font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--type-color, #6b7280); cursor: pointer; }
        .sheet-line { display: flex; flex-wrap: wrap; margin-top: .15rem; font-size: .92rem; line-height: 1.15; }
        .sheet-seg { display: inline-flex; flex-direction: column; }
        .sheet-seg .c { min-height: 1.05em; font-size: .82rem; font-weight: 800; color: #4f46e5; padding-right: .3rem; }
        .sheet-seg .c.is-playing > span { padding: 0 .2rem; margin: 0 -.2rem; border-radius: .25rem; background: #4f46e5; color: #fff; }
        .sheet-seg .t { white-space: pre; }
        .sheet-comment { margin-top: .15rem; font-size: .82rem; font-style: italic; color: #6b7280; }
        .sheet-head { display: flex; align-items: center; gap: .35rem; }
        .sheet-edit-btn { border: 0; background: none; padding: .1rem .3rem; border-radius: .3rem; color: #9ca3af; font-size: .75rem; cursor: pointer; }
        .sheet-edit-btn:hover { color: #4f46e5; background: #eef2ff; }
        .sheet-editor textarea { width: 100%; margin-top: .3rem; padding: .4rem .5rem; border: 1px solid #c7d2fe; border-radius: .4rem; font: .85rem/1.45 ui-monospace, SFMono-Regular, Menlo, monospace; resize: vertical; }
        .sheet-editor .row { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: .3rem; }
        .sheet-editor .help { font-size: .75rem; color: #6b7280; }
        .sheet-editor .error { font-size: .78rem; color: #dc2626; }

        .popover { position: absolute; z-index: 50; width: min(20rem, calc(100vw - 1.5rem)); padding: .65rem; border: 1px solid #f59e0b; border-radius: .6rem; background: #fff; box-shadow: 0 12px 30px rgba(0, 0, 0, .18); }
        .popover input[type=text] { width: 100%; padding: .4rem .55rem; border: 1px solid #d1d5db; border-radius: .4rem; font-weight: 700; font-size: 1.05rem; }
        .popover .row { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
        .popover .row .ws-btn { flex: 1; }
        .popover .title { margin: 0 0 .4rem; font-size: .8rem; color: #6b7280; }
        .key-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .35rem; }
        .key-chip { min-height: 38px; border: 1px solid #d1d5db; border-radius: .5rem; background: #fff; font-weight: 700; cursor: pointer; }
        .key-chip.is-active { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .bpm-input { width: 4.5rem; min-height: 38px; padding: 0 .4rem; border: 1px solid #d1d5db; border-radius: .5rem; text-align: center; font-weight: 700; }
        .version-select { min-height: 38px; padding: 0 2rem 0 .6rem; border: 1px solid #c7d2fe; border-radius: .5rem; font-weight: 700; color: #4f46e5; }
        .version-note { padding: .35rem .6rem; margin-top: .4rem; border-radius: .4rem; background: #eef2ff; color: #3730a3; font-size: .8rem; }
        .save-state { font-size: .8rem; color: #6b7280; }
        .save-state.is-dirty { color: #b45309; }
        .save-state.is-error { color: #dc2626; }
    </style>

    @include('songs.partials.workspace')

    <div class="ws-page">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="ws-card bg-white shadow sm:rounded-lg">
                @include('songs.partials.tabs', ['song' => $song, 'current' => 'layout'])
                @include('layouts.alert')

                <div class="bars-page" :class="{ 'is-editing': mode === 'edit' }" x-data="songBars(@js([
                    'structure' => $blocks,
                    'sheet' => $sheet,
                    'key' => $song->musical_key,
                    'bpm' => $song->bpm,
                    'updatedAt' => $song->updated_at?->toIso8601String(),
                    'audioUrl' => $song->audioUrl(),
                    'youtubeUrl' => $song->youtube_url,
                    'saveUrl' => route('songs.bars.update', $song),
                    'sheetSectionUrl' => route('songs.sheet-section.update', $song),
                    'versions' => $versions,
                    'versionStoreUrl' => route('songs.versions.store', $song),
                    'instruments' => $instruments,
                    'title' => $song->title,
                ]))" @keydown.window="onKey($event)">

                    {{-- Toolbar: player, play / edit, undo, tempo, key, save, options --}}
                    <div class="ws-toolbar">
                        @include('songs.partials.play-button', ['toggle' => 'togglePlay()', 'time' => 'formatTime(clock)', 'show' => 'canPlay'])
                        <span class="ws-seg" role="group" aria-label="{{ __('Mode') }}">
                            <button type="button" :class="{ 'is-active': mode === 'play' }" @click="setMode('play')" title="{{ __('Clicking a beat plays from it') }}"><i class="fa-solid fa-headphones"></i><span class="ws-label"> {{ __('Play') }}</span></button>
                            <button type="button" :class="{ 'is-active': mode === 'edit' }" @click="setMode('edit')" title="{{ __('Clicking a beat edits its chord') }}"><i class="fa-solid fa-pen"></i><span class="ws-label"> {{ __('Edit') }}</span></button>
                            <button type="button" :class="{ 'is-active': mode === 'lines' }" @click="setMode('lines')" title="{{ __('Lines of the map and passing chords, without changing the bars') }}"><i class="fa-solid fa-bars-staggered"></i><span class="ws-label"> {{ __('Lines') }}</span></button>
                        </span>
                        <select class="version-select" :value="versionId ?? ''" @change="selectVersion($event.target.value ? Number($event.target.value) : null); $event.target.value = versionId ?? ''"
                            title="{{ __('Version: the base or the map of an instrument') }}" aria-label="{{ __('Version') }}">
                            <option value="">{{ __('Base') }}</option>
                            <template x-for="version in versions" :key="version.id">
                                <option :value="version.id" x-text="version.instrument" :selected="version.id === versionId"></option>
                            </template>
                        </select>
                        <button type="button" class="ws-btn" @click="openVersionMenu($event.currentTarget)" title="{{ __('New version for an instrument') }}" aria-label="{{ __('New version for an instrument') }}"><i class="fa-solid fa-plus"></i></button>
                        <button type="button" class="ws-btn" x-show="versionId" @click="deleteVersion()" title="{{ __('Delete this version') }}" aria-label="{{ __('Delete this version') }}"><i class="fa-solid fa-trash"></i></button>
                        <button type="button" class="ws-btn" @click="undo()" :disabled="!history.length" title="{{ __('Undo') }}" aria-label="{{ __('Undo') }}"><i class="fa-solid fa-rotate-left"></i></button>
                        <button type="button" class="ws-btn" x-show="canPlay" :class="{ 'is-active': loop }" @click="toggleLoop()"
                            :title="loop ? @js(__('Stop repeating')) : @js(__('Repeat the marked bars (or the current bar)'))" aria-label="{{ __('Repeat') }}"><i class="fa-solid fa-repeat"></i></button>
                        <button type="button" class="ws-btn" x-show="canPlay" :class="{ 'is-active': metronome }" @click="metronome = !metronome"
                            title="{{ __('Metronome') }}" aria-label="{{ __('Metronome') }}"><i class="fa-solid fa-stopwatch"></i></button>
                        <span class="ws-spacer"></span>
                        <label class="ws-btn" style="padding-right: .3rem;" title="{{ __('Beats per minute') }}" x-show="!versionId">
                            <span class="ws-label">BPM</span>
                            <input type="number" class="bpm-input" min="30" max="300" step="0.1" :value="bpm" @change="setBpm($event.target.value)" aria-label="BPM" style="border: 0; min-height: 0;">
                        </label>
                        <button type="button" class="ws-btn" x-show="!versionId" :class="{ 'is-active': keyOpen }" @click="keyOpen = !keyOpen" title="{{ __('Key') }}">
                            <i class="fa-solid fa-music"></i> <strong x-text="keyLabel(key)"></strong>
                        </button>
                        <button type="button" class="ws-btn ws-btn-primary" @click="save()" :disabled="saving" title="{{ __('Save') }}">
                            <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i><span class="ws-label">{{ __('Save') }}</span>
                        </button>
                        <button type="button" class="ws-btn" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" aria-label="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="save-state" :class="{ 'is-dirty': dirty, 'is-error': saveError }" x-text="saveState"></span>
                        <span class="ws-note ws-desktop" x-text="mode === 'edit' ? @js(__('Click a beat to change its chord; right click a bar for its block.')) : @js(__('Click a beat to play from it; right click a bar for its block.'))"></span>
                    </div>
                    <p class="version-note" x-show="versionId" x-cloak x-text="@js(__('Editing the version for :instrument: only its chords change (the blocks, times, tempo, key and lyrics are the base\'s). It is a copy: later changes to the base do not reach it.')).replace(':instrument', currentVersion?.instrument ?? '')"></p>
                    {{-- Marked bars, copied chords and what happened to them --}}
                    <div class="passage-bar" x-show="hasSelection || clipboard || notice" x-cloak>
                        <span x-show="hasSelection" x-text="`${@js(__('Marked'))}: ${barsLabel(selection.from, selection.to)}`"></span>
                        <button type="button" class="ws-btn" x-show="hasSelection" @click="copyBars()"><i class="fa-solid fa-copy"></i> {{ __('Copy') }}</button>
                        <button type="button" class="ws-btn" x-show="hasSelection" @click="restoreBars()" title="{{ __('Back as they were when the page was opened (or last saved)') }}"><i class="fa-solid fa-clock-rotate-left"></i> {{ __('Restore') }}</button>
                        <button type="button" class="ws-btn" x-show="clipboard" @click="paste(cursorBar(), false)" :title="@js(__('Only the chord names: the changes here stay where they are'))"><i class="fa-solid fa-paste"></i> {{ __('Paste here') }}</button>
                        <button type="button" class="ws-btn" x-show="clipboard" @click="paste(cursorBar(), true)" :title="@js(__('Where each chord changes comes along too'))">{{ __('Paste with the rhythm') }}</button>
                        <button type="button" class="ws-btn" x-show="hasSelection" @click="clearSelection()" aria-label="{{ __('Unmark') }}"><i class="fa-solid fa-xmark"></i></button>
                        <span x-text="notice"></span>
                    </div>

                    {{-- Phones: the bars or the chord sheet --}}
                    <span class="ws-seg mt-2 pane-tabs" x-show="sheet.length" role="group">
                        <button type="button" :class="{ 'is-active': pane === 'bars' }" @click="pane = 'bars'"><i class="fa-solid fa-table-cells"></i> {{ __('Bars') }}</button>
                        <button type="button" :class="{ 'is-active': pane === 'sheet' }" @click="pane = 'sheet'"><i class="fa-solid fa-align-left"></i> {{ __('Chord sheet') }}</button>
                    </span>

                    <div class="bars-layout" :class="`pane-${pane}`">
                        {{-- Bars: the chord of each beat ("/" while it goes on, a change on the "and" splits the beat) --}}
                        <div class="bars-col">
                            {{-- Lines of the map (as on the public page), set by clicking the chords --}}
                            <template x-if="mode === 'lines'">
                                <div class="lines-view">
                                    <div class="line-tools">
                                        <span class="ws-seg" role="group">
                                            <button type="button" :class="{ 'is-active': lineTool === 'break' }" @click="lineTool = 'break'">{{ __('Break line') }}</button>
                                            <button type="button" :class="{ 'is-active': lineTool === 'passing' }" @click="lineTool = 'passing'">{{ __('Passing chord') }}</button>
                                        </span>
                                        <span class="ws-note" style="white-space: normal;" x-text="lineTool === 'break' ? @js(__('Click a chord to start a new line on it; click the first chord of a line to join it to the line above.')) : @js(__('Click a chord to mark it as a passing chord (underlined); click it again to unmark it. Passing chords do not count when looking for repetitions.'))"></span>
                                    </div>
                                    <template x-for="item in lineBlocks" :key="`${revision}-${item.block.id}`">
                                        <div class="line-block" :class="{ 'is-current': linePlaying?.id === item.block.id }" :style="typeStyle(item.name)">
                                            <div class="line-head">
                                                <button type="button" class="line-name block-chip" @click="canPlay && playFromTick(item.block.startTick)" :title="canPlay ? @js(__('Play from here')) : ''"><i class="fa-solid fa-play play" x-show="canPlay"></i> <span x-text="item.name"></span></button>
                                                <span class="line-state" x-text="item.manual ? @js(__('Lines set by hand')) : @js(__('Automatic lines'))"></span>
                                                <button type="button" class="ws-btn" x-show="item.manual" @click="automaticLines(item.block)">{{ __('Back to automatic') }}</button>
                                            </div>
                                            <template x-for="(line, l) in item.lines" :key="l">
                                                <div class="line-row">
                                                    <template x-for="(step, i) in line" :key="i">
                                                        <span class="line-step"><template x-for="chord in step" :key="chord.position"><button type="button" class="line-chord" :class="{ 'is-passing': chord.passing, 'is-playing': linePlaying?.id === item.block.id && linePlaying?.position === chord.position }" :data-line-chord="`${item.block.id}-${chord.position}`" @click="clickLineChord(item.block, chord.position)" x-text="transposeName(chord.name)"></button></template></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div class="bars-grid" x-show="mode !== 'lines'">
                                <template x-for="(bar, b) in bars" :key="`${revision}-${b}`">
                                    <div class="bar" :class="{ 'is-current': b === currentBar, 'has-block': bar.blocks.some(block => !isMarker(block)), 'is-selected': isSelected(b), 'is-looped': loop && b >= loop.from && b <= loop.to }"
                                        :style="bar.blocks.length ? typeStyle(blockName(bar.blocks[0])) : ''" :data-bar="b"
                                        @click="clickBar(b, $event)" @contextmenu.prevent="openBarMenu(b, $event.currentTarget)"
                                        @pointerdown="startPress(b, $event)" @pointerup="endPress()" @pointerleave="endPress()" @pointercancel="endPress()">
                                        <span class="number" x-text="b + 1"></span>
                                        <div class="bar-blocks">
                                            <template x-for="block in bar.blocks" :key="block.id">
                                                <button type="button" class="block-chip" :class="{ 'is-marker': isMarker(block) }" :style="typeStyle(blockName(block))"
                                                    @click.stop="isMarker(block) ? null : playFromTick(block.startTick)" @contextmenu.stop.prevent="openBarMenu(b, $event.currentTarget.closest('.bar'))"
                                                    :title="isMarker(block) ? '' : @js(__('Play from here'))">
                                                    <i class="fa-solid fa-play play" x-show="!isMarker(block) && canPlay"></i>
                                                    <span x-text="isMarker(block) ? '↺ ' + @js(__('Back to the start')) : blockName(block)"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="bar-beats" :style="`grid-template-columns: repeat(${bar.beats.length}, minmax(0, 1fr))`">
                                            <template x-for="(beat, i) in bar.beats" :key="i">
                                                <span class="beat" :class="{ 'is-on': b === currentBar && i === currentBeat }" @click.stop="clickBeat(b, i, $event.currentTarget, $event)"
                                                    :title="`${@js(__('Beat'))} ${i + 1}: ${beat.chord || '–'}${beat.and !== null ? ' → ' + (beat.and || '–') + ' (e)' : ''}`">
                                                    <span class="halves">
                                                        <span class="chord" :class="{ 'is-repeat': beat.repeat, 'is-long': !beat.repeat && beat.chord.length > 4 }" x-text="beat.repeat ? '/' : (beat.chord || '–')"></span>
                                                        <template x-if="beat.and !== null"><span class="chord is-and" :class="{ 'is-long': beat.and.length > 4 }" x-text="beat.and || '–'"></span></template>
                                                    </span>
                                                    <span class="count" x-text="i + 1"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Chord sheet with lyrics, split like the blocks; the block being played is lit --}}
                        <div class="sheet-col" x-show="sheet.length">
                            <div class="sheet-box">
                                <template x-for="(section, s) in shownSheet" :key="`${sheetRevision}-${versionId}-${s}`">
                                    <div class="sheet-section" :class="{ 'is-current': s === currentSheetSection }" :style="typeStyle(section.label)" :data-sheet="s">
                                        <div class="sheet-head">
                                            <div class="sheet-label" x-text="section.label" @click="playFromSheet(s)"></div>
                                            <button type="button" class="sheet-edit-btn" x-show="!versionId && !section.marker && sheetEdit?.index !== s" @click.stop="editSheetSection(s)"
                                                title="{{ __('Edit the lyrics') }}" aria-label="{{ __('Edit the lyrics') }}"><i class="fa-solid fa-pen"></i></button>
                                        </div>
                                        <template x-if="sheetEdit?.index === s">
                                            <div class="sheet-editor">
                                                <textarea x-model="sheetEdit.text" :rows="Math.max(3, sheetEdit.text.split('\n').length + 1)" spellcheck="false"
                                                    x-init="$nextTick(() => $el.focus())" @keydown.escape.stop.prevent="sheetEdit = null"
                                                    @keydown.enter.meta.prevent="saveSheetSection()" @keydown.enter.ctrl.prevent="saveSheetSection()"></textarea>
                                                <div class="row">
                                                    <button type="button" class="ws-btn ws-btn-primary" @click="saveSheetSection()" :disabled="sheetEdit.saving">
                                                        <i class="fa-solid" :class="sheetEdit.saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> {{ __('Save') }}
                                                    </button>
                                                    <button type="button" class="ws-btn" @click="sheetEdit = null">{{ __('Cancel') }}</button>
                                                </div>
                                                <p class="help">{{ __('Chords stay in brackets, e.g. Esp[G]írito Santo: change the lyrics or move a chord, but keep the same chords.') }}</p>
                                                <p class="error" x-show="sheetEdit.error" x-text="sheetEdit.error"></p>
                                            </div>
                                        </template>
                                        <template x-for="(line, l) in (sheetEdit?.index === s ? [] : section.lines)" :key="l">
                                            <div>
                                                <template x-if="line.comment !== undefined"><div class="sheet-comment" x-text="line.comment"></div></template>
                                                <template x-if="line.comment === undefined">
                                                    <div class="sheet-line"><template x-for="(unit, u) in line.units" :key="u"><span class="sheet-seg"><span class="c" :class="{ 'is-playing': unit.n !== undefined && unit.n === currentSheetChord }" :data-chord="unit.n"><span x-text="unit.chord ? transposeName(unit.chord) : ''"></span></span><span class="t" x-text="unit.text"></span></span></template></div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Chord of a beat --}}
                    <div class="popover" x-show="editor" x-cloak x-ref="editor" @click.outside="editor = null" @keydown.escape.stop="editor = null">
                        <p class="title" x-text="editor ? `${@js(__('Bar'))} ${editor.bar + 1}, ${@js(__('beat'))} ${editor.beat + 1}${editor.half ? ' (e)' : ''}` : ''"></p>
                        <input type="text" x-ref="editorChord" autocomplete="off" spellcheck="false" placeholder="G, Am7, C/E, D4" @keydown.enter.prevent="applyChord()">
                        <div class="row">
                            <button type="button" class="ws-btn" :class="{ 'is-active': editor?.half === 0 }" @click="editor.half = 0; fillEditor()">{{ __('On the beat') }}</button>
                            <button type="button" class="ws-btn" :class="{ 'is-active': editor?.half === 1 }" @click="editor.half = 1; fillEditor()">{{ __('On the "and"') }}</button>
                        </div>
                        <div class="row">
                            <button type="button" class="ws-btn ws-btn-primary" @click="applyChord()">{{ __('Apply') }}</button>
                            <button type="button" class="ws-btn" @click="removeChange()" :disabled="!canRemoveChange()" title="{{ __('The chord before goes on from here') }}">{{ __('Remove change') }}</button>
                        </div>
                        <p class="title mt-2" x-show="editorError" x-text="editorError" style="color: #dc2626;"></p>
                    </div>

                    {{-- Block of a bar: new, rename, join; play from the bar --}}
                    <div class="popover" x-show="barMenu" x-cloak x-ref="barMenu" @click.outside="barMenu = null" @keydown.escape.stop="barMenu = null">
                        <p class="title" x-text="barMenuTitle()"></p>
                        <input type="text" x-ref="blockName" list="block-names" x-show="!versionId" autocomplete="off" spellcheck="false" placeholder="{{ __('E.g. CHORUS') }}" style="text-transform: uppercase" @keydown.enter.prevent="saveBlockName()">
                        <datalist id="block-names">
                            <template x-for="name in commonNames" :key="name"><option :value="name"></option></template>
                        </datalist>
                        <div class="row" x-show="!versionId">
                            <button type="button" class="ws-btn ws-btn-primary" @click="saveBlockName()" x-text="barMenu && blockAtBar(barMenu.bar) ? @js(__('Rename')) : @js(__('New block here'))"></button>
                        </div>
                        <div class="row" x-show="!versionId && barMenu && blockAtBar(barMenu.bar)">
                            <button type="button" class="ws-btn" @click="joinPrevious()" :disabled="!barMenu || !previousBlock(barMenu.bar)">{{ __('Join to the block above') }}</button>
                            <button type="button" class="ws-btn" @click="joinNext()" :disabled="!barMenu || !nextBlock(barMenu.bar)">{{ __('Join with the next') }}</button>
                        </div>
                        <div class="row" x-show="!versionId && barMenu && blockOffset(barMenu.bar)">
                            <button type="button" class="ws-btn" @click="alignBlock(false)">{{ __('Start on beat 1 of this bar') }}</button>
                            <button type="button" class="ws-btn" @click="alignBlock(true)">{{ __('Start on the next bar') }}</button>
                        </div>
                        <div class="row" x-show="canPlay">
                            <button type="button" class="ws-btn" @click="playFromTick(barMenu.bar * perBar * 2); barMenu = null"><i class="fa-solid fa-play"></i> {{ __('Play from here') }}</button>
                        </div>
                        <hr>
                        <p class="title" x-text="barMenu ? menuBarsLabel() : ''"></p>
                        <div class="row">
                            <button type="button" class="ws-btn" @click="copyBars(barMenu.bar); barMenu = null"><i class="fa-solid fa-copy"></i> {{ __('Copy') }}</button>
                            <button type="button" class="ws-btn" @click="copyBlock(barMenu.bar); barMenu = null">{{ __('Copy the block') }}</button>
                        </div>
                        <div class="row" x-show="clipboard">
                            <button type="button" class="ws-btn" @click="paste(barMenu.bar, false); barMenu = null" :title="@js(__('Only the chord names: the changes here stay where they are'))"><i class="fa-solid fa-paste"></i> {{ __('Paste here') }}</button>
                            <button type="button" class="ws-btn" @click="paste(barMenu.bar, true); barMenu = null" :title="@js(__('Where each chord changes comes along too'))">{{ __('Paste with the rhythm') }}</button>
                        </div>
                        <div class="row">
                            <button type="button" class="ws-btn" @click="restoreBars(barMenu.bar); barMenu = null" title="{{ __('Back as they were when the page was opened (or last saved)') }}"><i class="fa-solid fa-clock-rotate-left"></i> {{ __('Restore') }}</button>
                            <button type="button" class="ws-btn" x-show="hasSelection && !isSelected(barMenu?.bar)" @click="selectTo(barMenu.bar); barMenu = null">{{ __('Mark up to here') }}</button>
                            <button type="button" class="ws-btn" x-show="canPlay" @click="toggleLoop(barMenu.bar); barMenu = null"><i class="fa-solid fa-repeat"></i> <span x-text="loop ? @js(__('Stop repeating')) : @js(__('Repeat'))"></span></button>
                        </div>
                    </div>

                    {{-- New version for an instrument: a copy of the base map --}}
                    <div class="popover" x-show="versionMenu" x-cloak x-ref="versionMenu" @click.outside="versionMenu = false" @keydown.escape.stop="versionMenu = false">
                        <p class="title">{{ __('New version for an instrument: a copy of the base map, to change its chords.') }}</p>
                        <input type="text" x-ref="instrumentName" list="instrument-names" autocomplete="off" placeholder="{{ __('E.g. Bass') }}" @keydown.enter.prevent="createVersion()">
                        <datalist id="instrument-names">
                            <template x-for="name in instruments.filter(name => !versions.some(version => version.instrument === name))" :key="name"><option :value="name"></option></template>
                        </datalist>
                        <div class="row">
                            <button type="button" class="ws-btn ws-btn-primary" @click="createVersion()" :disabled="versionBusy">{{ __('Create version') }}</button>
                        </div>
                        <p class="title mt-2" x-show="versionError" x-text="versionError" style="color: #dc2626;"></p>
                    </div>

                    <x-song-options :title="__('Key')" open="keyOpen">
                        @php
                            $minorSong = \App\Enums\MusicalKey::isMinor($song->musical_key);
                        @endphp
                        <div class="ws-panel-section">
                            <div class="key-grid">
                                @foreach ($keyOptions as $pair)
                                    <button type="button" class="key-chip" :class="{ 'is-active': key === @js($pair[$minorSong ? 'minor' : 'major']) }" @click="changeKey(@js($pair[$minorSong ? 'minor' : 'major'])); keyOpen = false">{{ $pair['label'] }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="ws-panel-section">
                            <p class="ws-panel-help">{{ __('Changing the key transposes the chords of the map and of the chord sheet. Save to keep it.') }}</p>
                        </div>
                    </x-song-options>

                    <x-song-options>
                        <div class="ws-panel-section" x-show="hasAudio">
                            <div class="ws-panel-label"><i class="fa-solid fa-gauge"></i> {{ __('Speed') }}</div>
                            <span class="ws-seg" role="group" aria-label="{{ __('Speed') }}">
                                <template x-for="speed in videoSpeeds" :key="speed">
                                    <button type="button" :class="{ 'is-active': audioSpeed === speed }" @click="setAudioSpeed(speed)" x-text="`${speed}×`"></button>
                                </template>
                            </span>
                        </div>
                        @include('songs.partials.video-options')
                        <div class="ws-panel-section">
                            <div class="ws-panel-label"><i class="fa-solid fa-file-csv"></i> CSV</div>
                            <div class="ws-panel-row">
                                <button type="button" class="ws-btn" @click="exportCsv()"><i class="fa-solid fa-download"></i> {{ __('Export CSV') }}</button>
                                <button type="button" class="ws-btn" @click="$refs.csvFile.click()"><i class="fa-solid fa-upload"></i> {{ __('Import CSV') }}</button>
                                <input type="file" x-ref="csvFile" accept=".csv,.txt,text/csv" hidden @change="importCsv($event.target.files[0]); $event.target.value = ''">
                            </div>
                            <p class="ws-panel-help mt-2">{{ __('One line per bar: the block that starts in it and the chord of each beat ("/" or empty: the chord goes on; "G D": G on the beat and D on the "and"). Importing needs the same number of bars and is not saved until you save; in a version only the chords are imported.') }}</p>
                            <p class="ws-panel-help mt-2" x-show="csvMessage" x-text="csvMessage" :style="csvError ? 'color: #dc2626' : ''"></p>
                        </div>
                        <div class="ws-panel-section">
                            <div class="ws-panel-label"><i class="fa-solid fa-circle-info"></i> {{ __('How it works') }}</div>
                            <p class="ws-panel-help">{{ __('Each bar shows the chord of every beat ("/" while it goes on; a change on the "and" splits the beat). The position of each bar in the song comes from the start of its block and the BPM.') }}</p>
                            <p class="ws-panel-help mt-2">{{ __('Play: click a beat to play from it. Edit: click a beat to change its chord. Right click (or press and hold on a phone) a bar for its block: new block, rename, join.') }}</p>
                            <p class="ws-panel-help mt-2">{{ __('Marking bars: click a bar, then Shift + click another; the marked bars can be copied, restored and repeated. Copy and paste also work on one bar or a whole block (right click a bar).') }}</p>
                            <p class="ws-panel-help mt-2">{{ __('Keys: space plays / pauses, ← → move a beat, ↑ ↓ a bar (Page Up / Down 4 bars), Enter edits the beat, Ctrl/⌘ + C / V copies and pastes, Shift + R restores, Esc unmarks, Ctrl/⌘ + Z undoes, Ctrl/⌘ + S saves.') }}</p>
                        </div>
                    </x-song-options>

                    @include('songs.partials.youtube-mini', ['id' => 'yt-bars'])
                </div>
            </div>
        </div>
    </div>

    <script>
        // Grid of bars of a chord map with durations (from the study app or created on the layout page).
        // The whole song is a list of ticks (two per beat: on the beat and halfway, "and"), each with its chord;
        // blocks start at a tick. Saving turns the ticks of each block back into chords and durations in beats.
        function songBars(config) {
            const audio = config.audioUrl ? new Audio(config.audioUrl) : null;
            if (audio) audio.preload = 'auto';
            let nextId = 0;

            const isMarkerData = (data) => data?.jump === 'start';
            const toSeconds = (value) => {
                if (typeof value === 'number') return value;
                if (typeof value !== 'string' || !/^\d+(:\d{1,2}){0,2}(\.\d+)?$/.test(value.trim())) return null;
                return value.trim().split(':').reduce((total, part) => total * 60 + Number(part), 0);
            };
            const formatStart = (seconds) => {
                const tenths = Math.round(seconds * 10);
                const whole = Math.floor(tenths / 10);
                const text = `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`;
                return tenths % 10 ? `${text}.${tenths % 10}` : text;
            };

            // Structure -> ticks + blocks.
            const parseStructure = (structure) => {
            const ticks = [];
            const blocks = [];
            let perBar = 4;
            (Array.isArray(structure) ? structure : []).forEach(section => {
                if (!section || typeof section !== 'object') return;
                const { chords: raw, durations, order, ...data } = section;
                if (isMarkerData(section)) return blocks.push({ id: nextId++, startTick: ticks.length, data });
                const list = Array.isArray(raw) ? raw.map(String) : [];
                const chords = chordsOnly(list);
                // Line breaks of the map: positions (chord indexes) where a new line starts; they and the passing
                // chords belong to these chords (`lineChords`) and go back to the map only while the block has them.
                const breaks = [];
                let position = 0;
                list.forEach(chord => { if (chord !== '|') position++; else if (position > 0 && !breaks.includes(position)) breaks.push(position); });
                if (section.beats_per_bar) perBar = section.beats_per_bar;
                blocks.push({ id: nextId++, startTick: ticks.length, data: { ...data, lineChords: chords, breaks } });
                chords.forEach((chord, i) => { for (let k = 0; k < Math.round((durations?.[i] ?? perBar) * 2); k++) ticks.push(chord); });
            });
            return { ticks, blocks, perBar };
            };
            const { ticks, blocks, perBar } = parseStructure(config.structure);

            // ChordPro lines -> { comment } or { units: [{ chord, text }] }.
            const parseLine = (line) => {
                const text = String(line ?? '');
                const comment = /^\{\s*(?:c|comment)\s*:\s*(.*?)\s*\}$/i.exec(text.trim());
                if (comment) return { comment: comment[1] };
                if (/^\s*\{/.test(text)) return { comment: '' };
                const units = [];
                let last = 0, chord = null;
                for (const match of text.matchAll(/\[([^\]]+)\]/g)) {
                    const before = text.slice(last, match.index);
                    if (chord !== null || before) units.push({ chord, text: before });
                    chord = match[1];
                    last = match.index + match[0].length;
                }
                units.push({ chord, text: text.slice(last) });
                return { units };
            };
            // Each chord of the sheet gets its number ("n"), counted from the start of the sheet.
            const parseSheet = (sections) => {
                let n = 0;
                const view = (sections || []).map(section => ({
                    label: section.label || String(section.section ?? '').replaceAll('_', ' '),
                    lines: (section.lines || []).map(parseLine),
                    marker: section.jump === 'start',
                }));
                view.forEach(section => section.lines.forEach(line => (line.units || []).forEach(unit => {
                    if (unit.chord !== null) unit.n = n++;
                })));
                view.chordCount = n;
                return view;
            };

            return withYoutubeMini({
                ticks,
                blocks,
                perBar,
                key: config.key || null,
                savedKey: config.key || null,
                bpm: Number(config.bpm) || 80,
                updatedAt: config.updatedAt,
                sheet: config.sheet || [],
                sheetView: parseSheet(config.sheet),
                // Versions of the map for instruments; the one being edited (null: the base). The base map as saved,
                // to show a version's chords in the chord sheet.
                versions: config.versions || [],
                versionId: null,
                instruments: config.instruments || [],
                baseStructure: config.structure || [],
                versionMenu: false,
                versionBusy: false,
                versionError: '',
                shownSheetCache: { key: null, value: null },
                csvMessage: '',
                csvError: false,
                sheetRevision: 0,
                // Section of the chord sheet whose lyrics are being edited: { index, text, error, saving }.
                sheetEdit: null,
                mode: 'play',
                pane: 'bars',
                history: [],
                revision: 0,
                dirty: false,
                saving: false,
                saveError: false,
                saveState: '',
                keyOpen: false,
                optionsOpen: false,
                editor: null,
                editorError: '',
                barMenu: null,
                clock: 0,
                currentTick: -1,
                // Bars marked (click, then Shift + click): { anchor, from, to }; from / to null when none.
                selection: { anchor: null, from: null, to: null },
                // Chords copied: { ticks, offset (ticks after the start of its first bar), label }.
                clipboard: null,
                notice: '',
                // Chords as they were when the page was opened or last saved (to restore bars).
                originalTicks: [...ticks],
                // Passage repeated while playing: { from, to } (bars).
                loop: null,
                loopCooldown: 0,
                metronome: false,
                audioSpeed: 1,
                // Lines view: what a click on a chord does ('break' or 'passing').
                lineTool: 'break',
                commonNames: ['INTRO', 'PRIMEIRA PARTE', 'SEGUNDA PARTE', 'PRÉ-REFRÃO', 'REFRÃO', 'PONTE', 'INTERLÚDIO', 'SOLO', 'RIFF', 'FINAL',
                    'VERSE', 'PRE CHORUS', 'CHORUS', 'BRIDGE', 'INTERLUDE', 'TAG', 'OUTRO'],

                init() {
                    if (audio) {
                        // The MP3 drives the same "playing" as the YouTube player (see youtube-mini.js).
                        audio.addEventListener('play', () => { this.playing = true; });
                        audio.addEventListener('pause', () => { this.playing = false; });
                    } else {
                        this.$nextTick(() => this.initVideo('yt-bars'));
                    }
                    window.addEventListener('beforeunload', event => {
                        if (this.dirty && !this.saving) event.preventDefault();
                    });
                    const follow = () => {
                        this.clock = this.now();
                        if (this.isPlaying()) this.keepLoop();
                        this.scheduleClicks();
                        const tick = this.isPlaying() ? this.tickAt(this.clock) : this.currentTick;
                        if (tick !== this.currentTick) {
                            const before = this.currentBar;
                            const chordBefore = this.currentSheetChord;
                            this.currentTick = tick;
                            if (this.isPlaying() && this.mode === 'lines') {
                                const position = this.linePlaying;
                                if (JSON.stringify(position) !== this.lastLinePlaying) { this.lastLinePlaying = JSON.stringify(position); this.scrollToLineChord(); }
                            } else if (this.isPlaying() && this.currentBar !== before) this.scrollToBar(this.currentBar);
                            const chord = this.currentSheetChord;
                            if (this.isPlaying() && chord >= 0 && chord !== chordBefore) this.scrollToSheetChord(chord);
                        }
                        requestAnimationFrame(follow);
                    };
                    requestAnimationFrame(follow);
                },

                // ---- Bars ----
                get bars() {
                    const size = this.perBar * 2;
                    const bars = [];
                    for (let start = 0; start < this.ticks.length; start += size) {
                        const part = this.ticks.slice(start, start + size);
                        const beats = [];
                        for (let i = 0; i < part.length; i += 2) {
                            const onBeat = part[i];
                            const previous = start + i > 0 ? this.ticks[start + i - 1] : null;
                            beats.push({
                                chord: this.transposeName(onBeat),
                                repeat: i > 0 && onBeat === previous,
                                and: part[i + 1] !== undefined && part[i + 1] !== onBeat ? this.transposeName(part[i + 1]) : null,
                            });
                        }
                        bars.push({ beats, blocks: this.blocks.filter(block => block.startTick >= start && block.startTick < start + size) });
                    }
                    return bars;
                },
                get currentBar() { return this.currentTick < 0 ? -1 : Math.floor(this.currentTick / (this.perBar * 2)); },
                get currentBeat() { return this.currentTick < 0 ? -1 : Math.floor((this.currentTick % (this.perBar * 2)) / 2); },
                isMarker(block) { return isMarkerData(block.data); },
                blockName(block) { return String(block.data.section || '').replaceAll('_', ' '); },
                typeStyle(name) { return `--type-color: var(--type-${blockType(name)})`; },
                timedBlocks() { return this.blocks.filter(block => !this.isMarker(block)).sort((a, b) => a.startTick - b.startTick); },
                blockOfTick(tick) { return this.timedBlocks().filter(block => block.startTick <= tick).at(-1) ?? null; },
                // The block that starts in a bar (on its first beat or later in it, e.g. on the "and" of beat 4).
                blockAtBar(bar) {
                    const size = this.perBar * 2;
                    return this.timedBlocks().find(block => block.startTick >= bar * size && block.startTick < (bar + 1) * size) ?? null;
                },
                blockOffset(bar) { const block = this.blockAtBar(bar); return block ? block.startTick - bar * this.perBar * 2 : 0; },
                // Moving a block's start to the first beat of this bar or of the next one (the chords stay where they are).
                alignBlock(toNext) {
                    const bar = this.barMenu.bar;
                    this.barMenu = null;
                    const block = this.blockAtBar(bar);
                    const target = (toNext ? bar + 1 : bar) * this.perBar * 2;
                    const next = this.timedBlocks()[this.timedBlocks().indexOf(block) + 1];
                    if (!block || target >= this.ticks.length || (next && target > next.startTick)) return;
                    this.remember();
                    // A block already starting there (e.g. one made at the next bar) becomes part of this one.
                    if (next && target === next.startTick) {
                        const lyrics = [block.data.lyrics, next.data.lyrics].map(text => (text || '').trim()).filter(Boolean).join('\n');
                        block.data = { ...block.data, lyrics: lyrics || null };
                        this.blocks = this.blocks.filter(item => item !== next);
                    }
                    // Its start time moves by the same amount (half beats at the song's tempo).
                    const start = this.blockStart(block) + (target - block.startTick) * this.halfBeat;
                    block.startTick = target;
                    block.data = { ...block.data, start: formatStart(start) };
                    this.revision++;
                },
                previousBlock(bar) { const list = this.timedBlocks(); return list[list.indexOf(this.blockAtBar(bar)) - 1] ?? null; },
                nextBlock(bar) { const list = this.timedBlocks(); const block = this.blockAtBar(bar); return block ? list[list.indexOf(block) + 1] ?? null : null; },
                scrollToBar(bar) {
                    this.$nextTick(() => document.querySelector(`[data-bar="${bar}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                },

                // ---- Time: each block starts at its "start" (marked in the system); within it, the BPM ----
                get halfBeat() { return 30 / this.bpm; },
                blockStart(block) {
                    const own = toSeconds(block.data.start);
                    if (own !== null) return own;
                    const before = this.timedBlocks().filter(item => item.startTick < block.startTick && toSeconds(item.data.start) !== null).at(-1);
                    return before ? toSeconds(before.data.start) + (block.startTick - before.startTick) * this.halfBeat : block.startTick * this.halfBeat;
                },
                timeOfTick(tick) {
                    const block = this.blockOfTick(tick);
                    return block ? this.blockStart(block) + (tick - block.startTick) * this.halfBeat : tick * this.halfBeat;
                },
                tickAt(time) {
                    const list = this.timedBlocks();
                    let tick = -1;
                    list.forEach((block, i) => {
                        const start = this.blockStart(block);
                        if (time < start) return;
                        const end = list[i + 1]?.startTick ?? this.ticks.length;
                        tick = Math.min(end - 1, block.startTick + Math.floor((time - start) / this.halfBeat));
                    });
                    return tick;
                },

                // ---- Player: the song's MP3, or its YouTube video ----
                get hasAudio() { return !!audio; },
                setAudioSpeed(speed) {
                    this.audioSpeed = speed;
                    if (audio) audio.playbackRate = speed;
                },
                // Moves the player to a tick without starting it (a playing player goes on from there).
                moveTo(tick) {
                    tick = Math.max(0, Math.min(this.ticks.length - 1, tick));
                    if (this.isPlaying()) return this.playFromTick(tick);
                    this.currentTick = tick;
                    const time = Math.max(0, this.timeOfTick(tick));
                    if (audio) audio.currentTime = time;
                    else if (this.playerReady) this.seekVideo(time, false);
                    this.scrollToBar(this.currentBar);
                },
                get canPlay() { return !!audio || !!this.videoId; },
                isPlaying() { return this.playing; },
                // The player's time. The MP3's time moves in small steps: between them it is estimated from the clock.
                now() {
                    if (!audio) return this.playerReady ? this.videoCurrentTime() : 0;
                    const time = audio.currentTime;
                    if (audio.paused || time !== this.mediaTime) {
                        this.mediaTime = time;
                        this.mediaAt = performance.now();
                        return time;
                    }
                    return time + Math.min(0.1, (performance.now() - this.mediaAt) / 1000) * (audio.playbackRate || 1);
                },
                togglePlay() {
                    if (audio) return audio.paused ? audio.play() : audio.pause();
                    this.toggleVideo();
                },
                playFromTick(tick) {
                    const time = Math.max(0, this.timeOfTick(tick));
                    this.currentTick = tick;
                    if (audio) {
                        audio.currentTime = time;
                        audio.play();
                    } else {
                        this.seekVideo(time);
                    }
                },
                formatTime(seconds) {
                    const total = Math.max(0, Math.floor(seconds || 0));
                    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
                },

                // ---- Chord sheet: the section of the block being played is lit ----
                get currentSheetSection() {
                    const block = this.blockOfTick(this.currentTick);
                    if (!block) return -1;
                    const timed = this.shownSheet.map((section, s) => (section.marker ? null : s)).filter(s => s !== null);
                    return timed[this.timedBlocks().indexOf(block)] ?? -1;
                },
                // The chord being played, lit in the sheet: the n-th chord of the map is the n-th of the sheet (when the
                // sheet has the map's chords; otherwise nothing is lit).
                chordStarts() {
                    const timed = this.timedBlocks();
                    const starts = [];
                    timed.forEach((block, i) => {
                        const end = timed[i + 1] ? timed[i + 1].startTick : this.ticks.length;
                        let last = null;
                        for (let tick = block.startTick; tick < end; tick++) {
                            const chord = this.ticks[tick];
                            if (chord && chord !== last) { starts.push(tick); last = chord; }
                        }
                    });
                    return starts;
                },
                get currentSheetChord() {
                    if (this.currentTick < 0) return -1;
                    const starts = this.chordStarts();
                    if (starts.length !== this.shownSheet.chordCount) return -1;
                    let n = -1;
                    for (const start of starts) {
                        if (start > this.currentTick) break;
                        n++;
                    }
                    return n;
                },
                // Keeps the lit chord in view while playing (in the sheet's own scroll on large screens).
                scrollToSheetChord(n) {
                    this.$nextTick(() => {
                        const chord = document.querySelector(`.sheet-box [data-chord="${n}"]`);
                        if (!chord || !chord.offsetParent) return;
                        const box = chord.closest('.sheet-box');
                        if (box.scrollHeight > box.clientHeight + 4) {
                            const top = chord.getBoundingClientRect().top - box.getBoundingClientRect().top;
                            if (top < box.clientHeight * .15 || top > box.clientHeight * .7) {
                                box.scrollTo({ top: box.scrollTop + top - box.clientHeight * .3, behavior: 'smooth' });
                            }
                        } else if (this.pane === 'sheet') {
                            chord.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        }
                    });
                },
                // ---- Editing the lyrics of a section of the chord sheet (saved on its own) ----
                editSheetSection(s) {
                    if (this.dirty) {
                        this.saveError = true;
                        this.saveState = @js(__('Save the changes to the map before editing the lyrics.'));
                        return;
                    }
                    this.sheetEdit = { index: s, text: (this.sheet[s]?.lines || []).join('\n'), error: '', saving: false };
                },
                async saveSheetSection() {
                    const edit = this.sheetEdit;
                    if (!edit || edit.saving) return;
                    edit.saving = true;
                    edit.error = '';
                    try {
                        const response = await fetch(config.sheetSectionUrl, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ index: edit.index, lines: edit.text.split('\n'), updated_at: this.updatedAt }),
                        });
                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(Object.values(result.errors || {})[0]?.[0] || result.message || @js(__('Something went wrong')));
                        }
                        this.updatedAt = result.updated_at;
                        this.sheet = result.sheet || [];
                        this.sheetView = parseSheet(this.sheet);
                        this.sheetRevision++;
                        // The map's blocks keep the same lyrics (saved with the map later).
                        [...this.blocks].sort((a, b) => a.startTick - b.startTick).forEach((block, i) => {
                            if (i in (result.lyrics || [])) block.data = { ...block.data, lyrics: result.lyrics[i], anchor: result.anchors?.[i] ?? block.data.anchor };
                        });
                        this.sheetEdit = null;
                        this.saveError = false;
                        this.saveState = @js(__('Lyrics saved ✓'));
                    } catch (error) {
                        edit.error = error.message;
                    } finally {
                        edit.saving = false;
                    }
                },
                playFromSheet(s) {
                    const timed = this.shownSheet.map((section, i) => (section.marker ? null : i)).filter(i => i !== null);
                    const block = this.timedBlocks()[timed.indexOf(s)];
                    if (block && this.canPlay) this.playFromTick(block.startTick);
                },
                transposeName(chord) {
                    if (!chord || !this.savedKey || !this.key || this.key === this.savedKey) return chord;
                    return ChordTransposer.transposeChord(chord, this.savedKey, this.key);
                },

                // ---- Versions for instruments: same blocks and times as the base, their own chords ----
                get currentVersion() { return this.versions.find(version => version.id === this.versionId) ?? null; },
                // The chord sheet as shown: in a version, with the version's chords.
                get shownSheet() {
                    if (!this.versionId) return this.sheetView;
                    const key = `${this.versionId}:${this.revision}:${this.sheetRevision}`;
                    if (this.shownSheetCache.key !== key) {
                        this.shownSheetCache = { key, value: parseSheet(sheetWithChords(this.sheet, this.baseStructure, this.toBlocks())) };
                    }
                    return this.shownSheetCache.value;
                },
                // Shows the base or a version on the grid (changes not saved must be saved or undone first).
                selectVersion(id) {
                    if (id === this.versionId) return;
                    if (this.dirty) {
                        this.saveError = true;
                        this.saveState = @js(__('Save (or undo) the changes before switching versions.'));
                        return;
                    }
                    const version = this.versions.find(item => item.id === id);
                    const structure = version ? versionStructure(version, this.savedKey) : this.baseStructure;
                    const parsed = parseStructure(structure);
                    this.ticks = parsed.ticks;
                    this.blocks = parsed.blocks;
                    this.perBar = parsed.perBar;
                    this.versionId = version ? version.id : null;
                    this.key = this.savedKey;
                    this.history = [];
                    this.originalTicks = [...this.ticks];
                    this.selection = { anchor: null, from: null, to: null };
                    this.loop = null;
                    this.editor = null;
                    this.barMenu = null;
                    this.notice = '';
                    this.saveError = false;
                    this.saveState = '';
                    this.revision++;
                },
                openVersionMenu(element) {
                    this.versionError = '';
                    this.versionMenu = true;
                    this.place(this.$refs.versionMenu, element);
                    this.$refs.instrumentName.value = '';
                    this.$nextTick(() => this.$refs.instrumentName.focus());
                },
                async sendJson(method, url, body) {
                    const response = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: body === undefined ? undefined : JSON.stringify(body),
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(Object.values(result.errors || {})[0]?.[0] || result.message || @js(__('Something went wrong')));
                    return result;
                },
                async createVersion() {
                    const instrument = this.$refs.instrumentName.value.trim();
                    if (!instrument) return this.$refs.instrumentName.focus();
                    if (this.dirty) { this.versionError = @js(__('Save (or undo) the changes before switching versions.')); return; }
                    this.versionBusy = true;
                    this.versionError = '';
                    try {
                        const version = await this.sendJson('POST', config.versionStoreUrl, { instrument });
                        this.versions = [...this.versions, version].sort((a, b) => a.instrument.localeCompare(b.instrument));
                        if (!this.instruments.includes(version.instrument)) this.instruments.push(version.instrument);
                        this.versionMenu = false;
                        this.selectVersion(version.id);
                        this.saveState = @js(__('Version created from the base ✓'));
                    } catch (error) {
                        this.versionError = error.message;
                    } finally {
                        this.versionBusy = false;
                    }
                },
                async deleteVersion() {
                    const version = this.currentVersion;
                    if (!version || !confirm(@js(__('Delete the version for :instrument? This cannot be undone.')).replace(':instrument', version.instrument))) return;
                    try {
                        await this.sendJson('DELETE', version.delete_url);
                        this.dirty = false;
                        this.versions = this.versions.filter(item => item.id !== version.id);
                        this.selectVersion(null);
                    } catch (error) {
                        this.saveError = true;
                        this.saveState = error.message;
                    }
                },

                // ---- CSV: one line per bar (bar, block starting in it, the chord of each beat), in the key on screen ----
                csvHeader() {
                    return [@js(__('Bar')), @js(__('Block')), ...Array.from({ length: this.perBar }, (_, i) => `${@js(__('Beat'))} ${i + 1}`)];
                },
                exportCsv() {
                    const size = this.perBar * 2;
                    const cell = (value) => (/[;"\n]/.test(value) ? `"${value.replaceAll('"', '""')}"` : value);
                    const rows = [this.csvHeader()];
                    for (let bar = 0; bar * size < this.ticks.length; bar++) {
                        const names = this.blocks.filter(block => block.startTick >= bar * size && block.startTick < (bar + 1) * size).map(block => {
                            const offset = block.startTick - bar * size;
                            const where = offset ? ` (${@js(__('beat'))} ${Math.floor(offset / 2) + 1}${offset % 2 ? ' e' : ''})` : '';
                            return (this.isMarker(block) ? '↺ ' : '') + this.blockName(block) + where;
                        });
                        const beats = [];
                        for (let i = 0; i < this.perBar; i++) {
                            const tick = bar * size + i * 2;
                            if (tick >= this.ticks.length) break;
                            const on = this.transposeName(this.ticks[tick]) || '';
                            const and = this.transposeName(this.ticks[tick + 1] ?? this.ticks[tick]) || '';
                            const before = tick > 0 ? this.transposeName(this.ticks[tick - 1]) : null;
                            beats.push((on === before ? '/' : on) + (and !== on ? ` ${and}` : ''));
                        }
                        rows.push([String(bar + 1), names.join(' + '), ...beats]);
                    }
                    const text = '\ufeff' + rows.map(row => row.map(cell).join(';')).join('\r\n') + '\r\n';
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(new Blob([text], { type: 'text/csv;charset=utf-8' }));
                    link.download = `${config.title || 'map'}${this.currentVersion ? ' - ' + this.currentVersion.instrument : ''}.csv`;
                    link.click();
                    setTimeout(() => URL.revokeObjectURL(link.href), 1000);
                },
                parseCsv(text) {
                    text = text.replace(/^\ufeff/, '');
                    const first = text.split(/\r?\n/)[0] || '';
                    const delimiter = [';', '\t', ','].sort((a, b) => first.split(b).length - first.split(a).length)[0];
                    const rows = [];
                    let row = [], value = '', quoted = false;
                    for (let i = 0; i < text.length; i++) {
                        const char = text[i];
                        if (quoted) {
                            if (char === '"' && text[i + 1] === '"') { value += '"'; i++; }
                            else if (char === '"') quoted = false;
                            else value += char;
                        } else if (char === '"') quoted = true;
                        else if (char === delimiter) { row.push(value); value = ''; }
                        else if (char === '\n' || char === '\r') {
                            if (char === '\r' && text[i + 1] === '\n') i++;
                            row.push(value); rows.push(row); row = []; value = '';
                        } else value += char;
                    }
                    if (value !== '' || row.length) { row.push(value); rows.push(row); }
                    return rows.filter(cells => cells.some(cell => cell.trim() !== ''));
                },
                async importCsv(file) {
                    if (!file) return;
                    this.csvError = false;
                    try {
                        const rows = this.parseCsv(await file.text());
                        // A first line that does not start with a bar number is the header.
                        if (rows.length && !/^\s*\d+\s*$/.test(rows[0][0] || '')) rows.shift();
                        const size = this.perBar * 2;
                        const bars = Math.ceil(this.ticks.length / size);
                        if (rows.length !== bars) throw new Error(@js(__('The file has :rows bars; this map has :bars. Import needs the same number of bars.')).replace(':rows', rows.length).replace(':bars', bars));
                        const isChord = (name) => /^[A-G][#b]?[^\s\[\]|]*$/.test(name);
                        // Typed in the key on screen; stored in the song's key.
                        const stored = (name) => (this.key && this.savedKey && this.key !== this.savedKey ? ChordTransposer.transposeChord(name, this.key, this.savedKey) : name);
                        const ticks = [];
                        let last = null;
                        rows.forEach((cells, r) => {
                            for (let i = 0; i < this.perBar; i++) {
                                if (r * size + i * 2 >= this.ticks.length) break;
                                const parts = String(cells[2 + i] ?? '').trim().split(/\s+/).filter(Boolean);
                                if (parts.length > 2) throw new Error(@js(__('Bar :bar, beat :beat: at most two chords per beat (on the beat and on the "and").')).replace(':bar', r + 1).replace(':beat', i + 1));
                                const on = parts[0] && parts[0] !== '/' ? parts[0] : last;
                                const and = parts[1] && parts[1] !== '/' ? parts[1] : on;
                                for (const name of [on, and]) {
                                    if (name === null) throw new Error(@js(__('Bar 1, beat 1 needs a chord.')));
                                    if (!isChord(name)) throw new Error(@js(__('Bar :bar, beat :beat: ":chord" is not a chord name.')).replace(':bar', r + 1).replace(':beat', i + 1).replace(':chord', name));
                                }
                                ticks.push(stored(on), stored(and));
                                last = and;
                            }
                        });
                        ticks.length = this.ticks.length;
                        // Blocks (base only): the names in the Block column, at the bar (and beat) where they start;
                        // a block already starting there keeps its lyrics, time and the rest.
                        let blocks = null;
                        if (!this.versionId) {
                            const wanted = [];
                            rows.forEach((cells, r) => String(cells[1] ?? '').split('+').map(item => item.trim()).filter(Boolean).forEach(item => {
                                const marker = item.startsWith('↺');
                                const match = /^(.*?)\s*\((?:\S+)\s+(\d+)(\s*e)?\)\s*$/i.exec(item.replace(/^↺\s*/, ''));
                                const name = (match ? match[1] : item.replace(/^↺\s*/, '')).trim().toUpperCase().replace(/\s+/g, ' ');
                                const offset = match ? (Number(match[2]) - 1) * 2 + (match[3] ? 1 : 0) : 0;
                                wanted.push({ name, marker, startTick: r * size + Math.min(offset, size - 1) });
                            }));
                            const same = JSON.stringify(wanted.map(item => [item.name, item.marker, item.startTick]))
                                === JSON.stringify([...this.blocks].sort((a, b) => a.startTick - b.startTick).map(block => [String(block.data.section || '').replaceAll('_', ' ').toUpperCase(), this.isMarker(block), block.startTick]));
                            if (!same) {
                                const left = [...this.blocks];
                                blocks = wanted.map(item => {
                                    const index = left.findIndex(block => block.startTick === item.startTick && this.isMarker(block) === item.marker);
                                    if (index >= 0) {
                                        const [block] = left.splice(index, 1);
                                        return { ...block, data: { ...block.data, section: item.marker ? block.data.section : item.name } };
                                    }
                                    return item.marker
                                        ? { id: nextId++, startTick: item.startTick, data: { section: item.name, jump: 'start' } }
                                        : { id: nextId++, startTick: item.startTick, data: { section: item.name, start: formatStart(this.timeOfTick(item.startTick)) } };
                                });
                            }
                        }
                        const changed = ticks.filter((chord, i) => chord !== this.ticks[i]).length;
                        this.remember();
                        this.ticks = ticks;
                        if (blocks) this.blocks = blocks;
                        this.revision++;
                        this.csvMessage = @js(__('Imported: :changed half beats changed:blocks. Review and save (or undo).'))
                            .replace(':changed', changed).replace(':blocks', blocks ? @js(__(', blocks updated')) : '');
                    } catch (error) {
                        this.csvError = true;
                        this.csvMessage = error.message;
                    }
                },

                // ---- Editing ----
                setMode(mode) { this.mode = mode; this.editor = null; this.barMenu = null; },
                remember() {
                    this.history.push(JSON.stringify({ ticks: this.ticks, blocks: this.blocks }));
                    if (this.history.length > 80) this.history.shift();
                    this.markDirty();
                },
                undo() {
                    if (!this.history.length) return;
                    const state = JSON.parse(this.history.pop());
                    this.ticks = state.ticks;
                    this.blocks = state.blocks;
                    this.revision++;
                    this.markDirty();
                },
                markDirty() {
                    this.dirty = true;
                    this.saveError = false;
                    this.saveState = @js(__('Not saved yet'));
                },
                clickBeat(bar, beat, cell, event) {
                    if (this.pressDone()) return;
                    if (event?.metaKey || event?.ctrlKey) return this.openBarMenu(bar, cell.closest('.bar'));
                    if (event?.shiftKey) return this.selectTo(bar);
                    this.selectAnchor(bar);
                    const tick = (bar * this.perBar + beat) * 2;
                    if (this.mode === 'play') return this.canPlay ? this.playFromTick(tick) : (this.currentTick = tick);
                    if (!this.isPlaying()) this.currentTick = tick;
                    const hasAnd = this.ticks[tick + 1] !== this.ticks[tick];
                    this.barMenu = null;
                    this.editor = { bar, beat, half: hasAnd ? 1 : 0 };
                    this.place(this.$refs.editor, cell);
                    this.fillEditor();
                    this.$nextTick(() => { this.$refs.editorChord.focus(); this.$refs.editorChord.select(); });
                },
                editorTick() { return (this.editor.bar * this.perBar + this.editor.beat) * 2 + this.editor.half; },
                fillEditor() {
                    this.editorError = '';
                    this.$refs.editorChord.value = this.ticks[this.editorTick()] || '';
                },
                canRemoveChange() {
                    if (!this.editor) return false;
                    const tick = this.editorTick();
                    return tick > 0 && this.ticks[tick - 1] !== this.ticks[tick];
                },
                // A chord typed at a tick goes on until the chord changes (at most to the end of its bar).
                setFrom(tick, chord) {
                    this.remember();
                    const old = this.ticks[tick];
                    const end = (Math.floor(tick / (this.perBar * 2)) + 1) * this.perBar * 2;
                    for (let t = tick; t < Math.min(end, this.ticks.length) && (t === tick || this.ticks[t] === old); t++) this.ticks[t] = chord;
                    this.revision++;
                },
                applyChord() {
                    const typed = this.$refs.editorChord.value.trim();
                    if (!/^[A-G][#b]?[^\s\[\]|]*$/.test(typed)) {
                        this.editorError = @js(__('Use a chord name, e.g. G, Am7, C/E, D4.'));
                        return;
                    }
                    // Typed in the key on screen; stored in the song's key.
                    const chord = this.key && this.savedKey && this.key !== this.savedKey ? ChordTransposer.transposeChord(typed, this.key, this.savedKey) : typed;
                    const tick = this.editorTick();
                    this.editor = null;
                    this.setFrom(tick, chord);
                },
                removeChange() {
                    const tick = this.editorTick();
                    this.editor = null;
                    this.setFrom(tick, this.ticks[tick - 1]);
                },

                // ---- Marked bars, copying and pasting chords, restoring, repeating ----
                get hasSelection() { return this.selection.to !== null; },
                isSelected(bar) { return this.hasSelection && bar >= this.selection.from && bar <= this.selection.to; },
                barsLabel(from, to) {
                    return from === to ? `${@js(__('bar'))} ${from + 1}` : `${@js(__('bars'))} ${from + 1}–${to + 1} (${to - from + 1})`;
                },
                // Bar the keys and the passage bar act on: the one being played (or clicked), else the first.
                cursorBar() { return this.currentBar >= 0 ? this.currentBar : (this.selection.anchor ?? 0); },
                // The marked bars when `bar` is one of them (or no bar is given), otherwise `bar` alone.
                barsFor(bar) {
                    if (this.hasSelection && (bar === undefined || this.isSelected(bar))) return { from: this.selection.from, to: this.selection.to };
                    const one = bar ?? this.cursorBar();
                    return { from: one, to: one };
                },
                menuBarsLabel() { const { from, to } = this.barsFor(this.barMenu.bar); return this.barsLabel(from, to); },
                // The tap that ends a long press (which opened the bar's menu) does nothing else.
                pressDone() {
                    if (!this.pressOpened) return false;
                    this.pressOpened = false;
                    return true;
                },
                clickBar(bar, event) {
                    if (this.pressDone()) return;
                    if (event.metaKey || event.ctrlKey) return this.openBarMenu(bar, event.currentTarget);
                    if (event.shiftKey) return this.selectTo(bar);
                    this.selectAnchor(bar);
                    if (this.mode === 'play' && this.canPlay) this.playFromTick(bar * this.perBar * 2);
                    else this.moveTo(bar * this.perBar * 2);
                },
                selectAnchor(bar) {
                    this.selection = { anchor: bar, from: null, to: null };
                    // Going out of the passage being repeated stops the repetition (otherwise it would jump back).
                    if (this.loop && (bar < this.loop.from || bar > this.loop.to)) this.loop = null;
                },
                selectTo(bar) {
                    const anchor = this.selection.anchor ?? Math.max(0, this.currentBar);
                    this.selection = { anchor, from: Math.min(anchor, bar), to: Math.max(anchor, bar) };
                    if (this.loop) this.loop = { from: this.selection.from, to: this.selection.to };
                },
                clearSelection() { this.selection = { anchor: this.selection.anchor, from: null, to: null }; },
                copyBars(bar) {
                    const { from, to } = this.barsFor(bar);
                    const size = this.perBar * 2;
                    this.clipboard = { ticks: this.ticks.slice(from * size, (to + 1) * size), offset: 0, label: this.barsLabel(from, to) };
                    this.notice = @js(__('Copied: :what. Go to the bar where it goes and paste.')).replace(':what', this.clipboard.label);
                },
                copyBlock(bar) {
                    const block = this.blockOfTick((bar ?? this.cursorBar()) * this.perBar * 2) ?? this.timedBlocks()[0];
                    if (!block) return;
                    const timed = this.timedBlocks();
                    const end = timed[timed.indexOf(block) + 1]?.startTick ?? this.ticks.length;
                    const size = this.perBar * 2;
                    const bars = Math.ceil(end / size) - Math.floor(block.startTick / size);
                    this.clipboard = {
                        ticks: this.ticks.slice(block.startTick, end),
                        offset: block.startTick % size,
                        label: `${this.blockName(block)} (${bars} ${@js(__('bars'))})`,
                    };
                    this.notice = @js(__('Copied: :what. Go to the bar where it goes and paste.')).replace(':what', this.clipboard.label);
                },
                // `withRhythm`: the copied chords as they are (where each one changes comes along). Without it, only
                // the names: the changes here stay where they are and take the copied chords in order (or the rhythm
                // too, when the passage here has another number of changes).
                paste(bar, withRhythm) {
                    if (!this.clipboard) return;
                    const from = bar * this.perBar * 2 + this.clipboard.offset;
                    const to = Math.min(this.ticks.length, from + this.clipboard.ticks.length);
                    if (from >= to) return;
                    const here = this.ticks.slice(from, to);
                    const copied = this.clipboard.ticks.slice(0, to - from);
                    const changes = (list) => list.filter((chord, i) => i === 0 || chord !== list[i - 1]).length;
                    const rhythm = withRhythm || changes(here) !== changes(copied);
                    let pasted = copied;
                    if (!rhythm) {
                        const names = copied.filter((chord, i) => chord && chord !== copied[i - 1]);
                        let change = -1;
                        pasted = here.map((chord, i) => {
                            if (i === 0 || chord !== here[i - 1]) change++;
                            return names[change] ?? chord;
                        });
                    }
                    this.remember();
                    pasted.forEach((chord, i) => { this.ticks[from + i] = chord; });
                    this.revision++;
                    const where = `${@js(__('from bar'))} ${bar + 1}`;
                    this.notice = (rhythm ? @js(__('Pasted with the rhythm: :what, :where.')) : @js(__('Pasted: :what, :where.')))
                        .replace(':what', this.clipboard.label).replace(':where', where)
                        + (rhythm && !withRhythm ? ' ' + @js(__('(The bars here had another number of chord changes, so the rhythm came along too.)')) : '')
                        + (to - from < this.clipboard.ticks.length ? ' ' + @js(__('(Cut at the end of the song.)')) : '');
                },
                restoreBars(bar) {
                    const { from, to } = this.barsFor(bar);
                    const size = this.perBar * 2;
                    if (this.originalTicks.length !== this.ticks.length) return;
                    this.remember();
                    for (let tick = from * size; tick < Math.min((to + 1) * size, this.ticks.length); tick++) this.ticks[tick] = this.originalTicks[tick];
                    this.revision++;
                    this.notice = @js(__('Restored: :what, as when the page was opened (or last saved).')).replace(':what', this.barsLabel(from, to));
                },
                toggleLoop(bar) {
                    if (this.loop) { this.loop = null; return; }
                    this.loop = this.barsFor(bar);
                    if (this.canPlay) this.playFromTick(this.loop.from * this.perBar * 2);
                },
                // Back to the start of the repeated passage when playing reaches its end (or left it).
                keepLoop() {
                    if (!this.loop || performance.now() < this.loopCooldown) return;
                    const size = this.perBar * 2;
                    const start = this.timeOfTick(this.loop.from * size);
                    const end = this.timeOfTick(Math.min((this.loop.to + 1) * size, this.ticks.length));
                    if (this.clock >= end - 0.03 || this.clock < start - 0.5) {
                        // The player reports the old time for a moment after a jump: no new jump meanwhile.
                        this.loopCooldown = performance.now() + (audio ? 250 : 1000);
                        this.playFromTick(this.loop.from * size);
                    }
                },
                // Metronome: the clicks of the next moments are scheduled ahead on the audio clock, so each one
                // sounds right on its beat (clicking when a frame notices the beat passed comes late).
                clickUntil: null,
                scheduleClicks() {
                    if (!this.metronome || !this.isPlaying()) { this.clickUntil = null; return; }
                    let context;
                    try {
                        context = this.clickContext = this.clickContext || new AudioContext();
                        if (context.state === 'suspended') context.resume();
                    } catch (e) { return; }
                    const now = this.clock;
                    const rate = audio ? (audio.playbackRate || 1) : (this.video?.speed || 1);
                    // Started, or jumped (seek, repeat): scheduling starts again from here.
                    if (this.clickUntil === null || now < this.clickUntil - 0.25 || now > this.clickUntil + 0.25) this.clickUntil = now - 0.005;
                    let until = now + 0.15;
                    if (this.loop) until = Math.min(until, this.timeOfTick(Math.min((this.loop.to + 1) * this.perBar * 2, this.ticks.length)) - 0.001);
                    for (let tick = Math.max(0, this.tickAt(this.clickUntil)); tick < this.ticks.length; tick++) {
                        const time = this.timeOfTick(tick);
                        if (time > until) break;
                        if (tick % 2 || time <= this.clickUntil) continue;
                        this.click(tick % (this.perBar * 2) === 0, context, context.currentTime + Math.max(0, (time - now) / rate));
                    }
                    this.clickUntil = Math.max(this.clickUntil, until);
                },
                click(accent, context, when) {
                    try {
                        const oscillator = context.createOscillator();
                        const gain = context.createGain();
                        oscillator.frequency.value = accent ? 1600 : 1000;
                        gain.gain.setValueAtTime(0.4, when);
                        gain.gain.exponentialRampToValueAtTime(0.001, when + 0.05);
                        oscillator.connect(gain).connect(context.destination);
                        oscillator.start(when);
                        oscillator.stop(when + 0.06);
                    } catch (e) {}
                },
                // Enter: edits the chord of the current beat.
                editCurrentBeat() {
                    const bar = this.cursorBar();
                    const beat = Math.max(0, this.currentBeat);
                    const cell = document.querySelector(`[data-bar="${bar}"] .beat:nth-child(${beat + 1})`);
                    if (!cell) return;
                    const hasAnd = this.ticks[(bar * this.perBar + beat) * 2 + 1] !== this.ticks[(bar * this.perBar + beat) * 2];
                    this.barMenu = null;
                    this.editor = { bar, beat, half: hasAnd ? 1 : 0 };
                    this.place(this.$refs.editor, cell);
                    this.fillEditor();
                    this.$nextTick(() => { this.$refs.editorChord.focus(); this.$refs.editorChord.select(); });
                },

                // ---- Lines of the map and passing chords (the bars do not change) ----
                // The chords of a block, from its ticks (a chord held over a bar line counts once).
                blockChords(block) {
                    const timed = this.timedBlocks();
                    const next = timed[timed.indexOf(block) + 1];
                    const chords = [];
                    this.ticks.slice(block.startTick, next ? next.startTick : this.ticks.length).forEach(chord => {
                        if (chord && chord !== chords.at(-1)) chords.push(chord);
                    });
                    return chords;
                },
                // Line breaks and passing chords of a block, valid while it has the chords they were set for.
                lineState(block) {
                    const chords = this.blockChords(block);
                    const valid = JSON.stringify(chords) === JSON.stringify(block.data.lineChords || null);
                    return { chords, valid, breaks: valid ? (block.data.breaks || []) : [], passing: valid ? (block.data.passing || []) : [] };
                },
                withBreaks(chords, breaks) { return chords.flatMap((chord, i) => (breaks.includes(i) && i > 0 ? ['|', chord] : [chord])); },
                get lineBlocks() {
                    const timed = this.timedBlocks();
                    const states = timed.map(block => this.lineState(block));
                    const cycles = songCyclesOf(states.map(state => this.withBreaks(state.chords, state.breaks)), states.map(state => state.passing));
                    return timed.map((block, index) => {
                        const state = states[index];
                        const lines = chordSegments(this.withBreaks(state.chords, state.breaks), cycles, state.passing)
                            .map(line => line.map(step => step.chords.map((name, i) => ({ name, position: step.positions[i], passing: state.passing.includes(step.positions[i]) }))));
                        return { block, name: this.blockName(block), manual: state.breaks.length > 0, lines, cycles };
                    });
                },
                // Chord being played in the lines view: its block and position (chord index in the block).
                get linePlaying() {
                    if (this.currentTick < 0) return null;
                    const block = this.blockOfTick(this.currentTick);
                    if (!block) return null;
                    let position = -1, last = null;
                    for (let tick = block.startTick; tick <= this.currentTick; tick++) {
                        const chord = this.ticks[tick];
                        if (chord && chord !== last) { position++; last = chord; }
                    }
                    return position < 0 ? null : { id: block.id, position };
                },
                // Keeps the chord being played in view in the lines view.
                scrollToLineChord() {
                    const playing = this.linePlaying;
                    if (!playing) return;
                    this.$nextTick(() => {
                        const element = document.querySelector(`[data-line-chord="${playing.id}-${playing.position}"]`);
                        if (!element) return;
                        const box = element.getBoundingClientRect();
                        if (box.top < window.innerHeight * .2 || box.bottom > window.innerHeight * .8) {
                            window.scrollBy({ top: box.top - window.innerHeight * .35, behavior: 'smooth' });
                        }
                    });
                },
                // Starts editing the lines of a block whose chords changed: none set yet.
                linesFor(block) {
                    const state = this.lineState(block);
                    if (!state.valid) block.data = { ...block.data, lineChords: state.chords, breaks: [], passing: [] };
                    return state;
                },
                clickLineChord(block, position) {
                    const item = this.lineBlocks.find(entry => entry.block === block);
                    this.remember();
                    this.linesFor(block);
                    if (this.lineTool === 'passing') {
                        const passing = block.data.passing || [];
                        block.data = { ...block.data, passing: passing.includes(position) ? passing.filter(p => p !== position) : [...passing, position].sort((a, b) => a - b) };
                    } else {
                        // Automatic lines become the starting point of the lines set by hand.
                        let breaks = block.data.breaks || [];
                        if (!breaks.length && item) breaks = item.lines.slice(1).map(line => line[0]?.[0]?.position).filter(p => p > 0);
                        if (position > 0) breaks = breaks.includes(position) ? breaks.filter(p => p !== position) : [...breaks, position].sort((a, b) => a - b);
                        block.data = { ...block.data, breaks };
                    }
                    this.revision++;
                },
                automaticLines(block) {
                    this.remember();
                    this.linesFor(block);
                    block.data = { ...block.data, breaks: [] };
                    this.revision++;
                },

                // ---- Blocks ----
                startPress(bar, event) {
                    if (event.pointerType !== 'touch') return;
                    const target = event.currentTarget;
                    this.pressTimer = setTimeout(() => { this.pressTimer = null; this.pressOpened = true; this.openBarMenu(bar, target); }, 550);
                },
                endPress() { clearTimeout(this.pressTimer); },
                openBarMenu(bar, element) {
                    this.editor = null;
                    this.barMenu = { bar };
                    this.place(this.$refs.barMenu, element);
                    const block = this.blockAtBar(bar);
                    this.$refs.blockName.value = block ? this.blockName(block) : '';
                    this.$nextTick(() => this.$refs.blockName.focus());
                },
                barMenuTitle() {
                    if (!this.barMenu) return '';
                    const block = this.blockAtBar(this.barMenu.bar);
                    const offset = this.blockOffset(this.barMenu.bar);
                    const where = offset ? ` (${@js(__('beat'))} ${Math.floor(offset / 2) + 1}${offset % 2 ? ' e' : ''})` : '';
                    return `${@js(__('Bar'))} ${this.barMenu.bar + 1}` + (block ? ` · ${this.blockName(block)}${where}` : ` · ${@js(__('new block from here'))}`);
                },
                saveBlockName() {
                    const name = this.$refs.blockName.value.trim().toUpperCase().replace(/\s+/g, ' ');
                    if (!name) return this.$refs.blockName.focus();
                    const bar = this.barMenu.bar;
                    this.barMenu = null;
                    this.remember();
                    const block = this.blockAtBar(bar);
                    if (block) {
                        block.data = { ...block.data, section: name };
                    } else {
                        const startTick = bar * this.perBar * 2;
                        // A new block takes no lyrics: they stay with the block it comes from. Its start comes from
                        // the bar's position in the song.
                        this.blocks.push({ id: nextId++, startTick, data: { section: name, start: formatStart(this.timeOfTick(startTick)) } });
                        this.blocks.sort((a, b) => a.startTick - b.startTick || (this.isMarker(a) ? -1 : 0));
                    }
                    this.revision++;
                },
                // Joining: the separation goes away and the block that stays keeps the lyrics of both.
                join(kept, gone) {
                    this.remember();
                    const lyrics = [kept.data.lyrics, gone.data.lyrics].map(text => (text || '').trim()).filter(Boolean).join('\n');
                    kept.data = { ...kept.data, lyrics: lyrics || null };
                    this.blocks = this.blocks.filter(block => block !== gone);
                    this.revision++;
                },
                joinPrevious() {
                    const bar = this.barMenu.bar;
                    this.barMenu = null;
                    this.join(this.previousBlock(bar), this.blockAtBar(bar));
                },
                joinNext() {
                    const bar = this.barMenu.bar;
                    this.barMenu = null;
                    this.join(this.blockAtBar(bar), this.nextBlock(bar));
                },
                place(popover, element) {
                    this.$nextTick(() => {
                        const box = element.getBoundingClientRect();
                        const parent = popover.offsetParent?.getBoundingClientRect() ?? { left: 0, top: 0 };
                        const width = popover.offsetWidth || 320;
                        popover.style.left = `${Math.max(8, Math.min(box.left, window.innerWidth - width - 12)) - parent.left}px`;
                        popover.style.top = `${box.bottom + 6 - parent.top}px`;
                    });
                },

                // ---- Key and tempo ----
                keyLabel(key) {
                    const name = key || '—';
                    return name;
                },
                changeKey(key) {
                    if (!key || key === this.key) return;
                    this.key = key;
                    this.markDirty();
                },
                setBpm(value) {
                    const bpm = Math.round(Number(value) * 10) / 10;
                    if (!(bpm >= 30 && bpm <= 300) || bpm === this.bpm) return;
                    this.bpm = bpm;
                    this.markDirty();
                },

                // ---- Saving: the ticks of each block back to chords and durations in beats ----
                toBlocks() {
                    const list = [...this.blocks].sort((a, b) => a.startTick - b.startTick);
                    const timed = list.filter(block => !this.isMarker(block));
                    const transpose = (chord) => (this.key && this.savedKey && this.key !== this.savedKey ? ChordTransposer.transposeChord(chord, this.savedKey, this.key) : chord);
                    return list.map(block => {
                        const { lineChords, breaks, ...data } = block.data;
                        if (this.isMarker(block)) return { ...data, chords: [] };
                        const next = timed[timed.indexOf(block) + 1];
                        const chords = [];
                        const durations = [];
                        this.ticks.slice(block.startTick, next ? next.startTick : this.ticks.length).forEach(chord => {
                            if (chords.length && (chord === chords.at(-1) || !chord)) durations[durations.length - 1] += 0.5;
                            else if (chord) { chords.push(chord); durations.push(0.5); }
                        });
                        // Line breaks and passing chords go back while the block has the chords they were set for.
                        const state = this.lineState(block);
                        if (state.valid && state.passing.length) data.passing = state.passing;
                        else delete data.passing;
                        const out = state.valid ? this.withBreaks(chords, state.breaks) : chords;
                        return { ...data, chords: out.map(chord => (chord === '|' ? chord : transpose(chord))), durations, beats_per_bar: this.perBar, start: formatStart(this.blockStart(block)) };
                    });
                },
                async save() {
                    if (this.saving) return;
                    if (this.versionId) return this.saveVersion();
                    this.saving = true;
                    this.saveState = @js(__('Saving…'));
                    try {
                        const response = await fetch(config.saveUrl, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ blocks: this.toBlocks(), updated_at: this.updatedAt, bpm: this.bpm, musical_key: this.key }),
                        });
                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(result.message || @js(__('Something went wrong')));
                        this.updatedAt = result.updated_at;
                        // Stored in the new key from now on.
                        if (this.key !== this.savedKey) {
                            this.ticks = this.ticks.map(chord => this.transposeName(chord));
                            this.blocks.forEach(block => {
                                if (block.data.lineChords) block.data.lineChords = block.data.lineChords.map(chord => this.transposeName(chord));
                            });
                            this.savedKey = this.key;
                        }
                        // Blocks whose chords changed were saved without lines and passing chords: they start over.
                        this.timedBlocks().forEach(block => this.linesFor(block));
                        this.sheet = result.sheet || [];
                        this.sheetView = parseSheet(this.sheet);
                        this.sheetRevision++;
                        this.originalTicks = [...this.ticks];
                        this.baseStructure = this.toBlocks();
                        this.dirty = false;
                        this.saveError = false;
                        this.saveState = result.sheet_updated ? @js(__('Saved ✓ (chord sheet updated too)')) : @js(__('Saved ✓'));
                    } catch (error) {
                        this.saveError = true;
                        this.saveState = error.message;
                    } finally {
                        this.saving = false;
                    }
                },

                // A version: only its chords (in the song's key), at its own address.
                async saveVersion() {
                    const version = this.currentVersion;
                    this.saving = true;
                    this.saveState = @js(__('Saving…'));
                    try {
                        const saved = await this.sendJson('PUT', version.update_url, { blocks: this.toBlocks(), updated_at: version.updated_at, musical_key: this.savedKey });
                        this.versions = this.versions.map(item => (item.id === saved.id ? saved : item));
                        this.timedBlocks().forEach(block => this.linesFor(block));
                        this.originalTicks = [...this.ticks];
                        this.dirty = false;
                        this.saveError = false;
                        this.saveState = @js(__('Version saved ✓'));
                    } catch (error) {
                        this.saveError = true;
                        this.saveState = error.message;
                    } finally {
                        this.saving = false;
                    }
                },

                onKey(event) {
                    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) return;
                    const key = event.key.toLowerCase();
                    if (event.metaKey || event.ctrlKey) {
                        if (key === 'z') { event.preventDefault(); return this.undo(); }
                        if (key === 's') { event.preventDefault(); return this.save(); }
                        // Copy / paste chords, unless some text is selected on the page.
                        if ((key === 'c' || key === 'v') && !window.getSelection().toString()) {
                            event.preventDefault();
                            return key === 'c' ? this.copyBars() : this.paste(this.cursorBar(), false);
                        }
                        return;
                    }
                    if (event.altKey || this.editor || this.barMenu || this.sheetEdit) return;
                    if (event.code === 'Space' && this.canPlay) { event.preventDefault(); return this.togglePlay(); }
                    if (event.shiftKey && key === 'r') { event.preventDefault(); return this.restoreBars(); }
                    if (event.key === 'Escape') return this.clearSelection();
                    if (event.key === 'Enter' && this.mode === 'edit') { event.preventDefault(); return this.editCurrentBeat(); }
                    const size = this.perBar * 2;
                    const tick = Math.max(0, this.currentTick);
                    const beatStart = tick - (tick % 2);
                    const barStart = tick - (tick % size);
                    const moves = {
                        ArrowLeft: this.currentTick < 0 ? 0 : beatStart - 2,
                        ArrowRight: this.currentTick < 0 ? 0 : beatStart + 2,
                        ArrowUp: barStart - size,
                        ArrowDown: this.currentTick < 0 ? 0 : barStart + size,
                        PageUp: barStart - 4 * size,
                        PageDown: barStart + 4 * size,
                    };
                    if (event.key in moves) {
                        event.preventDefault();
                        this.moveTo(moves[event.key]);
                        this.selectAnchor(this.currentBar);
                    }
                },
            }, audio ? null : config.youtubeUrl);
        }
    </script>
</x-app-layout>
