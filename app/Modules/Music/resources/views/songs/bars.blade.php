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
        /* Each block on its own rows, under its name (as the map on the public page) */
        .bar-group-head { grid-column: 1 / -1; display: flex; align-items: center; gap: .35rem; margin-top: .55rem; padding: 0 .1rem .15rem; border-bottom: 2px solid var(--type-color, #4f46e5); }
        .bars-grid > div:first-child > .bar-group-head { margin-top: 0; }
        .bar-group-head .group-name { display: inline-flex; align-items: center; gap: .3rem; padding: .1rem .2rem; border: 0; border-radius: .3rem; background: none; color: var(--type-color, #4f46e5); font-size: .78rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; cursor: pointer; }
        .bar-group-head .group-name:hover { background: #eef2ff; }
        .bar-group-head .group-name i { font-size: .65rem; }
        .bar-group-head .group-repeat { font-size: .7rem; font-weight: 700; color: #9ca3af; }
        .bar-group-head .group-bars { font-size: .7rem; color: #9ca3af; }
        .bar-group-head.is-accordion { cursor: pointer; }
        .bar-group-head.is-accordion:hover { background: #f8fafc; }
        .bar-group-head .group-repeat.is-grouped { padding: .05rem .4rem; border-radius: 999px; background: var(--type-color, #4f46e5); color: #fff; }
        .bar-group-head .group-toggle { border: 0; background: none; padding: .1rem .3rem; border-radius: .3rem; color: #6b7280; font-size: .7rem; font-weight: 600; text-decoration: underline; cursor: pointer; }
        .bar-group-head .group-toggle:hover { color: #4f46e5; }
        .bar.is-group-start { grid-column-start: 1; }
        .add-block { margin-top: .9rem; }
        .add-block-open { width: 100%; padding: .7rem; border: 2px dashed #d1d5db; border-radius: .6rem; background: none; color: #6b7280; font-weight: 700; cursor: pointer; }
        .add-block-open:hover { border-color: #4f46e5; color: #4f46e5; }
        .add-block-form { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding: .6rem; border: 1px solid #c7d2fe; border-radius: .6rem; background: #f8fafc; }
        .add-block-form input[type=text] { flex: 1 1 12rem; min-width: 0; padding: .4rem .55rem; border: 1px solid #d1d5db; border-radius: .4rem; font-weight: 700; }
        .add-block-form label { display: inline-flex; align-items: center; gap: .35rem; font-size: .85rem; color: #4b5563; }
        .add-block-form input[type=number] { width: 4.5rem; padding: .35rem .4rem; border: 1px solid #d1d5db; border-radius: .4rem; text-align: center; font-weight: 700; }
        .bar-group-head .group-grip { color: #9ca3af; cursor: grab; padding: .1rem .15rem; touch-action: none; }
        .bar-group-head .group-action { border: 0; background: none; padding: .15rem .35rem; border-radius: .3rem; color: #9ca3af; font-size: .75rem; cursor: pointer; }
        .bar-group-head .group-duplicate { margin-left: auto; }
        .bar-group-head .group-duplicate:hover { background: #eef2ff; color: #4f46e5; }
        .bar-group-head .group-remove:hover { background: #fef2f2; color: #dc2626; }
        .bar-group-head .group-remove:disabled { opacity: .3; cursor: default; }
        /* Moving a block: the bars fold away and only the heads show */
        .bars-page.is-moving-block, .bars-page.is-moving-block * { cursor: grabbing !important; user-select: none; }
        .bars-page.is-moving-block .bars-grid .bar { display: none; }
        .bars-page.is-moving-block .bar-group-head { margin-top: .2rem; padding: .35rem .2rem; border: 1px solid #e5e7eb; border-left: 4px solid var(--type-color, #4f46e5); border-radius: .45rem; background: #fff; }
        .bar-group-head.is-dragging { opacity: .45; }
        .bar-group-head.drop-before { box-shadow: 0 -3px 0 0 #4f46e5; }
        .bar-group-head.drop-after { box-shadow: 0 3px 0 0 #4f46e5; }
        @container (min-width: 34rem) { .bars-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @container (min-width: 46rem) { .bars-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

        .bar { position: relative; min-width: 0; padding: .3rem .4rem .45rem; border: 1px solid #e5e7eb; border-radius: .5rem; background: #fff; cursor: pointer; user-select: none; -webkit-touch-callout: none; }
        .bar:hover { border-color: #c7d2fe; }
        .bar.is-current { border-color: #f59e0b; box-shadow: 0 0 0 2px #f59e0b inset; background: #fffbeb; }
        .bar.has-block { border-top: 3px solid var(--type-color, #4f46e5); }
        .bar .number { position: absolute; top: .15rem; right: .35rem; font-size: .62rem; color: #9ca3af; }
        .review-done { color: #15803d; border-color: #86efac; background: #f0fdf4; }
        .review-save { color: #15803d; border-color: #86efac; }
        .bar-tools { position: absolute; top: .05rem; right: 1.35rem; display: flex; gap: .1rem; }
        .bar-tools button { border: 0; background: none; padding: .1rem .2rem; font-size: .68rem; color: #c7cbd3; cursor: pointer; line-height: 1; }
        .bar-tools button:hover { color: #4f46e5; }
        .bar-tools button:last-child:hover { color: #dc2626; }
        .bar-tools button:disabled { opacity: .3; cursor: default; }
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
        .block-mark { display: inline-flex; align-items: center; gap: .25rem; padding: .05rem .3rem; border: 1px dashed #9ca3af; border-radius: .3rem; background: #fff; color: #6b7280; font-size: .68rem; font-weight: 700; font-variant-numeric: tabular-nums; cursor: pointer; }
        .block-mark.is-marked { border-style: solid; border-color: #4f46e5; color: #4f46e5; background: #eef2ff; }
        .popover .help { margin: .35rem 0 0; font-size: .75rem; color: #6b7280; }
        /* Toolbar: icons only (each one says what it does in its title); numbers stay */
        .ws-toolbar .ws-label { display: none; }
        .ws-toolbar .bpm-input { width: 3.6rem; }
        @media (max-width: 640px) { .ws-toolbar .ws-spacer { display: none; } .ws-toolbar .bpm-input { width: 3rem; } }
        .popover hr { margin: .5rem 0 .1rem; border: 0; border-top: 1px solid #e5e7eb; }
        /* Passing chords (marked in the bar menu): underlined, as on the public page */
        .chord.is-passing { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .2em; }
        /* The menu of a block, next to its name in the bars */
        .block-more { border: 0; background: none; padding: 0 .25rem; border-radius: .3rem; color: #9ca3af; font-size: .75rem; cursor: pointer; }
        .block-more:hover { background: #eef2ff; color: #4f46e5; }
        .menu-chords { display: flex; flex-wrap: wrap; gap: .3rem; }
        .menu-chords button { padding: .2rem .55rem; border: 1px solid #d1d5db; border-radius: 999px; background: #fff; font-weight: 700; font-size: .85rem; cursor: pointer; }
        .menu-chords button.is-on { border-color: #4f46e5; background: #eef2ff; color: #4f46e5; text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .2em; }
        /* The menu of a block: its name and place on top, then name, bars, repetitions and start time */
        .block-menu-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; margin: -.65rem -.65rem .55rem; padding: .55rem .65rem .5rem; border-bottom: 3px solid var(--type-color, #4f46e5); border-radius: .6rem .6rem 0 0; background: #f8fafc; }
        .block-menu-name { margin: 0; color: var(--type-color, #4f46e5); font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .block-menu-place { margin: .1rem 0 0; font-size: .72rem; color: #6b7280; }
        .block-menu-close { border: 0; background: none; padding: .1rem .3rem; border-radius: .3rem; color: #9ca3af; cursor: pointer; }
        .block-menu-close:hover { background: #e5e7eb; color: #374151; }
        .block-menu-field { margin-top: .55rem; }
        .block-menu-label { display: block; margin-bottom: .2rem; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        .block-menu-name-row { display: flex; gap: .35rem; }
        .block-menu-name-row input { flex: 1; min-width: 0; }
        .block-menu-name-row .ws-btn { flex: none; min-width: 2.6rem; justify-content: center; }
        .block-menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
        .stepper { display: flex; align-items: stretch; border: 1px solid #d1d5db; border-radius: .45rem; overflow: hidden; }
        .stepper button { flex: none; width: 2.1rem; border: 0; background: #f3f4f6; color: #374151; font-size: .75rem; cursor: pointer; }
        .stepper button:hover:not(:disabled) { background: #e0e7ff; color: #4f46e5; }
        .stepper button:disabled { opacity: .4; cursor: default; }
        .stepper input { flex: 1; min-width: 0; width: 100%; border: 0; padding: .35rem 0; text-align: center; font-weight: 700; -moz-appearance: textfield; }
        .stepper input::-webkit-inner-spin-button, .stepper input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .block-menu-time { display: flex; gap: .3rem; }
        .block-menu-time input { flex: 1; min-width: 0; padding: .35rem .4rem; border: 1px solid #d1d5db; border-radius: .45rem; text-align: center; font-weight: 700; font-variant-numeric: tabular-nums; }
        .block-menu-time .nudge { flex: none; padding: 0 .45rem; border: 1px solid #d1d5db; border-radius: .45rem; background: #fff; color: #4b5563; font-size: .72rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .block-menu-time .nudge:hover { border-color: #4f46e5; color: #4f46e5; }
        .block-menu-time .now { color: #4f46e5; }
        .link-btn { margin-top: .3rem; padding: 0; border: 0; background: none; color: #4f46e5; font-size: .75rem; text-decoration: underline; cursor: pointer; }
        .chord-picker .block-menu-field:first-of-type { margin-top: .45rem; }
        .menu-chords button.is-current { border-color: var(--type-color, #4f46e5); background: var(--type-color, #4f46e5); color: #fff; }
        .menu-chords.is-key button { border-style: dashed; color: #4b5563; }
        .menu-chords button:hover { border-color: #4f46e5; color: #4f46e5; }
        .menu-chords button.is-current:hover { color: #fff; }
        .repeat-input { width: 3.6rem; padding: .15rem .35rem; border: 1px solid #d1d5db; border-radius: .35rem; font-weight: 700; text-align: center; }
        .beat.is-on { background: #f59e0b; }
        .beat.is-on .chord, .beat.is-on .count { color: #fff; }
        .bars-page.is-editing .beat { cursor: text; }
        .inline-chord { width: 100%; min-width: 0; padding: 0 .2rem; border: 1px solid #4f46e5; border-radius: .25rem; font: inherit; font-weight: 800; font-size: .95rem; background: #fff; outline: none; }
        .inline-chord.is-invalid { border-color: #dc2626; background: #fef2f2; }
        .bars-page.is-editing .chord.can-drag { cursor: grab; touch-action: none; border-radius: .2rem; }
        .bars-page.is-editing .chord.can-drag:hover { outline: 1px dashed #4f46e5; }
        .bars-page.is-dragging, .bars-page.is-dragging * { cursor: grabbing !important; user-select: none; }
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
        .sheet-seg .c.can-move { cursor: grab; touch-action: none; }
        .sheet-seg .c.is-added > span { color: #16a34a; text-decoration: underline; text-underline-offset: .15em; }
        .sheet-preview-note { margin: 0 0 .4rem; font-size: .75rem; color: #b45309; }
        .sheet-seg .c.can-move:hover > span { outline: 1px dashed #4f46e5; border-radius: .2rem; }
        .sheet-seg .c.is-moving > span { padding: 0 .2rem; margin: 0 -.2rem; border-radius: .25rem; background: #f59e0b; color: #fff; }
        .chord-move { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin: .3rem 0 .4rem; font-size: .78rem; }
        .chord-move .ws-btn { padding: .15rem .5rem; }
        .chord-move .error { color: #dc2626; }
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
        /* Long labels wrap inside their button instead of running over the next one */
        .popover .row .ws-btn { min-width: 0; white-space: normal; line-height: 1.2; text-align: center; padding-top: .35rem; padding-bottom: .35rem; }
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

    @include('music::songs.partials.workspace')

    <div class="ws-page">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="ws-card bg-white shadow sm:rounded-lg">
                @include('music::songs.partials.tabs', ['song' => $song, 'current' => 'layout'])
                @include('layouts.alert')

                <div class="bars-page" x-cloak :class="{ 'is-editing': mode === 'edit', 'is-dragging': drag && drag.moved, 'is-moving-block': groupDrag?.moved }" x-data="songBars(@js([
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
                    'reviewedAt' => $song->reviewed_at?->toIso8601String(),
                ]))" @keydown.window="onKey($event)">

                    {{-- Toolbar: player, play / edit, undo, tempo, key, save, options --}}
                    <div class="ws-toolbar">
                        @include('music::songs.partials.play-button', ['toggle' => 'togglePlay()', 'time' => 'formatTime(clock)', 'show' => 'canPlay'])
                        <span class="ws-seg" role="group" aria-label="{{ __('Mode') }}">
                            <button type="button" :class="{ 'is-active': mode === 'play' }" @click="setMode('play')" title="{{ __('Clicking a beat plays from it') }}"><i class="fa-solid fa-headphones"></i><span class="ws-label"> {{ __('Play') }}</span></button>
                            <button type="button" :class="{ 'is-active': mode === 'edit' }" @click="setMode('edit')" title="{{ __('Clicking a beat edits its chord') }}"><i class="fa-solid fa-pen"></i><span class="ws-label"> {{ __('Edit') }}</span></button>
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
                        {{-- Accordion: only the blocks' heads, one block open at a time --}}
                        <button type="button" class="ws-btn" :class="{ 'is-active': accordion }" @click="toggleAccordion()" aria-label="{{ __('Collapse the blocks') }}"
                            :title="accordion ? @js(__('Blocks collapsed: one open at a time (the one playing opens). Click to show every block')) : @js(__('Collapse every block: only their names, one open at a time'))"><i class="fa-solid fa-down-left-and-up-right-to-center"></i></button>
                        <button type="button" class="ws-btn" x-show="canPlay" :class="{ 'is-active': loop }" @click="toggleLoop()"
                            :title="loop ? @js(__('Stop repeating')) : @js(__('Repeat the marked bars (or the current bar)'))" aria-label="{{ __('Repeat') }}"><i class="fa-solid fa-repeat"></i></button>
                        <button type="button" class="ws-btn" x-show="canPlay && !clickOnly" :class="{ 'is-active': metronome }" @click="metronome = !metronome"
                            title="{{ __('Metronome') }}" aria-label="{{ __('Metronome') }}"><i class="fa-solid fa-stopwatch"></i></button>
                        {{-- Count-in: a bar of drumsticks before playing --}}
                        <button type="button" class="ws-btn" x-show="canPlay && bpm > 0" :class="{ 'is-active': countIn }" @click="toggleCountIn()"
                            :title="countIn ? @js(__('Count-in on: a bar of drumsticks at the BPM before playing. Click to turn it off')) : @js(__('Count-in off. Click for a bar of drumsticks at the BPM before playing'))"
                            aria-label="{{ __('Count-in') }}"><i class="fa-solid fa-drum"></i><span class="ws-label"> {{ __('Count-in') }}</span><span x-show="counting" x-text="` ${countBeat || ''}`"></span></button>
                        <span class="ws-spacer"></span>
                        {{-- Tapping "BPM" in the rhythm of the song sets it; the number can be typed too --}}
                        <span class="ws-btn" style="padding-right: .3rem;" x-show="!versionId">
                            <button type="button" @click="tapTempo()" title="{{ __('Tap the beat to set the tempo') }}" style="border: 0; background: none; padding: 0; font: inherit; color: inherit; cursor: pointer;">BPM <i class="fa-solid fa-hand-pointer"></i></button>
                            <input type="number" class="bpm-input" min="30" max="300" step="0.1" :value="bpm" @change="setBpm($event.target.value)" aria-label="BPM" style="border: 0; min-height: 0;">
                        </span>
                        {{-- The music on, or only the BPM click (the bars play on their own clock) --}}
                        <button type="button" class="ws-btn" x-show="hasMusic && bpm > 0" :class="{ 'is-active': !musicOff }" @click="toggleMusic()"
                            :title="musicOff ? @js(__('Playing only with the BPM: click to play with the music')) : @js(__('Playing with the music: click to play only with the BPM'))"><i class="fa-solid" :class="musicOff ? 'fa-volume-xmark' : 'fa-music'"></i><span class="ws-label"> {{ __('Song sound') }}</span></button>
                        {{-- Click on every beat, or doubled (also on each "and") for the drummer: only the sound changes --}}
                        <button type="button" class="ws-btn" x-show="!versionId && (clickOnly || metronome)" :class="{ 'is-active': clickDouble }" @click="clickDouble = !clickDouble; markDirty(); clickUntil = null"
                            :title="clickDouble ? @js(__('Doubled click: it also sounds on each "and" (the bars keep the BPM). Click for one click per beat')) : @js(__('One click per beat. Click to double it (the drummer\'s tempo); the bars keep the BPM'))"><i class="fa-solid fa-stopwatch"></i> 2×</button>
                        {{-- Where bar 1 starts in the recording (seconds): typed, or marked on the beat while it plays --}}
                        <span class="ws-btn" style="padding-right: .3rem;" x-show="!versionId && !clickOnly">
                            <button type="button" @click="markStartNow()" :disabled="!canPlay" title="{{ __('Click on the first beat of bar 1 while the song plays') }}" style="border: 0; background: none; padding: 0; font: inherit; color: inherit; cursor: pointer;" aria-label="{{ __('Start') }}"><span class="ws-label">{{ __('Start') }} </span><i class="fa-solid fa-flag"></i></button>
                            <input type="number" class="bpm-input" min="0" step="0.1" :value="songStart" @change="setSongStart($event.target.value)" aria-label="{{ __('Start of bar 1 (seconds)') }}" title="{{ __('Start of bar 1 (seconds)') }}" style="border: 0; min-height: 0;">
                        </span>
                        {{-- Free timing: each block starts where it was marked (songs whose tempo changes), or every block on the BPM --}}
                        <button type="button" class="ws-btn" x-show="!versionId" :class="{ 'is-active': freeTiming }" @click="toggleFreeTiming()"
                            :title="freeTiming ? @js(__('Free timing: each block starts where it was marked with its clock. Click to follow the bars (BPM) again')) : @js(__('Following the bars: every block starts where the BPM puts it. Click for free timing (mark where each block starts)'))">
                            <i class="fa-regular fa-clock"></i> <span class="ws-label">{{ __('Free timing') }}</span></button>
                        {{-- Time signature: only the bar lines move, every chord stays on its beat --}}
                        <select class="ws-btn" x-show="!versionId" :value="perBar" @change="setMeter(Number($event.target.value))" title="{{ __('Time signature (the chords keep their beats)') }}" aria-label="{{ __('Time signature') }}" style="padding-right: 1.8rem;">
                            @foreach (\App\Modules\Music\Services\CreateSongBarsService::METERS as $label => $beats)
                                <option value="{{ $beats }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="ws-btn" x-show="!versionId" :class="{ 'is-active': keyOpen }" @click="keyOpen = !keyOpen" title="{{ __('Key') }}">
                            <i class="fa-solid fa-music"></i> <strong x-text="keyLabel(key)"></strong>
                        </button>
                        {{-- Review: once the bars are right, saved as reviewed (a decision of whoever reviews); undone from the badge --}}
                        <button type="button" class="ws-btn review-done" x-show="!versionId && reviewedAt" @click="unmarkReviewed()" :disabled="saving"
                            :title="@js(__('Saved as reviewed on :date. Click to undo the review.')).replace(':date', formatDay(reviewedAt))">
                            <i class="fa-solid fa-circle-check"></i> <span x-text="formatDay(reviewedAt)"></span>
                        </button>
                        <button type="button" class="ws-btn review-save" x-show="!versionId && !reviewedAt" @click="save({ reviewed: true })" :disabled="saving"
                            title="{{ __('Save and mark the song as reviewed (its bars are right)') }}"><i class="fa-solid fa-check-double"></i> <span class="ws-label">{{ __('Save as reviewed') }}</span></button>
                        <button type="button" class="ws-btn ws-btn-primary" @click="save()" :disabled="saving" title="{{ __('Save') }}">
                            <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i><span class="ws-label">{{ __('Save') }}</span>
                        </button>
                        <button type="button" class="ws-btn" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" aria-label="{{ __('Options') }}" title="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="save-state" :class="{ 'is-dirty': dirty, 'is-error': saveError }" x-text="saveState"></span>
                        <span class="ws-note ws-desktop" x-text="mode === 'edit' ? @js(__('Double click a beat to choose its chord; right click a bar for its block.')) : @js(__('Click a beat to play from it; right click a bar for its block.'))"></span>
                    </div>
                    <p class="version-note" x-show="versionId" x-cloak x-text="@js(__('Editing the version for :instrument: only its chords change (the blocks, times, tempo, key and lyrics are the base\'s). It is a copy: later changes to the base do not reach it.')).replace(':instrument', currentVersion?.instrument ?? '')"></p>
                    {{-- Marked bars, copied chords and what happened to them --}}
                    <div class="passage-bar" x-show="hasSelection || clipboard || notice" x-cloak>
                        <span x-show="hasSelection" x-text="`${@js(__('Marked'))}: ${barsLabel(selection.from, selection.to)}`"></span>
                        <button type="button" class="ws-btn" x-show="hasSelection" @click="copyBars()"><i class="fa-solid fa-copy"></i> {{ __('Copy') }}</button>
                        <button type="button" class="ws-btn" x-show="hasSelection && !versionId" @click="removeBar()" :disabled="selection.to - selection.from + 1 >= bars.length"><i class="fa-solid fa-trash"></i> {{ __('Remove') }}</button>
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
                            <div class="bars-grid">
                                <template x-for="(bar, b) in bars" :key="bar.key">
                                    <div style="display: contents">
                                    {{-- A new block: its name over its rows of bars --}}
                                    <template x-if="bar.group">
                                        <div class="bar-group-head" x-show="!isHiddenRepeat(bar.group)" :style="typeStyle(blockName(bar.group))" :data-group-block="bar.group.id" @click="accordion && toggleOpenBlock(bar.group)"
                                            :class="{ 'is-accordion': accordion, 'is-dragging': groupDrag?.moved && groupDrag.id === bar.group.id, 'drop-before': groupDrag?.moved && groupDrag.target === bar.group.id && groupDrag.before, 'drop-after': groupDrag?.moved && groupDrag.target === bar.group.id && !groupDrag.before }">
                                            <span class="group-grip" x-show="!versionId" @click.stop @pointerdown="startGroupDrag($event, bar.group)" title="{{ __('Drag to move the block') }}"><i class="fa-solid fa-grip-vertical"></i></span>
                                            <button type="button" class="group-name" @click.stop="accordion ? toggleOpenBlock(bar.group) : playFromTick(bar.group.startTick)"
                                                :title="accordion ? (isOpenBlock(bar.group) ? @js(__('Close the block')) : @js(__('Open the block'))) : (canPlay ? @js(__('Play from here')) : '')" :aria-expanded="accordion ? String(isOpenBlock(bar.group)) : null">
                                                <i class="fa-solid" :class="accordion ? (isOpenBlock(bar.group) ? 'fa-chevron-down' : 'fa-chevron-right') : 'fa-play'" x-show="accordion || canPlay"></i> <span x-text="blockName(bar.group)"></span>
                                            </button>
                                            <span class="group-bars" x-show="accordion && !isOpenBlock(bar.group)" x-text="`${blockBarCount(bar.group)} ${blockBarCount(bar.group) === 1 ? @js(__('bar')) : @js(__('bars'))}`"></span>
                                            <span class="group-repeat" x-show="groupOf(bar.group).length > 1" :class="{ 'is-grouped': isCollapsed(bar.group) }"
                                                x-text="isCollapsed(bar.group) ? `×${groupOf(bar.group).length}` : `${groupOf(bar.group).indexOf(bar.group.id) + 1}/${groupOf(bar.group).length}`"></span>
                                            <template x-if="groupOf(bar.group).length > 1 && groupOf(bar.group)[0] === bar.group.id">
                                                <button type="button" class="group-toggle" @click.stop="toggleGroup(bar.group)"
                                                    x-text="isCollapsed(bar.group) ? @js(__('Show each block')) : @js(__('Group them'))"></button>
                                            </template>
                                            <template x-if="!versionId">
                                                <button type="button" class="block-more" @click.stop="openBlockMenu(bar.group, $event.currentTarget)"
                                                    :title="@js(__('Block menu'))" :aria-label="@js(__('Block menu'))"><i class="fa-solid fa-ellipsis"></i></button>
                                            </template>
                                            <template x-if="freeTiming && !versionId">
                                                <button type="button" class="block-mark" :class="{ 'is-marked': isMarked(bar.group) }" @click.stop="openMark(bar.group, $event)"
                                                    :title="`${@js(__('Mark the start of this block now'))} (${formatClock(blockStart(bar.group))})`" :aria-label="@js(__('Mark the start of this block now'))"><i class="fa-regular fa-clock"></i></button>
                                            </template>
                                            <template x-if="!versionId">
                                                <button type="button" class="group-action group-duplicate" @click.stop="isCollapsed(bar.group) ? setRepeats(bar.group, groupOf(bar.group).length + 1) : duplicateBlock(bar.group)"
                                                    :title="isCollapsed(bar.group) ? @js(__('Play it once more')) : @js(__('Duplicate the block right after it'))" :aria-label="@js(__('Duplicate'))"><i class="fa-solid fa-clone"></i></button>
                                            </template>
                                            <template x-if="!versionId">
                                                <button type="button" class="group-action group-remove" @click.stop="isCollapsed(bar.group) ? removeRun(bar.group) : removeBlock(bar.group)" :disabled="timedBlocks().length <= (isCollapsed(bar.group) ? groupOf(bar.group).length : 1)"
                                                    :title="@js(__('Remove block'))" :aria-label="@js(__('Remove block'))"><i class="fa-solid fa-trash"></i></button>
                                            </template>
                                        </div>
                                    </template>
                                    <div class="bar" x-show="!isHiddenBar(bar)" :class="{ 'is-group-start': !!bar.group, 'is-current': b === shownBar, 'has-block': bar.blocks.some(block => !isMarker(block)), 'is-selected': isSelected(b), 'is-looped': loop && b >= loop.from && b <= loop.to }"
                                        :style="bar.blocks.length ? typeStyle(blockName(bar.blocks[0])) : ''" :data-bar="b"
                                        @click="clickBar(b, $event)" @contextmenu.prevent="openBarMenu(b, $event.currentTarget)"
                                        @pointerdown="startPress(b, $event)" @pointerup="endPress()" @pointerleave="endPress()" @pointercancel="endPress()">
                                        <span class="number" x-text="b + 1"></span>
                                        {{-- Edit mode: duplicate the bar right after itself, or remove it (or the marked bars) --}}
                                        <span class="bar-tools" x-show="mode === 'edit' && !versionId" @click.stop @pointerdown.stop>
                                            <button type="button" @click="duplicateBar(b)" title="{{ __('Duplicate this bar') }}" aria-label="{{ __('Duplicate this bar') }}"><i class="fa-solid fa-clone"></i></button>
                                            <button type="button" @click="removeBar(b)" :disabled="ticks.length <= perBar * 2" :title="isSelected(b) && selection.to > selection.from ? @js(__('Remove the marked bars')) : @js(__('Remove this bar'))" aria-label="{{ __('Remove this bar') }}"><i class="fa-solid fa-trash"></i></button>
                                        </span>
                                        <div class="bar-blocks">
                                            <template x-for="block in bar.chips" :key="block.id">
                                                <span style="display: contents">
                                                    <button type="button" class="block-chip" :class="{ 'is-marker': isMarker(block) }" :style="typeStyle(blockName(block))"
                                                        @click.stop="isMarker(block) ? null : playFromTick(block.startTick)" @contextmenu.stop.prevent="isMarker(block) ? openBarMenu(b, $event.currentTarget.closest('.bar')) : openBlockMenu(block, $event.currentTarget)"
                                                        :title="isMarker(block) ? '' : @js(__('Play from here'))">
                                                        <i class="fa-solid fa-play play" x-show="!isMarker(block) && canPlay"></i>
                                                        <span x-text="isMarker(block) ? '↺ ' + @js(__('Back to the start')) : blockName(block)"></span>
                                                    </button>
                                                    <template x-if="!versionId">
                                                        <button type="button" class="block-more" @click.stop="openBlockMenu(block, $event.currentTarget)" @pointerdown.stop
                                                            :title="@js(__('Block menu'))" :aria-label="@js(__('Block menu'))"><i class="fa-solid fa-ellipsis"></i></button>
                                                    </template>
                                                    <template x-if="freeTiming && !versionId && !isMarker(block)">
                                                        <button type="button" class="block-mark" :class="{ 'is-marked': isMarked(block) }" @click.stop="openMark(block, $event)" @pointerdown.stop
                                                            :title="`${@js(__('Mark the start of this block now'))} (${formatClock(blockStart(block))})`" :aria-label="@js(__('Mark the start of this block now'))"><i class="fa-regular fa-clock"></i></button>
                                                    </template>
                                                </span>
                                            </template>
                                        </div>
                                        <div class="bar-beats" :style="`grid-template-columns: repeat(${bar.beats.length}, minmax(0, 1fr))`">
                                            <template x-for="(beat, i) in bar.beats" :key="i">
                                                <span class="beat" :class="{ 'is-on': b === shownBar && i === currentBeat }" :data-beat="i" @click.stop="clickBeat(b, i, $event.currentTarget, $event)" @dblclick.stop="mode === 'edit' && editBeat(b, i, $event)"
                                                    :title="`${@js(__('Beat'))} ${i + 1}: ${beat.chord || '–'}${beat.and !== null ? ' → ' + (beat.and || '–') + ' (e)' : ''}`">
                                                    <template x-if="inline && Math.floor(inline.tick / 2) === b * perBar + i">
                                                        <input type="text" class="inline-chord" :class="{ 'is-invalid': inline?.invalid }" :value="inline?.text ?? ''" autocomplete="off" spellcheck="false"
                                                            x-init="$nextTick(() => { $el.focus(); inline?.select ? $el.select() : $el.setSelectionRange($el.value.length, $el.value.length); })"
                                                            @click.stop @pointerdown.stop
                                                            @input="if (inline) { inline.text = $event.target.value; inline.invalid = false; }"
                                                            @keydown.enter.prevent="commitInline()" @keydown.escape.stop.prevent="inline = null" @keydown.tab.prevent="commitInline()"
                                                            @blur="commitInline(true)">
                                                    </template>
                                                    <span class="halves" x-show="!(inline && Math.floor(inline.tick / 2) === b * perBar + i)">
                                                        <span class="chord" :class="{ 'is-repeat': beat.repeat, 'is-long': !beat.repeat && beat.chord.length > 4, 'is-passing': !beat.repeat && passingTicks.has((b * perBar + i) * 2), 'can-drag': mode === 'edit' && canDragAt((b * perBar + i) * 2) }" x-text="beat.repeat ? '/' : (beat.chord || '–')"
                                                            @pointerdown="startDrag($event, b, i, 0)"></span>
                                                        <template x-if="beat.and !== null"><span class="chord is-and" :class="{ 'is-long': beat.and.length > 4, 'is-passing': passingTicks.has((b * perBar + i) * 2 + 1), 'can-drag': mode === 'edit' && canDragAt((b * perBar + i) * 2 + 1) }" x-text="beat.and || '–'"
                                                            @pointerdown="startDrag($event, b, i, 1)"></span></template>
                                                    </span>
                                                    <span class="count" x-text="i + 1"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    </div>
                                </template>
                            </div>
                            {{-- A new block at the end: its name and how many bars (each with a "?" to be written by ear) --}}
                            <div class="add-block" x-show="!versionId" x-data="{ open: false, name: '', count: 4 }">
                                <button type="button" class="add-block-open" x-show="!open" @click="open = true; $nextTick(() => $refs.newBlockName.focus())">
                                    <i class="fa-solid fa-plus"></i> {{ __('New block') }}
                                </button>
                                <form class="add-block-form" x-show="open" x-cloak @submit.prevent="if (addBlockAtEnd(name, count)) { open = false; name = ''; count = 4; }" @keydown.escape.stop="open = false">
                                    <input type="text" x-ref="newBlockName" x-model="name" list="block-names" required autocomplete="off" spellcheck="false" style="text-transform: uppercase"
                                        placeholder="{{ __('E.g. CHORUS') }}" aria-label="{{ __('Name of the block') }}">
                                    <label>{{ __('Bars') }} <input type="number" x-model.number="count" min="1" max="64" required></label>
                                    <button type="submit" class="ws-btn ws-btn-primary"><i class="fa-solid fa-plus"></i> {{ __('Add') }}</button>
                                    <button type="button" class="ws-btn" @click="open = false">{{ __('Cancel') }}</button>
                                </form>
                            </div>
                        </div>

                        {{-- Chord sheet with lyrics, split like the blocks; the block being played is lit --}}
                        <div class="sheet-col" x-show="sheet.length">
                            <div class="sheet-box">
                                <p class="sheet-preview-note" x-show="!versionId && dirty && previewSheet.changed">{{ __('Preview: the chord sheet will look like this when the map is saved (new chords in green).') }}</p>
                                <template x-for="(section, s) in shownSheet" :key="`${sheetRevision}-${versionId}-${s}`">
                                    <div class="sheet-section" :class="{ 'is-current': s === currentSheetSection }" :style="typeStyle(section.label)" :data-sheet="s">
                                        <div class="sheet-head">
                                            <div class="sheet-label" x-text="section.label" @click="playFromSheet(s)"></div>
                                            <template x-if="freeTiming && !versionId && !section.marker && sheetBlock(s)">
                                                <button type="button" class="block-mark" :class="{ 'is-marked': isMarked(sheetBlock(s)) }" @click.stop="openMark(sheetBlock(s), $event)"
                                                    :title="@js(__('Mark the start of this block now'))" :aria-label="@js(__('Mark the start of this block now'))">
                                                    <i class="fa-regular fa-clock"></i> <span x-text="formatClock(blockStart(sheetBlock(s)))"></span></button>
                                            </template>
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
                                                        <i class="fa-solid" :class="sheetEdit.saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                                        <span x-text="sheetEdit.copies ? @js(__('Save only this one')) : @js(__('Save'))"></span>
                                                    </button>
                                                    <button type="button" class="ws-btn" x-show="sheetEdit.copies" @click="saveSheetSection(true)" :disabled="sheetEdit.saving"
                                                        x-text="@js(__('Save in every :name (:count)')).replace(':name', sheetEdit.name).replace(':count', sheetEdit.copies + 1)"></button>
                                                    <button type="button" class="ws-btn" x-show="hasChordsOverLyrics(sheetEdit.text)" @click="sheetEdit.text = chordsOverLyrics(sheetEdit.text)"
                                                        title="{{ __('Chords pasted over the lyrics go into the lyrics, at their columns') }}"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Interpret') }}</button>
                                                    <button type="button" class="ws-btn" @click="sheetEdit = null">{{ __('Cancel') }}</button>
                                                </div>
                                                <p class="help">{{ __('You can also paste chords over the lyrics (a line of chords, the lyrics under it) and click Interpret.') }}</p>
                                                <p class="help">{{ __('Chords stay in brackets, e.g. Esp[G]írito Santo: change the lyrics, move, swap, add or remove chords (the map changes too: an added chord takes half of the one before it, a removed one gives its beats to the one before).') }}</p>
                                                <p class="error" x-show="sheetEdit.error" x-text="sheetEdit.error"></p>
                                            </div>
                                        </template>
                                        <template x-for="(line, l) in (sheetEdit?.index === s ? [] : section.lines)" :key="sheetLineKey(line, l)">
                                            <div>
                                                <template x-if="line.comment !== undefined"><div class="sheet-comment" x-text="line.comment"></div></template>
                                                <template x-if="line.comment === undefined">
                                                    <div>
                                                        <div class="sheet-line" :data-sheet-line="`${s}-${l}`"><template x-for="(unit, u) in (chordMove && chordMove.s === s && chordMove.l === l ? chordMove.units : line.units)" :key="`${u}:${unit.chord}:${unit.text}:${unit.added || unit.changed ? 'a' : ''}`"><span class="sheet-seg"><span class="c" :class="{ 'is-playing': unit.n !== undefined && unit.n === playingSheetChord, 'is-moving': chordMove && chordMove.s === s && chordMove.l === l && unit.ordinal === chordMove.k, 'can-move': mode === 'edit' && !versionId && !section.marker && unit.chord && (!dirty || sheetLinked), 'is-added': unit.added || unit.changed }" :data-chord="unit.n"
                                                            @pointerdown="unit.chord && startSheetDrag($event, s, l, unit.ordinal)"><span x-text="unit.chord ? transposeName(unit.chord) : ''"></span></span><span class="t" :data-from="unit.from" x-text="unit.text"></span></span></template></div>
                                                        <template x-if="chordMove && !chordMove.dragging && chordMove.s === s && chordMove.l === l">
                                                            <div class="chord-move" @click.stop>
                                                                {{-- Saved in the copies of the section too (same name and lines) --}}
                                                                <button type="button" class="ws-btn ws-btn-primary" @click="saveChordMove(true)" :disabled="chordMove.saving"><i class="fa-solid" :class="chordMove.saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> {{ __('Save') }}</button>
                                                                <button type="button" class="ws-btn" @click="chordMove = null">{{ __('Cancel') }}</button>
                                                                <span class="error" x-show="chordMove.error" x-text="chordMove.error"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Chord of a beat (double click, or a tap on touch screens): one of the song's chords, one of the key, or typed --}}
                    <div class="popover chord-picker" x-show="editor" x-cloak x-ref="editor" @click.outside="editor = null" @keydown.escape.stop="editor = null" :style="editor ? typeStyle(editorBlockName()) : ''">
                        <div class="block-menu-head">
                            <div>
                                <p class="block-menu-name" x-text="editorBlockName()"></p>
                                <p class="block-menu-place" x-text="editor ? `${@js(__('Bar'))} ${editor.bar + 1}, ${@js(__('beat'))} ${editor.beat + 1}${editor.half ? ' (e)' : ''}` : ''"></p>
                            </div>
                            <button type="button" class="block-menu-close" @click="editor = null" :aria-label="@js(__('Close'))"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="block-menu-name-row">
                            <input type="text" x-ref="editorChord" autocomplete="off" spellcheck="false" placeholder="G, Am7, C/E, D4"
                                @input="if (editor) { editor.text = $event.target.value; editorError = ''; }" @keydown.enter.prevent="applyChord()">
                            <button type="button" class="ws-btn ws-btn-primary" @click="applyChord()" :title="@js(__('Apply'))" :aria-label="@js(__('Apply'))"><i class="fa-solid fa-check"></i></button>
                        </div>
                        <p class="help" x-show="editorError" x-text="editorError" style="color: #dc2626;"></p>
                        <template x-if="editor">
                            <div>
                                <div class="block-menu-field" x-show="pickerChords('song').length">
                                    <span class="block-menu-label">{{ __('In the song') }}</span>
                                    <div class="menu-chords">
                                        <template x-for="chord in pickerChords('song')" :key="chord">
                                            <button type="button" :class="{ 'is-current': chord === editorChordNow() }" @click="pickChord(chord)" x-text="chord"></button>
                                        </template>
                                    </div>
                                </div>
                                <div class="block-menu-field" x-show="pickerChords('key').length">
                                    <span class="block-menu-label" x-text="`${@js(__('In the key of'))} ${key}`"></span>
                                    <div class="menu-chords is-key">
                                        <template x-for="chord in pickerChords('key')" :key="chord">
                                            <button type="button" @click="pickChord(chord)" x-text="chord"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div class="block-menu-field">
                            <span class="block-menu-label">{{ __('Where it starts') }}</span>
                            <div class="row" style="margin-top: 0;">
                                <button type="button" class="ws-btn" :class="{ 'is-active': editor?.half === 0 }" @click="editor.half = 0; fillEditor()">{{ __('On the beat') }}</button>
                                <button type="button" class="ws-btn" :class="{ 'is-active': editor?.half === 1 }" @click="editor.half = 1; fillEditor()">{{ __('On the "and"') }}</button>
                            </div>
                        </div>
                        <div class="row" x-show="canRemoveChange()">
                            <button type="button" class="ws-btn" @click="removeChange()" title="{{ __('The chord before goes on from here') }}"><i class="fa-solid fa-eraser"></i> {{ __('Remove change') }}</button>
                        </div>
                    </div>

                    {{-- After a chord change in a block that has equal copies: the same change there too? --}}
                    <div class="popover" x-show="copyOffer" x-cloak x-ref="copyOffer" @click.outside="if (copyOffer && performance.now() - copyOffer.at > 400) copyOffer = null" @keydown.escape.window="copyOffer = null">
                        <p class="title" x-text="copyOffer ? @js(__('Also in the other :count :name blocks?')).replace(':count', copyOffer.copies.length).replace(':name', copyOffer.name) : ''"></p>
                        <div class="row">
                            <button type="button" class="ws-btn ws-btn-primary" x-ref="copyOfferAll" @click="applyToCopies()">{{ __('In all of them') }}</button>
                            <button type="button" class="ws-btn" @click="copyOffer = null">{{ __('Only this one') }}</button>
                        </div>
                    </div>

                    {{-- Clock of a block (free timing): its start at the player's time, on the nearest bar or exact --}}
                    <div class="popover" x-show="markMenu" x-cloak @click.outside="markMenu = null" @keydown.escape.window="markMenu = null"
                        :style="markMenu ? `position: fixed; left: ${Math.max(8, markMenu?.x)}px; top: ${markMenu?.y}px;` : ''">
                        <template x-if="markMenu">
                            <div>
                                <p class="title" x-text="`${blockName(blocks.find(item => item.id === markMenu?.id) ?? { data: {} })} · ${@js(__('starts at'))}`"></p>
                                <template x-if="markMenu?.first">
                                    <div>
                                        <p class="help">{{ __('The first block is where the song starts (bar 1): the exact time.') }}</p>
                                        <div class="row"><button type="button" class="ws-btn ws-btn-primary" @click="applyMark(markMenu?.exact)"><i class="fa-regular fa-clock"></i> <span x-text="formatClock(markMenu?.exact)"></span></button></div>
                                    </div>
                                </template>
                                <template x-if="!markMenu?.first">
                                    <div>
                                        <div class="row">
                                            <button type="button" class="ws-btn" :class="{ 'ws-btn-primary': !markMenu?.far }" @click="applyMark(markMenu?.snapped)" :disabled="markMenu?.snapped <= 0"
                                                title="{{ __('On a bar line, counted from the block before it') }}">
                                                <i class="fa-solid fa-table-cells"></i> <span x-text="`${formatClock(markMenu?.snapped)} (${markLabel(markMenu)})`"></span></button>
                                            <button type="button" class="ws-btn" :class="{ 'ws-btn-primary': markMenu?.far }" @click="applyMark(markMenu?.exact)"
                                                title="{{ __('Exactly where you clicked, off the bars: the blocks after it count from here') }}">
                                                <i class="fa-regular fa-clock"></i> <span x-text="`${formatClock(markMenu?.exact)} (${@js(__('exact'))})`"></span></button>
                                        </div>
                                        <p class="help" x-show="markMenu?.far">{{ __('Far from the bar lines: the tempo probably changed here, the exact time is better.') }}</p>
                                        <div class="row" x-show="markMenu?.marked">
                                            <button type="button" class="ws-btn" @click="applyMark(null)" x-text="`${@js(__('Right after the block before'))} (${formatClock(markMenu?.expected)})`"></button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Block of a bar: new, rename, join; play from the bar --}}
                    <div class="popover" x-show="barMenu" x-cloak x-ref="barMenu" @click.outside="barMenu = null" @keydown.escape.stop="barMenu = null">
                        <p class="title" x-text="barMenuTitle()"></p>
                        {{-- A new block from this bar (renaming a block is in its own menu) --}}
                        <input type="text" x-ref="blockName" list="block-names" x-show="!versionId && barMenu && !blockAtBar(barMenu.bar)" autocomplete="off" spellcheck="false" placeholder="{{ __('E.g. CHORUS') }}" style="text-transform: uppercase" @keydown.enter.prevent="saveBlockName()">
                        <datalist id="block-names">
                            <template x-for="name in commonNames" :key="name"><option :value="name"></option></template>
                        </datalist>
                        <div class="row" x-show="!versionId && barMenu && !blockAtBar(barMenu.bar)">
                            <button type="button" class="ws-btn ws-btn-primary" @click="saveBlockName()">{{ __('New block here') }}</button>
                        </div>
                        <div class="row" x-show="!versionId && barMenu && blockOffset(barMenu.bar)">
                            <button type="button" class="ws-btn" @click="alignBlock(false)">{{ __('Start on beat 1 of this bar') }}</button>
                            <button type="button" class="ws-btn" @click="alignBlock(true)">{{ __('Start on the next bar') }}</button>
                        </div>
                        {{-- Passing chords of the bar: underlined in the bars and on the public page --}}
                        <template x-if="!versionId && barMenu && barChords(barMenu.bar).length">
                            <div>
                                <hr>
                                <p class="title">{{ __('Passing chords') }}</p>
                                <div class="menu-chords">
                                    <template x-for="chord in barChords(barMenu.bar)" :key="chord.tick">
                                        <button type="button" :class="{ 'is-on': chord.passing }" @click="togglePassing(chord.tick)" :aria-pressed="chord.passing" x-text="transposeName(chord.name)"
                                            :title="chord.passing ? @js(__('Passing chord: click to unmark it')) : @js(__('Click to mark it as a passing chord'))"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        {{-- Bars added (the chord sounding there goes on one bar longer) or removed --}}
                        <template x-if="!versionId">
                            <div>
                                <hr>
                                <p class="title">{{ __('Bar') }}</p>
                                <div class="row" style="margin-top: 0;">
                                    <button type="button" class="ws-btn" @click="insertBar(barMenu.bar, false)" title="{{ __('Insert a bar before: the chord at the start of this bar lasts one bar longer') }}"><i class="fa-solid fa-plus"></i> {{ __('Before') }}</button>
                                    <button type="button" class="ws-btn" @click="insertBar(barMenu.bar, true)" title="{{ __('Insert a bar after: the chord at the end of this bar lasts one bar longer') }}"><i class="fa-solid fa-plus"></i> {{ __('After') }}</button>
                                    <button type="button" class="ws-btn" @click="removeBar(barMenu.bar); if (barMenu && barMenu.bar >= bars.length) barMenu = null" :disabled="bars.length < 2" :title="@js(__('Remove')) + ': ' + menuBarsLabel()"><i class="fa-solid fa-trash"></i> <span x-text="barMenu && isSelected(barMenu.bar) && selection.to > selection.from ? `${@js(__('Remove'))} (${selection.to - selection.from + 1})` : @js(__('Remove'))"></span></button>
                                </div>
                            </div>
                        </template>
                        <hr>
                        <p class="title" x-text="barMenu ? menuBarsLabel() : ''"></p>
                        <div class="row">
                            <button type="button" class="ws-btn" @click="copyBars(barMenu.bar); barMenu = null"><i class="fa-solid fa-copy"></i> {{ __('Copy') }}</button>
                        </div>
                        <div class="row" x-show="clipboard">
                            <button type="button" class="ws-btn" @click="paste(barMenu.bar, false); barMenu = null" :title="@js(__('Only the chord names: the changes here stay where they are'))"><i class="fa-solid fa-paste"></i> {{ __('Paste here') }}</button>
                            <button type="button" class="ws-btn" @click="paste(barMenu.bar, true); barMenu = null" :title="@js(__('Where each chord changes comes along too'))">{{ __('Paste with the rhythm') }}</button>
                        </div>
                        <div class="row" x-show="hasSelection && !isSelected(barMenu?.bar)">
                            <button type="button" class="ws-btn" @click="selectTo(barMenu.bar); barMenu = null">{{ __('Mark up to here') }}</button>
                        </div>
                    </div>

                    {{-- The menu of a block (its "..." next to its name): name, bars, repetitions (it is moved by dragging its head,
                         duplicated and removed with the buttons on its right) --}}
                    <div class="popover" x-show="blockMenu" x-cloak x-ref="blockMenu" @click.outside="blockMenu = null" @keydown.escape.stop="blockMenu = null">
                        <template x-if="blockMenu && menuBlock()">
                            <div x-data="{ name: menuBlockInfo().name }" class="block-menu" :style="typeStyle(menuBlockInfo().name)">
                                <div class="block-menu-head">
                                    <div>
                                        <p class="block-menu-name" x-text="menuBlockInfo().name"></p>
                                        <p class="block-menu-place" x-text="@js(__('Bars :from–:to · block :n of :total')).replace(':from', menuBlockInfo().from).replace(':to', menuBlockInfo().to).replace(':n', menuBlockInfo().number).replace(':total', menuBlockInfo().total)"></p>
                                    </div>
                                    <button type="button" class="block-menu-close" @click="blockMenu = null" :aria-label="@js(__('Close'))"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <div class="block-menu-field">
                                    <span class="block-menu-label">{{ __('Name') }}</span>
                                    <div class="block-menu-name-row">
                                        <input type="text" list="block-names" x-model="name" autocomplete="off" spellcheck="false" style="text-transform: uppercase"
                                            :aria-label="@js(__('Name of the block'))" @keydown.enter.prevent="renameMenuBlock(name)">
                                        <button type="button" class="ws-btn ws-btn-primary" @click="renameMenuBlock(name)" :disabled="!name.trim() || name.trim().toUpperCase() === menuBlockInfo().name"
                                            :title="@js(__('Rename'))" :aria-label="@js(__('Rename'))"><i class="fa-solid fa-check"></i></button>
                                    </div>
                                </div>
                                <div class="block-menu-grid">
                                    <div class="block-menu-field" :title="@js(__('More bars are added at the end of the block (with a "?" each); fewer take its last bars out'))">
                                        <span class="block-menu-label">{{ __('Bars') }}</span>
                                        <div class="stepper">
                                            <button type="button" @click="setBlockBars(menuBlock(), menuBlockInfo().bars - 1)" :disabled="menuBlockInfo().bars <= 1" :aria-label="@js(__('Fewer bars'))"><i class="fa-solid fa-minus"></i></button>
                                            <input type="number" min="1" max="128" :value="menuBlockInfo().bars" @change="setBlockBars(menuBlock(), $event.target.value)" :aria-label="@js(__('Bars'))">
                                            <button type="button" @click="setBlockBars(menuBlock(), menuBlockInfo().bars + 1)" :aria-label="@js(__('More bars'))"><i class="fa-solid fa-plus"></i></button>
                                        </div>
                                    </div>
                                    <div class="block-menu-field" :title="@js(__('How many times this block is played, one after the other'))">
                                        <span class="block-menu-label">{{ __('Repetitions') }}</span>
                                        <div class="stepper">
                                            <button type="button" @click="setRepeats(menuBlock(), menuBlockInfo().repeats - 1)" :disabled="menuBlockInfo().repeats <= 1" :aria-label="@js(__('Fewer repetitions'))"><i class="fa-solid fa-minus"></i></button>
                                            <input type="number" min="1" max="50" :value="menuBlockInfo().repeats" @change="setRepeats(menuBlock(), $event.target.value)" :aria-label="@js(__('Repetitions'))">
                                            <button type="button" @click="setRepeats(menuBlock(), menuBlockInfo().repeats + 1)" :aria-label="@js(__('More repetitions'))"><i class="fa-solid fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                {{-- Start time: where the block starts in the recording --}}
                                <div class="block-menu-field">
                                    <span class="block-menu-label">{{ __('Starts at') }}</span>
                                    <div class="block-menu-time">
                                        <button type="button" class="nudge" @click="setBlockTime(menuBlock(), blockStart(menuBlock()) - 0.1)" title="{{ __('0.1 s earlier') }}">−0,1</button>
                                        <input type="text" inputmode="decimal" :value="formatClock(blockStart(menuBlock()))" @change="setBlockTime(menuBlock(), $event.target.value); $event.target.value = formatClock(blockStart(menuBlock()))"
                                            @keydown.enter.prevent="$event.target.blur()" :aria-label="@js(__('Starts at'))" placeholder="0:00.00">
                                        <button type="button" class="nudge" @click="setBlockTime(menuBlock(), blockStart(menuBlock()) + 0.1)" title="{{ __('0.1 s later') }}">+0,1</button>
                                        <button type="button" class="nudge now" x-show="hasMusic && !clickOnly" @click="const block = menuBlock(); blockMenu = null; openMark(block, $event)"
                                            title="{{ __('Mark the start of this block now') }}"><i class="fa-regular fa-clock"></i> {{ __('Now') }}</button>
                                    </div>
                                    <p class="help" x-text="menuTimeHelp()"></p>
                                    <button type="button" class="link-btn" x-show="menuBlockInfo().index > 0 && freeTiming && menuBlock()?.data.free_start !== undefined"
                                        @click="setBlockTime(menuBlock(), null)">{{ __('Back to right after the block before') }}</button>
                                </div>
                            </div>
                        </template>
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
                            $minorSong = \App\Modules\Music\Enums\MusicalKey::isMinor($song->musical_key);
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
                        @include('music::songs.partials.video-options')
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
                        {{-- Fast songs (a chord over two bars): every chord twice as long; the extra bars are then removed by hand --}}
                        <div class="ws-panel-section" x-show="!versionId">
                            <div class="ws-panel-label"><i class="fa-solid fa-up-right-and-down-left-from-center"></i> {{ __('Double the bars') }}</div>
                            <button type="button" class="ws-btn" @click="doubleBars(); optionsOpen = false" :disabled="!ticks.length"><i class="fa-solid fa-clone"></i> {{ __('Double every bar') }}</button>
                            <p class="ws-panel-help mt-2">{{ __('Every chord lasts twice as long (one bar becomes two), for fast songs where a chord takes two bars. The BPM and the empty beats before the first chord stay; remove the extra bars afterwards. Undo goes back; nothing is kept until you save.') }}</p>
                        </div>
                        {{-- Wrong chord sheet: the chords out, to write them by ear (blocks and bars stay) --}}
                        <div class="ws-panel-section" x-show="!versionId">
                            <div class="ws-panel-label"><i class="fa-solid fa-eraser"></i> {{ __('Clear chords') }}</div>
                            <form method="POST" action="{{ route('songs.bars.clear', $song) }}"
                                @submit="if (!confirm(@js(__('Clear every chord? The blocks and their bars stay, each bar gets a "?" (in the map and in the chord sheet, over its lyrics) to be written by ear. Unsaved changes here are lost; the current version stays in the history.')))) $event.preventDefault(); else dirty = false">
                                @csrf
                                <button type="submit" class="ws-btn"><i class="fa-solid fa-eraser"></i> {{ __('Clear chords') }}</button>
                            </form>
                            <p class="ws-panel-help mt-2">{{ __('For a wrong chord sheet: keeps the blocks, the bars and the lyrics, and puts a "?" in each bar (also in the chord sheet) to write the chords by ear.') }}</p>
                        </div>
                        <div class="ws-panel-section">
                            <div class="ws-panel-label"><i class="fa-solid fa-circle-info"></i> {{ __('How it works') }}</div>
                            <p class="ws-panel-help">{{ __('Each bar shows the chord of every beat ("/" while it goes on; a change on the "and" splits the beat). The position of each bar in the song comes from the start of its block and the BPM.') }}</p>
                            <p class="ws-panel-help mt-2">{{ __('Play: click a beat to play from it. Edit: click a beat to change its chord. Right click (or press and hold on a phone) a bar for its block: new block, rename, join.') }}</p>
                            <p class="ws-panel-help mt-2">{{ __('Marking bars: click a bar, then Shift + click another; the marked bars can be copied, restored and repeated. Copy and paste also work on one bar or a whole block (right click a bar).') }}</p>
                            <p class="ws-panel-help mt-2">{{ __('Keys: space plays / pauses, ← → move a beat, ↑ ↓ a bar (Page Up / Down 4 bars), Enter edits the beat, Ctrl/⌘ + C / V copies and pastes, Shift + R restores, Esc unmarks, Ctrl/⌘ + Z undoes, Ctrl/⌘ + S saves.') }}</p>
                        </div>
                    </x-song-options>

                    @include('music::songs.partials.youtube-mini', ['id' => 'yt-bars'])
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
            // Hundredths: a start marked by ear may sit off the bars.
            const formatFineStart = (seconds) => {
                const units = Math.round(seconds * 100);
                const whole = Math.floor(units / 100);
                const fraction = String(units % 100).padStart(2, '0').replace(/0+$/, '');
                return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}${fraction ? '.' + fraction : ''}`;
            };
            const formatStart = (seconds) => {
                const tenths = Math.round(seconds * 10);
                const whole = Math.floor(tenths / 10);
                const text = `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`;
                return tenths % 10 ? `${text}.${tenths % 10}` : text;
            };

            // Structure -> ticks + blocks.
            // `splits`: ticks where a chord starts right after the same chord ("G G" are two chords of the map, each
            // with its place in the chord sheet), so they are not joined into one.
            const parseStructure = (structure) => {
            const ticks = [];
            const blocks = [];
            const splits = [];
            let perBar = 4;
            let clickDouble = false;
            let freeTiming = false;
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
                if (section.click_double) clickDouble = true;
                delete data.click_double;
                if (section.free_timing) freeTiming = true;
                delete data.free_timing;
                // Beats before the first chord in bar 1 (the song starts on beat 3, say): empty half beats first.
                if (!blocks.some(block => !isMarkerData(block.data)) && Number(section.lead_beats) > 0) {
                    for (let k = 0; k < Math.round(Number(section.lead_beats) * 2); k++) ticks.push('');
                }
                delete data.lead_beats;
                blocks.push({ id: nextId++, startTick: ticks.length, data: { ...data, lineChords: chords, breaks } });
                chords.forEach((chord, i) => {
                    if (i > 0 && chord === chords[i - 1]) splits.push(ticks.length);
                    for (let k = 0; k < Math.round((durations?.[i] ?? perBar) * 2); k++) ticks.push(chord);
                });
            });
            return { ticks, blocks, perBar, splits, clickDouble, freeTiming };
            };
            const { ticks, blocks, perBar, splits, clickDouble, freeTiming } = parseStructure(config.structure);

            // ChordPro lines -> { comment } or { units: [{ chord, text }] }.
            const parseLine = (line) => {
                const text = String(line ?? '');
                const comment = /^\{\s*(?:c|comment)\s*:\s*(.*?)\s*\}$/i.exec(text.trim());
                if (comment) return { comment: comment[1], raw: text };
                if (/^\s*\{/.test(text)) return { comment: '', raw: text };
                const units = [];
                let last = 0, chord = null, ordinal = 0, plain = 0;
                for (const match of text.matchAll(/\[([^\]]+)\]/g)) {
                    const before = text.slice(last, match.index);
                    if (chord !== null || before) units.push({ chord, text: before, from: plain, ordinal: chord === null ? undefined : ordinal++ });
                    plain += before.length;
                    chord = match[1];
                    last = match.index + match[0].length;
                }
                units.push({ chord, text: text.slice(last), from: plain, ordinal: chord === null ? undefined : ordinal });
                return { units };
            };
            // How a list of chords became another (same rule as the server, UpdateChordSheetChordsService): for each
            // old chord, what takes its place (none: removed), and the chords added after it (-1: before the first).
            // With the half beat where each chord starts (`oldAt`, `nowAt`), of the ways with the fewest changes the
            // one keeping chords nearest their place is taken: a G added right before another G is the added one.
            const chordChanges = (old, now, oldAt = null, nowAt = null) => {
                const n = old.length, m = now.length;
                const CHANGE = 1e7;
                const distance = (i, j) => (oldAt && nowAt ? Math.min(CHANGE - 1, Math.abs(oldAt[i] - nowAt[j])) : 0);
                const cost = Array.from({ length: n + 1 }, (_, i) => Array.from({ length: m + 1 }, (_, j) => (i === n || j === m ? ((n - i) + (m - j)) * CHANGE : 0)));
                for (let i = n - 1; i >= 0; i--) for (let j = m - 1; j >= 0; j--) {
                    const skip = Math.min(cost[i + 1][j], cost[i][j + 1]) + CHANGE;
                    cost[i][j] = old[i] === now[j] ? Math.min(cost[i + 1][j + 1] + distance(i, j), skip) : skip;
                }
                const replace = {}, after = {};
                let removed = [], added = [], previous = -1;
                const flush = () => {
                    removed.forEach((i, k) => { replace[i] = k < added.length ? [added[k]] : []; previous = i; });
                    added.slice(removed.length).forEach(chord => (after[previous] ||= []).push(chord));
                    removed = [];
                    added = [];
                };
                let i = 0, j = 0;
                while (i < n || j < m) {
                    if (i < n && j < m && old[i] === now[j] && cost[i][j] === cost[i + 1][j + 1] + distance(i, j)) { flush(); replace[i] = [old[i]]; previous = i; i++; j++; }
                    else if (j >= m || (i < n && cost[i][j] === cost[i + 1][j] + CHANGE)) removed.push(i++);
                    else added.push(now[j++]);
                }
                flush();
                return { replace, after };
            };
            // A parsed line back to its text ("[G]Digno é o [D]Cordeiro").
            const lineText = (units) => units.map(unit => (unit.chord !== null ? `[${unit.chord}]` : '') + unit.text).join('');
            // Each chord of the sheet gets its number ("n"), counted from the start of the sheet.
            const parseSheet = (sections) => {
                let n = 0;
                const view = (sections || []).map(section => ({
                    section: section.section ?? null,
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

            let rootElement = null;
            return withYoutubeMini({
                ticks,
                blocks,
                perBar,
                // The click also sounds on each "and" (the drummer's doubled tempo); the bars keep the BPM.
                clickDouble,
                // Free timing: each block starts where it was marked in the recording (songs whose tempo changes);
                // otherwise every block follows bar 1 and the BPM.
                freeTiming,
                markMenu: null,
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
                barMenu: null,
                clock: 0,
                playingSheetChord: -1,
                currentTick: -1,
                // Bars marked (click, then Shift + click): { anchor, from, to }; from / to null when none.
                selection: { anchor: null, from: null, to: null },
                // Chords copied: { ticks, offset (ticks after the start of its first bar), label }.
                clipboard: null,
                notice: '',
                // Chords as they were when the page was opened or last saved (to restore bars).
                originalTicks: [...ticks],
                splits: [...splits],
                originalSplits: [...splits],
                // Passage repeated while playing: { from, to } (bars).
                loop: null,
                loopCooldown: 0,
                metronome: false,
                audioSpeed: 1,
                // Lines view: what a click on a chord does ('break' or 'passing').
                commonNames: ['INTRO', 'PRIMEIRA PARTE', 'SEGUNDA PARTE', 'PRÉ-REFRÃO', 'REFRÃO', 'PONTE', 'INTERLÚDIO', 'SOLO', 'RIFF', 'FINAL',
                    'VERSE', 'PRE CHORUS', 'CHORUS', 'BRIDGE', 'INTERLUDE', 'TAG', 'OUTRO'],

                // An element of the page marked with x-ref, found from the page itself (not from the element a handler
                // was called from, which may have been drawn again meanwhile).
                ref(name) { return (rootElement || this.$el).querySelector(`[x-ref="${name}"]`); },
                // A reload brings back the view and the scroll position it had.
                placeKey: `bars-place:${location.pathname}`,
                keepPlace() {
                    try { sessionStorage.setItem(this.placeKey, JSON.stringify({ at: Date.now(), y: window.scrollY, mode: this.mode, pane: this.pane })); } catch (error) {}
                },
                restorePlace() {
                    let place = null;
                    try {
                        place = JSON.parse(sessionStorage.getItem(this.placeKey) || 'null');
                        sessionStorage.removeItem(this.placeKey);
                    } catch (error) {}
                    if (!place || Date.now() - place.at > 60000) return;
                    if (['play', 'edit'].includes(place.mode)) this.mode = place.mode;
                    if (place.pane) this.pane = place.pane;
                    this.$nextTick(() => requestAnimationFrame(() => window.scrollTo(0, place.y)));
                },
                init() {
                    this.restorePlace();
                    rootElement = this.$el;
                    if (audio) {
                        // The MP3 drives the same "playing" as the YouTube player (see youtube-mini.js).
                        // With the music off the bars run on their own clock: the MP3's events do not count.
                        audio.addEventListener('play', () => { if (!this.clickOnly) this.playing = true; });
                        audio.addEventListener('pause', () => { if (!this.clickOnly) this.playing = false; });
                    } else {
                        this.$nextTick(() => this.initVideo('yt-bars'));
                    }
                    // The last pointer used: a finger opens the chord editor, a mouse types right in the cell.
                    window.addEventListener('pointerdown', event => { this.touch = event.pointerType === 'touch' || event.pointerType === 'pen'; }, true);
                    window.addEventListener('pointermove', event => { this.onDragMove(event); this.onSheetDragMove(event); this.onGroupDragMove(event); });
                    window.addEventListener('pointerup', () => { this.onDragEnd(); this.onSheetDragEnd(); this.onGroupDragEnd(); });
                    window.addEventListener('pointercancel', () => { this.onDragEnd(); this.onSheetDragEnd(); this.groupDrag = null; });
                    window.addEventListener('beforeunload', event => {
                        if (this.dirty && !this.saving) event.preventDefault();
                    });
                    // What is being played in the sheet, worked out once per change (not once per chord shown), so
                    // following the music stays light.
                    Alpine.effect(() => {
                        this.playingSheetChord = this.currentSheetChord;
                    });
                    const follow = () => {
                        this.clock = this.now();
                        if (this.clickOnly && this.playing && this.atEnd()) { this.stopClock(); this.clickOffset = 0; }
                        if (this.isPlaying()) this.keepLoop();
                        this.scheduleClicks();
                        const tick = this.isPlaying() ? this.tickAt(this.clock) : this.currentTick;
                        if (tick !== this.currentTick) {
                            const before = this.currentBar;
                            const chordBefore = this.currentSheetChord;
                            this.currentTick = tick;
                            if (this.isPlaying() && this.currentBar !== before) { this.followAccordion(); this.scrollToBar(this.shownBar); }
                            const chord = this.currentSheetChord;
                            // A new block goes to the top of the chord sheet; within a block, the lit chord is kept in view.
                            if (this.isPlaying() && !this.followSheet() && chord >= 0 && chord !== chordBefore) this.scrollToSheetChord(chord);
                        }
                        requestAnimationFrame(follow);
                    };
                    requestAnimationFrame(follow);
                },

                // ---- Bars ----
                get bars() {
                    const size = this.perBar * 2;
                    const bars = [];
                    const timed = this.timedBlocks();
                    let previousOwner = null;
                    // Drawn again only when what a bar shows changes, not when it moves (equal bars told apart by their count).
                    const seen = {};
                    for (let start = 0; start < this.ticks.length; start += size) {
                        const part = this.ticks.slice(start, start + size);
                        const beats = [];
                        for (let i = 0; i < part.length; i += 2) {
                            const onBeat = part[i];
                            const previous = start + i > 0 ? this.ticks[start + i - 1] : null;
                            beats.push({
                                chord: this.transposeName(onBeat),
                                repeat: i > 0 && !!onBeat && onBeat === previous && !this.splits.includes(start + i),
                                and: part[i + 1] !== undefined && (part[i + 1] !== onBeat || this.splits.includes(start + i + 1)) ? this.transposeName(part[i + 1]) : null,
                            });
                        }
                        const blocks = this.blocks.filter(block => block.startTick >= start && block.startTick < start + size);
                        // The block of the bar: the one playing on its first beat (bar 1 belongs to the first block even
                        // when its first beats are empty). A bar of another block than the one before starts a new row.
                        const owner = this.blockOfTick(start) ?? timed[0] ?? null;
                        const groupStart = !!owner && owner !== previousOwner;
                        previousOwner = owner;
                        // Names shown in the bar: the "back to the start" markers and the blocks starting after its first
                        // beat (the block of a new row is named in its head; so is the first block in bar 1).
                        const chips = blocks.filter(block => this.isMarker(block) || !(groupStart && block === owner && (block.startTick === start || start === 0)));
                        // Drawn again only when what it shows changes (not on every change elsewhere in the song).
                        const content = [this.key, part.join(','), this.splits.filter(tick => tick >= start && tick < start + size).map(tick => tick - start).join(','),
                            blocks.map(block => `${block.id}:${block.startTick - start}:${block.data.section}:${block.data.jump ?? ''}`).join(','),
                            groupStart ? `head:${owner.id}:${owner.data.section}` : ''].join('|');
                        seen[content] = (seen[content] ?? 0) + 1;
                        bars.push({ beats, blocks, chips, group: groupStart ? owner : null, owner: owner?.id ?? null, key: `${content}#${seen[content]}` });
                    }
                    return bars;
                },
                get currentBar() { return this.currentTick < 0 ? -1 : Math.floor(this.currentTick / (this.perBar * 2)); },
                // The bar lit while playing: in a grouped run, the bar of the block in view played now by a hidden repetition.
                get shownBar() {
                    const bar = this.currentBar;
                    if (bar < 0) return bar;
                    const size = this.perBar * 2;
                    const owner = this.blockOfTick(bar * size);
                    if (!owner || !this.isHiddenRepeat(owner)) return bar;
                    const head = this.blocks.find(block => block.id === this.groupOf(owner)[0]);
                    return head ? Math.floor(head.startTick / size) + bar - Math.floor(owner.startTick / size) : bar;
                },
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
                scrollToBar(bar) {
                    this.$nextTick(() => document.querySelector(`[data-bar="${bar}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                },

                // ---- Time: bar 1 starts at the song's start (the delay in the recording); from there, only the BPM ----
                // (the blocks' own start times are not followed: with another BPM they would leave gaps between blocks).
                get halfBeat() { return 30 / this.bpm; },
                blockStart(block) {
                    const timed = this.timedBlocks();
                    const first = timed[0];
                    const origin = first ? (toSeconds(first.data.start) ?? 0) - first.startTick * this.halfBeat : 0;
                    if (this.freeTiming) {
                        const start = this.freeStarts(timed)[timed.indexOf(block)];
                        if (start !== undefined) return start;
                    }
                    return origin + block.startTick * this.halfBeat;
                },
                // Free timing: the start of each block (in the order of `timed`). The first one is the song's start;
                // each other one its marked time ("free_start") or, unmarked, right after the block before it at the
                // BPM. A mark not after the block before it is not followed.
                freeStarts(timed) {
                    const key = `${this.bpm}|${timed.map(block => `${block.startTick}:${block.data.free_start ?? ''}`).join(',')}|${timed[0]?.data.start ?? ''}`;
                    if (this.freeStartsCache?.key === key) return this.freeStartsCache.value;
                    const value = [];
                    timed.forEach((block, i) => {
                        if (i === 0) return value.push(toSeconds(block.data.start) ?? block.startTick * this.halfBeat);
                        const chained = value[i - 1] + (block.startTick - timed[i - 1].startTick) * this.halfBeat;
                        const mark = toSeconds(block.data.free_start);
                        value.push(mark !== null && mark > value[i - 1] ? mark : chained);
                    });
                    this.freeStartsCache = { key, value };
                    return value;
                },
                freeStartsCache: null,
                // Free timing on or off. The marks stay in the blocks either way (only followed while it is on). Turned
                // on, a block whose stored start is off the bars (a time marked before the song had bars) keeps that
                // time as its mark.
                toggleFreeTiming() {
                    this.remember();
                    this.markMenu = null;
                    if (this.freeTiming) {
                        this.freeTiming = false;
                        this.notice = @js(__('Following the bars: every block starts where the BPM puts it (the marked times are kept).'));
                        this.revision++;
                        return;
                    }
                    const timed = this.timedBlocks();
                    let previous = null;
                    let recovered = 0;
                    timed.forEach((block, i) => {
                        if (i > 0 && block.data.free_start === undefined) {
                            const stored = toSeconds(block.data.start);
                            const chained = previous + (block.startTick - timed[i - 1].startTick) * this.halfBeat;
                            if (stored !== null && stored > previous && Math.abs(stored - chained) > 0.15) {
                                block.data = { ...block.data, free_start: formatFineStart(stored) };
                                recovered++;
                            }
                        }
                        previous = this.freeStarts(timed.slice(0, i + 1))[i];
                    });
                    this.freeTiming = true;
                    this.notice = recovered
                        ? @js(__('Free timing: :n blocks kept the times marked before the bars. Use the clock of each block to mark the others.')).replace(':n', recovered)
                        : @js(__('Free timing: use the clock of each block, while the song plays, to mark where it starts.'));
                    this.revision++;
                },
                // The clock of a block (free timing): its start at the player's time. The first block's is the song's
                // start; for the others, the nearest bar line counted from the block before it is suggested, or the
                // exact time (the blocks after it count from there).
                openMark(block, event) {
                    if (!block || this.isMarker(block)) return;
                    if (!this.hasMusic || this.clickOnly) {
                        this.notice = @js(__('Play the song (with its sound) to mark the blocks.'));
                        return;
                    }
                    const timed = this.timedBlocks();
                    const i = timed.indexOf(block);
                    const time = Math.max(0, this.now());
                    const rect = event?.currentTarget?.getBoundingClientRect();
                    const place = rect ? { x: Math.min(rect.left, window.innerWidth - 300), y: rect.bottom + 4 } : { x: 16, y: 80 };
                    if (i <= 0) {
                        this.markMenu = { id: block.id, first: true, exact: time, ...place };
                        return;
                    }
                    const starts = this.freeStarts(timed);
                    const expected = starts[i - 1] + (block.startTick - timed[i - 1].startTick) * this.halfBeat;
                    const bar = this.perBar * 2 * this.halfBeat;
                    const bars = Math.round((time - expected) / bar);
                    const snapped = expected + bars * bar;
                    this.markMenu = {
                        id: block.id, first: false, exact: time, snapped, bars, expected,
                        // Far from any bar line (near the middle of a bar): the tempo probably changed there.
                        far: Math.abs(time - snapped) > bar * 0.3 || snapped <= starts[i - 1],
                        marked: block.data.free_start !== undefined,
                        ...place,
                    };
                },
                applyMark(value) {
                    const menu = this.markMenu;
                    this.markMenu = null;
                    const block = this.blocks.find(item => item.id === menu?.id);
                    if (!block) return;
                    this.remember();
                    if (menu.first) {
                        block.data = { ...block.data, start: formatFineStart(value) };
                    } else {
                        const data = { ...block.data };
                        // Where the block before it would end anyway: no mark needed.
                        if (value === null || Math.abs(value - menu.expected) < 0.005) delete data.free_start;
                        else data.free_start = formatFineStart(value);
                        block.data = data;
                    }
                    this.revision++;
                },
                markLabel(menu) {
                    if (!menu || menu.first) return '';
                    if (!menu.bars) return @js(__('on the bars'));
                    const n = Math.abs(menu.bars);
                    return (menu.bars > 0 ? '+' : '−') + n + ' ' + (n === 1 ? @js(__('bar')) : @js(__('bars')));
                },
                isMarked(block) { return this.freeTiming && block?.data?.free_start !== undefined && this.timedBlocks().indexOf(block) > 0; },
                sheetBlock(s) {
                    const timed = this.shownSheet.map((section, i) => (section.marker ? null : i)).filter(i => i !== null);
                    return this.timedBlocks()[timed.indexOf(s)] ?? null;
                },
                // Delay before bar 1 in the recording: moving it moves every block by the same time.
                get songStart() {
                    const first = this.timedBlocks()[0];
                    // Bar 1 (empty beats before the first chord included).
                    return first ? Math.max(0, Math.round((this.blockStart(first) - first.startTick * this.halfBeat) * 10) / 10) : 0;
                },
                setSongStart(value) {
                    const seconds = Math.max(0, Math.round(Number(value) * 10) / 10);
                    const timed = this.timedBlocks();
                    if (!timed.length || !Number.isFinite(seconds)) return;
                    const shift = seconds - (this.blockStart(timed[0]) - timed[0].startTick * this.halfBeat);
                    if (Math.abs(shift) < 0.05) return;
                    this.remember();
                    const starts = timed.map(block => this.blockStart(block) + shift);
                    timed.forEach((block, i) => { block.data = { ...block.data, start: formatStart(Math.max(0, starts[i])) }; });
                    this.revision++;
                },
                markStartNow() {
                    if (!this.canPlay || this.clickOnly) return;
                    this.setSongStart(this.now());
                },
                timeOfTick(tick) {
                    const block = this.blockOfTick(tick);
                    return block ? this.playStart(block) + (tick - block.startTick) * this.halfBeat : tick * this.halfBeat;
                },
                tickAt(time) {
                    const list = this.timedBlocks();
                    let tick = -1;
                    list.forEach((block, i) => {
                        const start = this.playStart(block);
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
                    else if (this.clickOnly) this.clickOffset = time;
                    else if (this.playerReady) this.seekVideo(time, false);
                    this.scrollToBar(this.currentBar);
                    this.followSheet(true);
                },
                get canPlay() { return !!audio || !!this.videoId || this.clickOnly; },
                get hasMusic() { return !!audio || !!this.videoId; },
                // Without an MP3 or a video (or with the music turned off) the song plays on its own clock, at the
                // BPM, with the metronome.
                get clickOnly() { return (this.musicOff || !this.hasMusic) && this.bpm > 0; },
                // Remembered on this device for each song.
                musicOff: (() => { try { return localStorage.getItem(`bars-music-off:${location.pathname}`) === '1'; } catch (error) { return false; } })(),
                // Plays with the music, or only with the BPM click, from the same bar.
                toggleMusic() {
                    const tick = Math.max(0, this.currentTick);
                    const wasPlaying = this.isPlaying();
                    if (wasPlaying) {
                        if (this.clickOnly) this.stopClock();
                        else if (audio) audio.pause();
                        else this.pauseVideo();
                    }
                    this.playing = false;
                    this.musicOff = !this.musicOff;
                    try { localStorage.setItem(`bars-music-off:${location.pathname}`, this.musicOff ? '1' : '0'); } catch (error) {}
                    // Only the BPM: the video goes away (it comes back with the music).
                    if (this.musicOff && this.videoId && !audio) this.closeVideo();
                    this.clickUntil = null;
                    if (wasPlaying) return this.playFromTick(tick);
                    this.moveTo(tick);
                },
                // Start of a block in the playback: its marked time with the music; on the BPM alone, its beats.
                playStart(block) { return this.clickOnly ? block.startTick * this.halfBeat : this.blockStart(block); },
                clickStartedAt: 0,
                clickOffset: 0,
                startClock(time) {
                    this.clickOffset = Math.max(0, time);
                    this.clickStartedAt = performance.now();
                    this.playing = true;
                },
                stopClock() {
                    this.clickOffset = this.now();
                    this.playing = false;
                },
                isPlaying() { return this.playing; },
                // The player's time. The MP3's time moves in small steps: between them it is estimated from the clock.
                now() {
                    if (this.clickOnly) return this.clickOffset + (this.playing ? (performance.now() - this.clickStartedAt) / 1000 : 0);
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
                    if (this.counting) return this.stopCountIn();
                    // With the count-in, from the start of the current bar (bar 1 before the song starts): the
                    // drumsticks lead right into it.
                    if (this.countIn && !this.isPlaying()) {
                        const size = this.perBar * 2;
                        return this.playFromTick(this.atEnd() ? 0 : Math.floor(Math.max(0, this.tickAt(this.clock)) / size) * size);
                    }
                    this.togglePlayNow();
                },
                togglePlayNow() {
                    if (audio) return audio.paused ? audio.play() : audio.pause();
                    if (this.clickOnly) return this.playing ? this.stopClock() : this.startClock(this.atEnd() ? 0 : this.clickOffset);
                    this.toggleVideo();
                },
                // Count-in: a bar of drumsticks at the BPM before the song starts playing (not when it jumps while
                // playing, e.g. repeating the marked bars). Remembered on this device.
                countIn: (() => { try { return localStorage.getItem('bars-count-in') === '1'; } catch (error) { return false; } })(),
                counting: false,
                countBeat: 0,
                countTimers: [],
                countSounds: [],
                toggleCountIn() {
                    this.countIn = !this.countIn;
                    try { localStorage.setItem('bars-count-in', this.countIn ? '1' : '0'); } catch (error) {}
                    if (!this.countIn) this.stopCountIn();
                },
                withCountIn(start) {
                    let context;
                    try {
                        context = this.clickContext = this.clickContext || new AudioContext();
                        if (context.state === 'suspended') context.resume();
                    } catch (e) { return start(); }
                    this.stopCountIn();
                    const rate = this.clickOnly ? 1 : audio ? (audio.playbackRate || 1) : (this.video?.speed || 1);
                    const beat = 60 / this.bpm / rate;
                    const lead = 0.1;
                    const at = context.currentTime + lead;
                    this.counting = true;
                    for (let i = 0; i < this.perBar; i++) {
                        this.stick(i === 0, context, at + i * beat);
                        this.countTimers.push(setTimeout(() => { this.countBeat = i + 1; }, (lead + i * beat) * 1000));
                    }
                    this.countTimers.push(setTimeout(() => {
                        this.counting = false;
                        this.countBeat = 0;
                        this.countTimers = [];
                        start();
                    }, (lead + this.perBar * beat) * 1000));
                },
                stopCountIn() {
                    this.countTimers.forEach(timer => clearTimeout(timer));
                    this.countSounds.forEach(sound => { try { sound.stop(); } catch (e) {} });
                    this.countTimers = [];
                    this.countSounds = [];
                    this.counting = false;
                    this.countBeat = 0;
                },
                // A drumstick: a short burst of noise, brighter on beat 1.
                stick(accent, context, when) {
                    try {
                        const length = Math.floor(context.sampleRate * 0.04);
                        const buffer = context.createBuffer(1, length, context.sampleRate);
                        const data = buffer.getChannelData(0);
                        for (let i = 0; i < length; i++) data[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / length, 3);
                        const source = context.createBufferSource();
                        source.buffer = buffer;
                        const filter = context.createBiquadFilter();
                        filter.type = 'bandpass';
                        filter.frequency.value = accent ? 3200 : 2400;
                        filter.Q.value = 1.5;
                        const gain = context.createGain();
                        gain.gain.value = accent ? 1.6 : 1.1;
                        source.connect(filter).connect(gain).connect(context.destination);
                        source.start(when);
                        this.countSounds.push(source);
                    } catch (e) {}
                },
                playFromTick(tick) {
                    if (this.counting) this.stopCountIn();
                    if (this.countIn && !this.isPlaying()) {
                        this.moveTo(tick);
                        return this.withCountIn(() => this.playFromTickNow(tick));
                    }
                    this.playFromTickNow(tick);
                },
                playFromTickNow(tick) {
                    const time = Math.max(0, this.timeOfTick(tick));
                    this.currentTick = tick;
                    this.followSheet(true);
                    if (audio) {
                        audio.currentTime = time;
                        audio.play();
                    } else if (this.clickOnly) {
                        this.startClock(time);
                    } else {
                        this.seekVideo(time);
                    }
                },
                // The clock went past the last bar.
                atEnd() { return this.ticks.length > 0 && this.clock >= this.timeOfTick(this.ticks.length - 1) + this.halfBeat; },
                formatTime(seconds) {
                    const total = Math.max(0, Math.floor(seconds || 0));
                    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
                },
                // m:ss.cc (a block's start).
                formatClock(seconds) {
                    const units = Math.max(0, Math.round((seconds || 0) * 100));
                    const whole = Math.floor(units / 100);
                    return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}.${String(units % 100).padStart(2, '0')}`;
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
                            if (chord && (chord !== last || this.splits.includes(tick))) { starts.push(tick); last = chord; }
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
                // The section of the block that starts playing at the top of the chord sheet (in its own scroll on large
                // screens; on small ones the page scrolls, below the toolbar).
                scrollToSheetSection(s) {
                    this.$nextTick(() => {
                        const section = document.querySelector(`.sheet-box [data-sheet="${s}"]`);
                        if (!section || !section.offsetParent) return;
                        const box = section.closest('.sheet-box');
                        if (box.scrollHeight > box.clientHeight + 4) {
                            const top = section.getBoundingClientRect().top - box.getBoundingClientRect().top;
                            box.scrollTo({ top: Math.max(0, box.scrollTop + top - 8), behavior: 'smooth' });
                        } else if (this.pane === 'sheet') {
                            section.style.scrollMarginTop = `${this.toolbarBottom() + 8}px`;
                            section.scrollIntoView({ block: 'start', behavior: 'smooth' });
                        }
                    });
                },
                // The block at the current place: its section at the top of the chord sheet when it is not the one
                // brought there last (or always, `force`, after a jump to a bar). True when it scrolled.
                sheetShownSection: -1,
                followSheet(force = false) {
                    const section = this.currentSheetSection;
                    if (section < 0 || (!force && section === this.sheetShownSection)) return false;
                    this.sheetShownSection = section;
                    this.scrollToSheetSection(section);
                    return true;
                },
                // Where the toolbar ends once it sticks to the top of the screen (what scrolled content must stay below).
                toolbarBottom() {
                    const bar = document.querySelector('.ws-toolbar');
                    if (!bar) return 0;
                    const style = getComputedStyle(bar);
                    return (style.position === 'sticky' ? parseFloat(style.top) || 0 : 0) + bar.offsetHeight;
                },
                // Keeps the lit chord in view (in the sheet's own scroll on large screens).
                scrollToSheetChord(n) {
                    this.$nextTick(() => {
                        const chord = document.querySelector(`.sheet-box [data-chord="${n}"]`);
                        if (!chord || !chord.offsetParent) return;
                        const box = chord.closest('.sheet-box');
                        if (box.scrollHeight > box.clientHeight + 4) {
                            const top = chord.getBoundingClientRect().top - box.getBoundingClientRect().top;
                            // Only when it is out of sight, so the block that came to the top stays there (a chord written
                            // at the end of the section before, above it, does not take the sheet back up).
                            const section = box.querySelector(`[data-sheet="${this.currentSheetSection}"]`);
                            const sectionShown = !!section && section.getBoundingClientRect().top >= box.getBoundingClientRect().top - 1;
                            if ((top < 0 && !sectionShown) || top > box.clientHeight * .85) {
                                box.scrollTo({ top: box.scrollTop + top - box.clientHeight * .15, behavior: 'smooth' });
                            }
                        } else if (this.pane === 'sheet') {
                            // The same on small screens, where the page scrolls (below the toolbar).
                            const below = this.toolbarBottom();
                            const section = box.querySelector(`[data-sheet="${this.currentSheetSection}"]`);
                            const top = chord.getBoundingClientRect().top;
                            if (top < below && section && section.getBoundingClientRect().top >= below - 1) return;
                            chord.style.scrollMarginTop = `${below + 8}px`;
                            chord.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        }
                    });
                },
                // ---- Moving a chord along the lyrics of the chord sheet (only where it falls changes) ----
                chordMove: null,
                // Edit mode: a chord of the sheet dragged along its line, letter by letter (never past its neighbours);
                // dropped, it waits to be saved.
                // When the sheet has the map's chords, the move joins the changes not saved yet (saved with the map, undone
                // with them); otherwise it is saved on its own.
                startSheetDrag(event, s, l, k) {
                    if (this.mode !== 'edit' || this.versionId || this.shownSheet[s]?.marker) return;
                    event.preventDefault();
                    event.stopPropagation();
                    const draft = this.sheetLinked;
                    if (this.dirty && !draft) {
                        this.saveError = true;
                        this.saveState = @js(__('Save the changes to the map before editing the lyrics.'));
                        return;
                    }
                    const shown = this.shownSheet;
                    const section = shown[s];
                    const textOf = (i) => JSON.stringify((shown[i]?.lines || []).map(item => (item.units ? lineText(item.units) : item.raw)));
                    const own = textOf(s);
                    const copies = shown.map((other, i) => i).filter(i => i !== s && !shown[i].marker && section.section && shown[i].section === section.section && textOf(i) === own);
                    const line = draft ? lineText(shown[s]?.lines?.[l]?.units || []) : String(this.sheet[s]?.lines?.[l] ?? '');
                    const same = !draft && this.chordMove && this.chordMove.s === s && this.chordMove.l === l;
                    const text = same ? this.chordMove.text : line;
                    const n = (draft ? shown[s] : this.sheetView[s])?.lines?.[l]?.units?.filter(unit => unit.chord !== null)[k]?.n;
                    this.chordMove = { s, l, k, n, text, original: same ? this.chordMove.original : line, units: parseLine(text).units, copies: copies.length, copyList: copies, draft, saving: false, error: '',
                        name: String(section.label || section.section || '').replaceAll('_', ' '), dragging: true, moved: false, x: event.clientX, y: event.clientY };
                },
                onSheetDragMove(event) {
                    const move = this.chordMove;
                    if (!move?.dragging) return;
                    if (!move.moved && Math.hypot(event.clientX - move.x, event.clientY - move.y) < 4) return;
                    move.moved = true;
                    const at = this.letterAt(move.s, move.l, event.clientX, event.clientY);
                    if (at !== null) this.placeChord(at);
                },
                // The place between letters of a sheet line nearest to the pointer (0: before the first letter). Only
                // across matters: the pointer may be on the chords' row, above the lyrics; on a line that wraps, the row
                // of lyrics nearest to the pointer is used.
                letterAt(s, l, x, y) {
                    const line = document.querySelector(`[data-sheet-line="${s}-${l}"]`);
                    if (!line) return null;
                    const letters = [];
                    line.querySelectorAll('.t').forEach(element => {
                        const from = Number(element.dataset.from);
                        const node = element.firstChild;
                        if (!node || node.nodeType !== 3 || !Number.isFinite(from)) return;
                        const range = document.createRange();
                        for (let i = 0; i < node.length; i++) {
                            range.setStart(node, i);
                            range.setEnd(node, i + 1);
                            const box = range.getBoundingClientRect();
                            if (box.width || box.height) letters.push({ at: from + i, left: box.left, right: box.right, middle: (box.top + box.bottom) / 2 });
                        }
                    });
                    if (!letters.length) return null;
                    // The chords sit a row above the lyrics: the pointer counts as being on the row below it.
                    const chord = line.querySelector('.c.is-moving') || line.querySelector('.c');
                    const text = chord?.closest('.sheet-seg')?.querySelector('.t');
                    const shift = chord && text ? text.getBoundingClientRect().top - chord.getBoundingClientRect().top : 0;
                    const row = letters.reduce((best, letter) => (Math.abs(letter.middle - (y + shift)) < Math.abs(best - (y + shift)) ? letter.middle : best), letters[0].middle);
                    const inRow = letters.filter(letter => Math.abs(letter.middle - row) < 2);
                    for (const letter of inRow) {
                        if (x < (letter.left + letter.right) / 2) return letter.at;
                    }
                    return inRow[inRow.length - 1].at + 1;
                },
                onSheetDragEnd() {
                    const move = this.chordMove;
                    if (!move?.dragging) return;
                    move.dragging = false;
                    if (!move.moved) this.selectSheetChord(move.n);
                    if (move.text === move.original) { this.chordMove = null; return; }
                    if (!move.draft) return;
                    this.chordMove = null;
                    this.remember();
                    // The same line of every copy of the section (same name and lines) moves along, without asking.
                    const moves = { ...this.sheetMoves };
                    [move.s, ...move.copyList].forEach(i => { moves[`${i}:${move.l}`] = move.text; });
                    this.sheetMoves = moves;
                },
                // The beat where chord `n` of the sheet starts, selected on the grid.
                selectSheetChord(n) {
                    const starts = this.chordStarts();
                    if (n === undefined || this.isPlaying() || starts.length !== this.shownSheet.chordCount || starts[n] === undefined) return;
                    this.currentTick = starts[n];
                    this.selectAnchor(this.currentBar);
                    this.scrollToBar(this.currentBar);
                },
                sheetMoves: {},
                // ---- Blocks moved, duplicated or removed (Lines view): the bars and the chord sheet's sections go along ----
                // `sheetBase`: the chord sheet as reshaped, and the chords it has (the preview starts from it).
                sheetBase: null,
                sheetBaseRevision: 0,
                baseViewCache: { key: null, value: null },
                get baseSheetView() {
                    if (!this.sheetBase) return this.sheetView;
                    if (this.baseViewCache.key !== this.sheetBaseRevision) this.baseViewCache = { key: this.sheetBaseRevision, value: parseSheet(this.sheetBase.sections) };
                    return this.baseViewCache.value;
                },
                // The blocks in order, each with its ticks and its splits (counted from its start).
                blockItems() {
                    const list = [...this.blocks].sort((a, b) => a.startTick - b.startTick);
                    const timed = list.filter(block => !this.isMarker(block));
                    return list.map(block => {
                        if (this.isMarker(block)) return { block, ticks: [], splits: [] };
                        const next = timed[timed.indexOf(block) + 1];
                        const end = next ? next.startTick : this.ticks.length;
                        return { block, ticks: this.ticks.slice(block.startTick, end), splits: this.splits.filter(tick => tick >= block.startTick && tick < end).map(tick => tick - block.startTick) };
                    });
                },
                // The chord sheet as shown, one section per block (when it follows the map block by block), as text.
                sheetSections() {
                    const view = this.dirty ? this.previewSheet.view : this.sheetView;
                    if (!this.sheetLinked || view.length !== this.blocks.length) return null;
                    const list = [...this.blocks].sort((a, b) => a.startTick - b.startTick);
                    if (view.some((section, i) => !!section.marker !== this.isMarker(list[i]))) return null;
                    return view.map(section => ({
                        section: section.section, label: section.label, ...(section.marker ? { jump: 'start' } : {}),
                        lines: section.lines.map(line => (line.units ? lineText(line.units) : line.raw ?? '')),
                    }));
                },
                // Blocks and sheet sections reshaped by `change(list)` (the same change for both lists). From the first
                // block that changed on, the blocks follow one another at the tempo (the recording's times no longer
                // apply there); that first one starts where the block at its place used to start.
                reshapeBlocks(change) {
                    const items = this.blockItems();
                    const sections = this.sheetSections();
                    const before = items.map(item => item.block.id);
                    const slotStarts = items.map(item => (this.isMarker(item.block) ? null : this.blockStart(item.block)));
                    this.remember();
                    const reshaped = change([...items]);
                    const first = reshaped.findIndex((item, i) => item.block.id !== before[i]);
                    // The empty beats before the first chord (the song's lead) stay at the start, before whichever block is first.
                    const lead = this.timedBlocks()[0]?.startTick ?? 0;
                    let tick = lead;
                    const ticks = this.ticks.slice(0, lead);
                    const splits = this.splits.filter(split => split < lead);
                    this.blocks = reshaped.map((item, i) => {
                        const block = { ...item.block, startTick: tick, data: { ...item.block.data } };
                        if (first >= 0 && i >= first && !this.isMarker(block)) {
                            const at = i === first ? slotStarts.slice(i).find(start => start !== null) : null;
                            if (at !== null && at !== undefined) block.data.start = formatStart(at);
                            else delete block.data.start;
                        }
                        ticks.push(...item.ticks);
                        item.splits.forEach(offset => splits.push(tick + offset));
                        tick += item.ticks.length;
                        return block;
                    });
                    this.ticks = ticks;
                    this.splits = splits;
                    if (sections) {
                        this.sheetBase = { sections: change(sections), chords: this.timedBlocks().flatMap(block => this.blockChords(block)) };
                        this.sheetMoves = {};
                    } else if (this.sheet.length) {
                        this.notice = @js(__('The chord sheet does not follow the map block by block: only the bars changed.'));
                    }
                    this.sheetBaseRevision++;
                    this.revision++;
                },
                // Index of a block among all blocks in order (markers too).
                blockIndex(block) { return [...this.blocks].sort((a, b) => a.startTick - b.startTick).findIndex(item => item.id === block.id); },
                // Ticks before the song's first chord (empty beats in bar 1).
                get leadTicks() { return this.timedBlocks()[0]?.startTick ?? 0; },
                // ---- Review: "Save as reviewed" once the bars are right ----
                reviewedAt: config.reviewedAt || null,
                formatDay(iso) { const date = iso ? new Date(iso) : null; return date && !isNaN(date) ? date.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit', year: 'numeric' }) : ''; },
                unmarkReviewed() {
                    if (confirm(@js(__('Undo the review of this song? It will no longer show as reviewed.')))) this.save({ reviewed: false });
                },
                // ---- Equal blocks played one after the other: a run of the same block "×N" ----
                repeatCache: { key: null, value: null },
                // For each block, the first of its run of equal blocks (same name, chords, durations), and each run's blocks.
                get repeats() {
                    const key = `${this.revision}:${this.blocks.map(block => `${block.id}@${block.startTick}`).join(',')}`;
                    if (this.repeatCache.key === key) return this.repeatCache.value;
                    const head = {};
                    const members = {};
                    let current = null;
                    let currentSignature = null;
                    this.blockItems().forEach(item => {
                        const signature = this.isMarker(item.block) ? null : `${this.blockName(item.block)}|${item.ticks.join(' ')}|${item.splits.join(',')}`;
                        if (signature !== null && signature === currentSignature) {
                            members[current].push(item.block.id);
                            head[item.block.id] = current;
                        } else {
                            current = item.block.id;
                            currentSignature = signature;
                            members[current] = [current];
                            head[current] = current;
                        }
                    });
                    this.repeatCache = { key, value: { head, members } };
                    return this.repeatCache.value;
                },
                groupOf(block) { return this.repeats.members[this.repeats.head[block.id]] || [block.id]; },
                // A run is shown grouped (one block "×N", its bars once) until "Show each block" is chosen on it.
                expandedGroups: {},
                isCollapsed(block) { const ids = this.groupOf(block); return ids.length > 1 && !this.expandedGroups[ids[0]]; },
                isHiddenRepeat(block) { return this.isCollapsed(block) && this.groupOf(block)[0] !== block.id; },
                isHiddenBar(bar) {
                    const owner = bar.owner === null ? null : this.blocks.find(block => block.id === bar.owner);
                    if (!owner) return false;
                    return this.isHiddenRepeat(owner) || (this.accordion && !this.isOpenBlock(owner));
                },
                // ---- Accordion: every block collapsed to its head, one open at a time ----
                accordion: (() => { try { return localStorage.getItem('bars-accordion') === '1'; } catch (error) { return false; } })(),
                openBlockId: null,
                toggleAccordion() {
                    this.accordion = !this.accordion;
                    try { localStorage.setItem('bars-accordion', this.accordion ? '1' : '0'); } catch (error) {}
                    // The block being played (or the one of the selected bar) stays open.
                    const block = this.blockOfTick(this.currentTick >= 0 ? this.currentTick : (this.selection.anchor ?? -1) * this.perBar * 2);
                    this.openBlockId = this.accordion && block ? this.shownHead(block).id : null;
                },
                // A grouped run opens and closes as one block (its first one).
                shownHead(block) { return this.isCollapsed(block) ? this.blocks.find(item => item.id === this.groupOf(block)[0]) ?? block : block; },
                isOpenBlock(block) { return !!block && this.openBlockId === this.shownHead(block).id; },
                toggleOpenBlock(block) {
                    const id = this.shownHead(block).id;
                    this.openBlockId = this.openBlockId === id ? null : id;
                    if (this.openBlockId !== null) this.$nextTick(() => document.querySelector(`[data-group-block="${id}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                },
                // While playing, the block playing opens (the others close).
                followAccordion() {
                    if (!this.accordion) return;
                    const block = this.blockOfTick(this.currentTick);
                    if (block && !this.isOpenBlock(block)) this.openBlockId = this.shownHead(block).id;
                },
                toggleGroup(block) {
                    const head = this.groupOf(block)[0];
                    this.expandedGroups = { ...this.expandedGroups, [head]: !this.expandedGroups[head] };
                },
                // The whole run of a grouped block taken out.
                removeRun(block) {
                    const ids = this.groupOf(block);
                    if (this.timedBlocks().length <= ids.length) return;
                    this.reshapeBlocks(items => items.filter(item => !ids.includes(item.block?.id)));
                    this.notice = @js(__('Block removed (Undo brings it back).'));
                },
                // A change on a block of a run goes to every block of the run (one step to undo).
                forGroup(block, change) {
                    this.remember();
                    const ids = this.groupOf(block);
                    ids.forEach(id => { const target = this.blocks.find(item => item.id === id); if (target) change(target); });
                },
                // The block played `times` times one after the other: copies added after its run, or the last ones taken out.
                setRepeats(block, times) {
                    const ids = this.groupOf(block);
                    const wanted = Math.max(1, Math.min(50, Math.round(Number(times) || 1)));
                    if (wanted === ids.length) return;
                    const list = [...this.blocks].sort((a, b) => a.startTick - b.startTick);
                    const last = list.findIndex(item => item.id === ids[ids.length - 1]);
                    if (wanted > ids.length) {
                        const newIds = Array.from({ length: wanted - ids.length }, () => nextId++);
                        this.reshapeBlocks(items => {
                            const copies = newIds.map(id => (items[last].block
                                ? { ...items[last], block: { ...items[last].block, id, data: JSON.parse(JSON.stringify(items[last].block.data)) } }
                                : JSON.parse(JSON.stringify(items[last]))));
                            items.splice(last + 1, 0, ...copies);
                            return items;
                        });
                    } else {
                        const remove = ids.length - wanted;
                        this.reshapeBlocks(items => { items.splice(last - remove + 1, remove); return items; });
                    }
                    this.notice = @js(__(':name is played :n times.')).replace(':name', this.blockName(block)).replace(':n', wanted);
                },
                duplicateBlock(block) {
                    const at = this.blockIndex(block);
                    const id = nextId++;
                    this.reshapeBlocks(items => {
                        const copy = items[at].block ? { ...items[at], block: { ...items[at].block, id, data: JSON.parse(JSON.stringify(items[at].block.data)) } } : JSON.parse(JSON.stringify(items[at]));
                        items.splice(at + 1, 0, copy);
                        return items;
                    });
                },
                removeBlock(block) {
                    if (this.timedBlocks().length < 2) return;
                    const at = this.blockIndex(block);
                    this.reshapeBlocks(items => { items.splice(at, 1); return items; });
                    this.notice = @js(__('Block removed (Undo brings it back).'));
                },
                // A line of the sheet drawn again whenever its chords, their places or their marks change.
                sheetLineKey(line, l) {
                    return `${l}:` + (line.units ? line.units.map(unit => `${unit.chord ?? ''}|${unit.text}${unit.added || unit.changed ? '+' : ''}`).join('~') : (line.comment ?? ''));
                },
                // The chord sheet has exactly the chords of the saved map (chord n of the sheet is chord n of the map).
                linkedCache: { key: null, base: null, value: false },
                get sheetLinked() {
                    if (this.sheetBase) return true;
                    if (this.linkedCache.key === this.sheetRevision && this.linkedCache.base === this.baseStructure) return this.linkedCache.value;
                    const saved = chordsOnly(this.baseStructure.flatMap(block => (Array.isArray(block?.chords) ? block.chords.map(String) : [])));
                    const inSheet = this.sheetView.flatMap(section => section.lines.flatMap(line => (line.units || []).filter(unit => unit.chord !== null).map(unit => unit.chord)));
                    const value = inSheet.length > 0 && JSON.stringify(inSheet) === JSON.stringify(saved);
                    this.linkedCache = { key: this.sheetRevision, base: this.baseStructure, value };
                    return value;
                },
                // The moving chord at letter `at` of the line's lyrics.
                placeChord(at) {
                    const move = this.chordMove;
                    const marks = [];
                    let plain = '';
                    let last = 0;
                    for (const match of move.text.matchAll(/\[([^\]]+)\]/g)) {
                        plain += move.text.slice(last, match.index);
                        marks.push({ at: plain.length, chord: match[1] });
                        last = match.index + match[0].length;
                    }
                    plain += move.text.slice(last);
                    const mark = marks[move.k];
                    if (!mark) return;
                    const low = move.k > 0 ? marks[move.k - 1].at : 0;
                    const high = move.k < marks.length - 1 ? marks[move.k + 1].at : plain.length;
                    const place = Math.max(low, Math.min(high, at));
                    if (place === mark.at) return;
                    mark.at = place;
                    let text = '';
                    let from = 0;
                    marks.forEach(item => { text += plain.slice(from, item.at) + `[${item.chord}]`; from = item.at; });
                    move.text = text + plain.slice(from);
                    move.units = parseLine(move.text).units;
                },
                async saveChordMove(same) {
                    const move = this.chordMove;
                    if (!move || move.saving) return;
                    move.saving = true;
                    move.error = '';
                    try {
                        const lines = [...(this.sheet[move.s]?.lines || [])];
                        lines[move.l] = move.text;
                        await this.putSheetSection(move.s, lines, same);
                        this.chordMove = null;
                    } catch (error) {
                        move.error = error.message;
                    } finally {
                        move.saving = false;
                    }
                },
                // Saves one section of the chord sheet (and its copies when `same`); the page takes the new sheet and lyrics.
                async putSheetSection(index, lines, same) {
                    const response = await fetch(config.sheetSectionUrl, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ index, lines, updated_at: this.updatedAt, same }),
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
                    this.saveError = false;
                    this.saveState = @js(__('Lyrics saved ✓'));
                    return result;
                },

                // ---- Editing the lyrics of a section of the chord sheet (saved on its own) ----
                editSheetSection(s) {
                    if (this.dirty) {
                        this.saveError = true;
                        this.saveState = @js(__('Save the changes to the map before editing the lyrics.'));
                        return;
                    }
                    const section = this.sheet[s] || {};
                    const lines = JSON.stringify(section.lines || []);
                    // Copies: the other sections with the same name written exactly the same way.
                    const copies = this.sheet.filter((other, i) => i !== s && !other.jump && section.section && other.section === section.section
                        && JSON.stringify(other.lines || []) === lines).length;
                    this.sheetEdit = { index: s, text: (section.lines || []).join('\n'), error: '', saving: false, copies,
                        name: String(section.label || section.section || '').replaceAll('_', ' ') };
                },
                async saveSheetSection(same = false) {
                    const edit = this.sheetEdit;
                    if (!edit || edit.saving) return;
                    // Chords pasted over the lyrics and not interpreted yet: put into the lyrics first.
                    if (hasChordsOverLyrics(edit.text)) edit.text = chordsOverLyrics(edit.text);
                    edit.saving = true;
                    edit.error = '';
                    // Chords swapped: the map changed too, the page reads it again.
                    const chordsOf = (text) => [...text.matchAll(/\[([^\]]+)\]/g)].map(match => match[1]).join(' ');
                    const swapped = chordsOf(edit.text) !== chordsOf((this.sheet[edit.index]?.lines || []).join('\n'));
                    try {
                        await this.putSheetSection(edit.index, edit.text.split('\n'), same);
                        if (swapped) { this.keepPlace(); return window.location.reload(); }
                        this.sheetEdit = null;
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
                    if (!this.versionId) return this.dirty ? this.previewSheet.view : this.sheetView;
                    const key = `${this.versionId}:${this.revision}:${this.sheetRevision}`;
                    if (this.shownSheetCache.key !== key) {
                        this.shownSheetCache = { key, value: parseSheet(sheetWithChords(this.sheet, this.baseStructure, this.toBlocks())) };
                    }
                    return this.shownSheetCache.value;
                },
                // ---- The chord sheet as it will be once the map is saved: chords removed on the grid left out, added or
                // changed ones marked (an added chord goes right after the chord before it, as when saved) ----
                previewCache: { key: null, value: null },
                get previewSheet() {
                    const key = `${this.revision}:${this.sheetRevision}:${this.sheetBaseRevision}:${this.splits.join(',')}:${JSON.stringify(this.sheetMoves)}`;
                    if (this.previewCache.key === key) return this.previewCache.value;
                    const sheetView = this.baseSheetView;
                    const saved = this.sheetBase ? this.sheetBase.chords : chordsOnly(this.baseStructure.flatMap(block => (Array.isArray(block?.chords) ? block.chords.map(String) : [])));
                    const now = this.timedBlocks().flatMap(block => this.blockChords(block));
                    let value = { view: sheetView, changed: false };
                    if (this.sheetLinked && JSON.stringify(saved) !== JSON.stringify(now)) {
                        // Where each chord starts, as saved and now (unknown after chords were moved on the sheet): its
                        // block and its place in it, so a chord is only taken for the same one inside the same block (a bar
                        // duplicated in one block moves the next blocks, but their chords stay theirs).
                        const BLOCK = 1e6;
                        let savedAt = null;
                        const timedNow = this.timedBlocks();
                        const timedSaved = this.baseStructure.filter(block => block && !isMarkerData(block));
                        if (!this.sheetBase && timedSaved.length === timedNow.length) {
                            savedAt = [];
                            timedSaved.forEach((block, b) => {
                                let tick = b === 0 ? Math.round((Number(block.lead_beats) || 0) * 2) : 0;
                                const chords = chordsOnly(Array.isArray(block.chords) ? block.chords.map(String) : []);
                                chords.forEach((chord, i) => { savedAt.push(b * BLOCK + tick); tick += Math.round((block.durations?.[i] ?? this.perBar) * 2); });
                            });
                        }
                        const nowAt = timedNow.flatMap((block, b) => this.blockChordTicks(block).map(tick => b * BLOCK + tick - (b === 0 ? 0 : block.startTick)));
                        const { replace, after } = savedAt && savedAt.length === saved.length && nowAt.length === now.length
                            ? chordChanges(saved, now, savedAt, nowAt) : chordChanges(saved, now);
                        const added = (chords, text) => chords.map((chord, k) => ({ chord, text: k === chords.length - 1 ? text : '', added: true }));
                        const view = sheetView.map(section => ({ ...section, lines: section.lines.map(line => {
                            if (!line.units) return line;
                            const units = [];
                            line.units.forEach(unit => {
                                if (unit.chord === null) return units.push(unit);
                                const n = unit.n;
                                const before = n === 0 ? (after[-1] || []) : [];
                                if (before.length) units.push(...added(before, ''));
                                const place = replace[n] || [];
                                const extra = after[n] || [];
                                const own = place.length === 0 ? { ...unit, chord: null }
                                    : { ...unit, chord: place[0], changed: place[0] !== unit.chord };
                                if (!extra.length) return units.push(own);
                                units.push({ ...own, text: '' }, ...added(extra, unit.text));
                            });
                            return { ...line, units };
                        }) }));
                        // Chords counted again: in each line (for moving them) and in the whole sheet (to light the chord of
                        // the selected or playing beat, the n-th chord of the map as it is now).
                        let n = 0;
                        view.forEach(section => section.lines.forEach(line => {
                            let k = 0;
                            if (line.units) line.units = line.units.map(unit => (unit.chord !== null ? { ...unit, ordinal: k++, n: n++ } : { ...unit, ordinal: undefined }));
                        }));
                        view.chordCount = n;
                        value = { view, changed: true };
                    }
                    // Split like the blocks of the grid as they are now (a block created, renamed, joined…).
                    if (this.sheetLinked) value = { view: this.sheetByBlocks(value.view), changed: value.changed };
                    // Chords moved by hand along the lyrics (kept while the line has the same chords).
                    if (Object.keys(this.sheetMoves).length) {
                        const chordsOf = (units) => units.filter(unit => unit.chord !== null).map(unit => unit.chord).join(' ');
                        const view = value.view.map((section, s) => ({ ...section, lines: section.lines.map((line, l) => {
                            const moved = this.sheetMoves[`${s}:${l}`];
                            if (moved === undefined || !line.units) return line;
                            const units = parseLine(moved).units;
                            if (chordsOf(units) !== chordsOf(line.units)) return line;
                            const marks = line.units.filter(unit => unit.chord !== null);
                            return { ...line, units: units.map(unit => (unit.chord === null ? unit
                                : { ...unit, n: marks[unit.ordinal]?.n, added: marks[unit.ordinal]?.added, changed: marks[unit.ordinal]?.changed })) };
                        }) }));
                        view.chordCount = value.view.chordCount;
                        value = { view, changed: value.changed };
                    }
                    this.previewCache = { key, value };
                    return value;
                },
                // The chord sheet (parsed) split into the blocks of the grid as they are now, the same rule as the server
                // (SyncChordSheetWithStructureService): each line goes to the block of its first chord, a note with the
                // chord after it, lyrics without chords with the chord before them; "back to the start" marks between.
                // Unchanged when the sheet does not have the grid's number of chords.
                sheetByBlocks(view) {
                    const list = [...this.blocks].sort((a, b) => a.startTick - b.startTick);
                    const counts = list.map(block => (this.isMarker(block) ? 0 : this.blockChords(block).length));
                    const inView = view.reduce((n, section) => n + section.lines.reduce((m, line) => m + (line.units ? line.units.filter(unit => unit.chord !== null).length : 0), 0), 0);
                    if (!counts.some(Boolean) || counts.reduce((a, b) => a + b, 0) !== inView) return view;
                    const ends = [];
                    counts.reduce((total, count) => { ends.push(total + count); return total + count; }, 0);
                    const blockOf = (n) => {
                        const index = ends.findIndex((end, k) => counts[k] > 0 && n < end);
                        return index < 0 ? ends.length - 1 : index;
                    };
                    const groups = [];
                    let n = 0;
                    let last = 0;
                    view.forEach(section => {
                        if (section.marker) return;
                        section.lines.forEach(line => {
                            let ref = n;
                            if (line.units) {
                                const count = line.units.filter(unit => unit.chord !== null).length;
                                ref = count ? n : last;
                                if (count) last = n + count - 1;
                                n += count;
                            }
                            const block = blockOf(ref);
                            const group = groups[groups.length - 1];
                            if (group && group.block === block) group.lines.push(line);
                            else groups.push({ block, lines: [line] });
                        });
                    });
                    const result = [];
                    list.forEach((block, index) => {
                        const name = String(block.data.section ?? '').trim();
                        const key = name.toUpperCase().replace(/\s+/g, '_');
                        if (this.isMarker(block)) return result.push({ section: key, label: name.replaceAll('_', ' '), lines: [], marker: true });
                        const group = groups.find(item => item.block === index);
                        if (group) result.push({ section: key, label: name.replaceAll('_', ' '), lines: group.lines, marker: false });
                    });
                    result.chordCount = view.chordCount;
                    return result;
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
                    this.splits = parsed.splits;
                    this.originalSplits = [...parsed.splits];
                    this.sheetMoves = {};
                    this.sheetBase = null;
                    this.perBar = parsed.perBar;
                    this.versionId = version ? version.id : null;
                    this.key = this.savedKey;
                    this.history = [];
                    this.originalTicks = [...this.ticks];
                    this.selection = { anchor: null, from: null, to: null };
                    this.loop = null;
                    this.inline = null;
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
                    this.place(this.ref('versionMenu'), element);
                    this.ref('instrumentName').value = '';
                    this.$nextTick(() => this.ref('instrumentName').focus());
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
                    const instrument = this.ref('instrumentName').value.trim();
                    if (!instrument) return this.ref('instrumentName').focus();
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
                        this.splits = this.splits.filter(tick => ticks[tick] !== undefined && ticks[tick] === ticks[tick - 1]);
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
                setMode(mode) { this.mode = mode; this.inline = null; this.editor = null; this.barMenu = null; },
                remember() {
                    this.history.push(JSON.stringify({ ticks: this.ticks, blocks: this.blocks, splits: this.splits, sheetMoves: this.sheetMoves, sheetBase: this.sheetBase, perBar: this.perBar, freeTiming: this.freeTiming }));
                    if (this.history.length > 80) this.history.shift();
                    this.markDirty();
                },
                undo() {
                    if (!this.history.length) return;
                    const state = JSON.parse(this.history.pop());
                    this.ticks = state.ticks;
                    this.blocks = state.blocks;
                    this.splits = state.splits || [];
                    this.sheetMoves = state.sheetMoves || {};
                    this.sheetBase = state.sheetBase || null;
                    if (state.perBar) this.perBar = state.perBar;
                    if (state.freeTiming !== undefined) this.freeTiming = state.freeTiming;
                    this.sheetBaseRevision++;
                    this.revision++;
                    this.markDirty();
                },
                // A new time signature only redraws the bar lines: the chords keep their beats (3/4 "C / / | D / /"
                // becomes 4/4 "C / / D | / /").
                setMeter(beats) {
                    if (!beats || beats === this.perBar) return;
                    this.remember();
                    this.perBar = beats;
                    this.barMenu = null;
                    this.loop = null;
                    this.revision++;
                },
                markDirty() {
                    this.dirty = true;
                    this.saveError = false;
                    this.saveState = '';
                },
                clickBeat(bar, beat, cell, event) {
                    if (this.pressDone() || performance.now() - this.dragEnded < 300) return;
                    if (event?.metaKey || event?.ctrlKey) return this.openBarMenu(bar, cell.closest('.bar'));
                    if (event?.shiftKey) return this.selectTo(bar);
                    this.selectAnchor(bar);
                    // The "and" of the beat when it was clicked, or when only there a chord starts.
                    const onBeat = (bar * this.perBar + beat) * 2;
                    const andStarts = this.ticks[onBeat + 1] !== undefined && this.startsChord(onBeat + 1);
                    const onAnd = andStarts && (!!event?.target?.closest?.('.is-and') || !(onBeat === 0 || this.startsChord(onBeat)));
                    const tick = onBeat + (onAnd ? 1 : 0);
                    if (this.mode === 'play') {
                        if (this.canPlay) return this.playFromTick(tick);
                        this.currentTick = tick;
                        return this.followSheet(true);
                    }
                    // Edit mode: a click selects the beat (Backspace removes its change); a double click edits its chord.
                    // On a beat where no chord starts, the chord can be typed right there. A tap opens the editor.
                    if (!this.isPlaying()) this.currentTick = tick;
                    this.barMenu = null;
                    if (!this.followSheet()) this.scrollToSheetChord(this.currentSheetChord);
                    if (this.touch) return this.openBeatEditor(bar, beat, cell, onAnd ? 1 : (andStarts && !this.chordStartsAt(onBeat) ? 1 : 0));
                    if (!this.isPlaying() && !andStarts && !this.chordStartsAt(onBeat)) this.openInline(onBeat, '');
                },
                // Double click: the chord of the beat (or of its "and" when that was clicked, or when only there a chord
                // starts) typed again right in the cell.
                editBeat(bar, beat, event) {
                    const onBeat = (bar * this.perBar + beat) * 2;
                    const andStarts = this.ticks[onBeat + 1] !== undefined && this.startsChord(onBeat + 1);
                    const onAnd = andStarts && (!!event?.target?.closest?.('.is-and') || !this.chordStartsAt(onBeat));
                    const cell = event?.target?.closest?.('.beat');
                    if (cell) return this.openBeatEditor(bar, beat, cell, onAnd ? 1 : 0);
                    this.editTick(onBeat + (onAnd ? 1 : 0));
                },
                editTick(tick) {
                    const existing = this.chordStartsAt(tick);
                    this.openInline(tick, existing ? this.transposeName(this.ticks[tick]) || '' : '', existing);
                },
                // ---- Dragging where a chord starts, half a beat at a time (Edit mode) ----
                drag: null,
                dragEnded: 0,
                // A chord starts at `tick` inside its block (the first chord of a block stays where the block starts).
                isChangeAt(tick) {
                    return tick > 0 && this.ticks[tick] !== undefined && this.startsChord(tick) && this.blockOfTick(tick)?.startTick !== tick;
                },
                startsChord(tick, ticks = this.ticks, splits = this.splits) {
                    return ticks[tick] !== ticks[tick - 1] || splits.includes(tick);
                },
                // A chord change, or the first chord of a block (moved within the block's first bar).
                canDragAt(tick) { return this.isChangeAt(tick) || this.isBlockStartAt(tick); },
                isBlockStartAt(tick) { return !this.versionId && !!this.ticks[tick] && this.timedBlocks().some(block => block.startTick === tick); },
                startDrag(event, bar, beat, half) {
                    const tick = (bar * this.perBar + beat) * 2 + half;
                    if (this.mode === 'edit' && this.isBlockStartAt(tick)) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.inline = null;
                        this.editor = null;
                        this.copyOffer = null;
                        this.drag = { blockStart: true, from: tick, to: tick, base: [...this.ticks], baseSplits: [...this.splits], baseBlocks: this.blocks.map(block => ({ ...block, data: { ...block.data } })), x: event.clientX, y: event.clientY, moved: false };
                        return;
                    }
                    if (this.mode !== 'edit' || !this.isChangeAt(tick)) return;
                    event.preventDefault();
                    event.stopPropagation();
                    this.inline = null;
                    this.editor = null;
                    this.copyOffer = null;
                    this.drag = { from: tick, to: tick, base: [...this.ticks], baseSplits: [...this.splits], x: event.clientX, y: event.clientY, moved: false };
                },
                onDragMove(event) {
                    const drag = this.drag;
                    if (!drag) return;
                    if (!drag.moved && Math.hypot(event.clientX - drag.x, event.clientY - drag.y) < 6) return;
                    drag.moved = true;
                    const cell = document.elementFromPoint(event.clientX, event.clientY)?.closest('.beat');
                    const bar = Number(cell?.closest('.bar')?.dataset.bar);
                    const beat = Number(cell?.dataset.beat);
                    if (!cell || !Number.isFinite(bar) || !Number.isFinite(beat)) return;
                    const box = cell.getBoundingClientRect();
                    const target = (bar * this.perBar + beat) * 2 + (event.clientX > box.left + box.width / 2 ? 1 : 0);
                    if (drag.blockStart) {
                        const [to, ticks, splits, blocks] = this.movedBlockStart(drag.base, drag.baseSplits, drag.baseBlocks, drag.from, target);
                        if (to === drag.to) return;
                        drag.to = to;
                        this.ticks = ticks;
                        this.splits = splits;
                        this.blocks = blocks;
                        this.revision++;
                        return;
                    }
                    const [to, ticks, splits] = this.dragResult(drag.base, drag.baseSplits, drag.from, target);
                    if (to === drag.to) return;
                    drag.to = to;
                    this.ticks = ticks;
                    this.splits = splits;
                    this.revision++;
                },
                onDragEnd() {
                    const drag = this.drag;
                    if (!drag) return;
                    this.drag = null;
                    if (!drag.moved) return;
                    this.dragEnded = performance.now();
                    if (drag.to === drag.from) return;
                    if (drag.blockStart) {
                        // Undo goes back to before the drag.
                        const moved = [this.ticks, this.splits, this.blocks];
                        [this.ticks, this.splits, this.blocks] = [drag.base, drag.baseSplits, drag.baseBlocks];
                        this.remember();
                        [this.ticks, this.splits, this.blocks] = moved;
                        this.originalTicks = [];
                        this.revision++;
                        const beat = Math.floor((drag.to % (this.perBar * 2)) / 2) + 1;
                        this.notice = @js(__('The block now starts on beat :n of its bar.')).replace(':n', beat + (drag.to % 2 ? ' e' : ''));
                        return;
                    }
                    // Undo goes back to before the drag; the equal blocks are offered (compared as they were).
                    const moved = [this.ticks, this.splits];
                    this.ticks = drag.base;
                    this.splits = drag.baseSplits;
                    this.remember();
                    const found = this.copiesBefore(drag.from);
                    [this.ticks, this.splits] = moved;
                    this.revision++;
                    this.offerCopies(found, drag.to, start => {
                        const from = start + drag.from - found.block.startTick;
                        const to = start + drag.to - found.block.startTick;
                        if (from > 0 && this.startsChord(from)) [, this.ticks, this.splits] = this.dragResult(this.ticks, this.splits, from, to);
                    });
                },
                // A chord starting at `from` dragged to `to`, always inside its block: between its neighbours only where
                // it starts changes; passed over another chord, it is taken out from where it was and put where it was
                // dropped, as a new chord. Returns [to, ticks, splits].
                dragResult(ticks, splits, from, to) {
                    const [blockStart, blockEnd] = this.blockBounds(from);
                    to = Math.max(blockStart + 1, Math.min(blockEnd - 1, to));
                    let before = from - 1;
                    while (before > blockStart && ticks[before - 1] === ticks[from - 1] && !splits.includes(before)) before--;
                    if (to > before && to < this.chordEnd(ticks, splits, from)) return [to, ...this.movedChange(ticks, from, to, splits)];
                    const chord = ticks[from];
                    const [without, withoutSplits] = this.withoutChord(ticks, splits, from);
                    return [to, ...this.withChord(without, withoutSplits, to, chord)];
                },
                // The first chord of a block moved to `to`, within the block's first bar: moved later, the block starts
                // there and the beats left behind keep the chord before (the previous block's) or, in the song's first
                // block, stay empty; moved earlier, the chord before gets shorter. The song's other chords keep their
                // time. Returns [to, ticks, splits, blocks].
                movedBlockStart(ticks, splits, blocks, from, to) {
                    const size = this.perBar * 2;
                    const timed = blocks.filter(block => !isMarkerData(block.data)).sort((a, b) => a.startTick - b.startTick);
                    const index = timed.findIndex(block => block.startTick === from);
                    const block = timed[index];
                    const previous = timed[index - 1];
                    const barStart = Math.floor(from / size) * size;
                    let end = from + 1;
                    while (end < ticks.length && ticks[end] === ticks[from] && !splits.includes(end) && !timed.some(item => item.startTick === end)) end++;
                    let low = barStart;
                    if (previous) {
                        // The chord before keeps at least half a beat.
                        let chordStart = from - 1;
                        while (chordStart > previous.startTick && ticks[chordStart - 1] === ticks[from - 1] && !splits.includes(chordStart)) chordStart--;
                        low = Math.max(barStart, chordStart + 1);
                    } else {
                        // Song's first block: only over empty beats.
                        while (low < from && ticks[low] !== '') low++;
                    }
                    to = Math.max(low, Math.min(to, end - 1, barStart + size - 1));
                    if (to === from) return [from, ticks, splits, blocks];
                    const chord = ticks[from];
                    const filler = previous ? ticks[from - 1] : '';
                    const next = [...ticks];
                    for (let t = Math.min(from, to); t < Math.max(from, to); t++) next[t] = to > from ? filler : chord;
                    let nextSplits = splits.filter(tick => tick < Math.min(from, to) || tick > Math.max(from, to));
                    // The same chord on both sides of the block start stays two chords.
                    if (to > 0 && next[to - 1] === next[to]) nextSplits = [...nextSplits, to];
                    nextSplits = nextSplits.filter((tick, i, list) => list.indexOf(tick) === i && next[tick] === next[tick - 1]);
                    const nextBlocks = blocks.map(item => {
                        if (item.id !== block.id) return item;
                        // The song's first chord keeps its own time when it moves (the bars stay where they are).
                        const data = previous ? item.data : { ...item.data, start: formatStart(Math.max(0, this.blockStart(item) + (to - from) * this.halfBeat)) };
                        return { ...item, startTick: to, data };
                    });
                    return [to, next, nextSplits, nextBlocks];
                },
                // [start, end) of the block of `tick`.
                blockBounds(tick) {
                    const list = this.timedBlocks();
                    const block = this.blockOfTick(tick);
                    const next = block ? list[list.indexOf(block) + 1] : null;
                    return [block?.startTick ?? 0, next ? next.startTick : this.ticks.length];
                },
                // Where the chord starting at `from` ends: at the next chord, inside the block.
                chordEnd(ticks, splits, from) {
                    const blockEnd = this.blockBounds(from)[1];
                    let end = from + 1;
                    while (end < blockEnd && ticks[end] === ticks[from] && !splits.includes(end)) end++;
                    return end;
                },
                // The chord starting at `from` taken out (all its beats): the chord before goes on over them. A chord
                // after it equal to the one before stays a chord of its own. Returns [ticks, splits].
                withoutChord(ticks, splits, from) {
                    if (splits.includes(from)) return [ticks, splits.filter(tick => tick !== from)];
                    const end = this.chordEnd(ticks, splits, from);
                    const before = ticks[from - 1];
                    const out = [...ticks];
                    for (let t = from; t < end; t++) out[t] = before;
                    const outSplits = [...splits];
                    if (end < this.blockBounds(from)[1] && ticks[end] === before && !outSplits.includes(end)) outSplits.push(end);
                    return [out, outSplits];
                },
                // A new chord at `at`, as when typed: until the next chord or the end of the bar, inside the block; next
                // to the same chord it stays a chord of its own. Returns [ticks, splits].
                withChord(ticks, splits, at, chord) {
                    const [blockStart, blockEnd] = this.blockBounds(at);
                    const end = Math.min((Math.floor(at / (this.perBar * 2)) + 1) * this.perBar * 2, blockEnd);
                    const old = ticks[at];
                    const out = [...ticks];
                    let t = at;
                    for (; t < end && (t === at || (ticks[t] === old && !splits.includes(t))); t++) out[t] = chord;
                    const outSplits = splits.filter(tick => tick !== at);
                    if (at > blockStart && out[at - 1] === chord) outSplits.push(at);
                    if (t < blockEnd && out[t] === chord && !outSplits.includes(t)) outSplits.push(t);
                    return [out, outSplits];
                },
                // The chord starting at `from` starts at `to` instead: earlier, it takes over the end of the chord
                // before; later, the chord before goes on until there. Returns [ticks, splits].
                movedChange(ticks, from, to, splits = this.splits) {
                    const moved = [...ticks];
                    if (to < from) for (let t = to; t < from; t++) moved[t] = ticks[from];
                    else for (let t = from; t < to; t++) moved[t] = ticks[from - 1];
                    return [moved, splits.includes(from) ? [...splits.filter(tick => tick !== from), to] : splits];
                },
                // Blocks like `block`: the same name and the same length (a change goes to the same place in each one).
                copiesOf(block) {
                    const list = this.timedBlocks();
                    const length = (item) => {
                        const next = list[list.indexOf(item) + 1];
                        return (next ? next.startTick : this.ticks.length) - item.startTick;
                    };
                    const own = length(block);
                    return list.filter(item => item !== block && item.data.section === block.data.section && length(item) === own);
                },
                // The chord starting at `tick` goes: the chord before goes on over its beats.
                removeStart(tick) {
                    [this.ticks, this.splits] = this.withoutChord(this.ticks, this.splits, tick);
                    this.revision++;
                },
                // ---- The same change in the equal blocks, asked right after a change ----
                copyOffer: null,
                // Called before a change at `tick`: the equal blocks as they are now, to be offered afterwards.
                copiesBefore(tick) {
                    const block = this.blockOfTick(tick);
                    if (!block || this.versionId) return null;
                    const copies = this.copiesOf(block);
                    const list = this.timedBlocks();
                    const end = list[list.indexOf(block) + 1]?.startTick ?? this.ticks.length;
                    return copies.length ? { block, end, copies: copies.map(copy => copy.startTick), ids: copies.map(copy => copy.id), name: this.blockName(block) } : null;
                },
                // Asks next to the beat at `tick`; `change(start)` does the change in the copy starting at `start`.
                offerCopies(found, tick, change) {
                    if (!found) return;
                    const size = this.perBar * 2;
                    this.askCopies(found, change, () => document.querySelector(`[data-bar="${Math.floor(tick / size)}"] .beat[data-beat="${(tick % size) >> 1}"]`));
                },
                // Ticks [from, to) of a block changed (pasted, restored): the same chords, at the same place, in its
                // equal blocks when asked. Only when they are all inside the block.
                offerRegion(found, from, to) {
                    if (!found || from < found.block.startTick || to > found.end) return;
                    const ticks = this.ticks.slice(from, to);
                    const splits = this.splits.filter(tick => tick >= from && tick < to).map(tick => tick - from);
                    const offset = from - found.block.startTick;
                    this.offerCopies(found, from, start => {
                        const at = start + offset;
                        ticks.forEach((chord, i) => { this.ticks[at + i] = chord; });
                        this.splits = [...this.splits.filter(tick => tick < at || tick >= at + ticks.length), ...splits.map(tick => tick + at)];
                    });
                },
                // A bar added or taken out of a block: the same in its equal blocks when asked (found by id, as each
                // change moves the blocks after it).
                offerBarCopies(found, tick, change) {
                    if (!found) return;
                    const offset = tick - found.block.startTick;
                    this.offerCopies({ ...found, copies: found.ids }, tick, id => {
                        const copy = this.blocks.find(block => block.id === id);
                        if (copy) change(copy.startTick + offset);
                    });
                },
                // The question next to `anchor()`; "In all of them" calls `change` for each copy.
                askCopies(found, change, anchor) {
                    // Taken now: the element this was called from (the box typed in) may be gone in a moment.
                    const popover = this.ref('copyOffer');
                    const button = this.ref('copyOfferAll');
                    this.copyOffer = { ...found, change, at: performance.now() };
                    this.$nextTick(() => {
                        const element = anchor();
                        if (element && popover) this.place(popover, element);
                        setTimeout(() => button?.focus(), 30);
                    });
                },
                applyToCopies() {
                    const offer = this.copyOffer;
                    this.copyOffer = null;
                    if (!offer) return;
                    this.remember();
                    offer.copies.forEach(start => offer.change(start));
                    this.revision++;
                },
                // A chord typed at a tick goes on until the chord changes (at most to the end of its bar or block).
                setFrom(tick, chord, remember = true) {
                    if (remember) this.remember();
                    const old = this.ticks[tick];
                    // To the end of the bar at most, and never into the next block.
                    const list = this.timedBlocks();
                    const block = this.blockOfTick(tick);
                    const next = block ? list[list.indexOf(block) + 1] : null;
                    const end = Math.min((Math.floor(tick / (this.perBar * 2)) + 1) * this.perBar * 2, next ? next.startTick : Infinity);
                    for (let t = tick; t < Math.min(end, this.ticks.length) && (t === tick || (this.ticks[t] === old && !this.splits.includes(t))); t++) this.ticks[t] = chord;
                    this.revision++;
                },
                // A chord name, or "?" (a chord not known yet).
                isChordName(typed) { return typed === '?' || /^[A-G][#b]?[^\s\[\]|]*$/.test(typed); },
                // A chord typed at `tick` (in the key on screen; stored in the song's key); the equal blocks are offered.
                typeChordAt(tick, typed) {
                    if (tick < this.leadTicks) { this.notice = @js(__('Before the first chord: drag the first chord to move where it starts.')); return; }
                    const chord = this.key && this.savedKey && this.key !== this.savedKey ? ChordTransposer.transposeChord(typed, this.key, this.savedKey) : typed;
                    const found = this.copiesBefore(tick);
                    this.setFrom(tick, chord);
                    this.offerCopies(found, tick, start => this.setFrom(start + tick - found.block.startTick, chord, false));
                },
                // ---- Typing a chord right in a beat of the grid ----
                inline: null,
                // ---- The chord editor of a beat, on touch screens ----
                editor: null,
                editorError: '',
                touch: false,
                openBeatEditor(bar, beat, cell, half = null) {
                    const tick = (bar * this.perBar + beat) * 2;
                    this.inline = null;
                    this.barMenu = null;
                    this.copyOffer = null;
                    this.editor = { bar, beat, half: half ?? (this.ticks[tick + 1] !== this.ticks[tick] ? 1 : 0), text: '' };
                    this.place(this.ref('editor'), cell);
                    this.fillEditor();
                    this.$nextTick(() => { this.ref('editorChord').focus(); this.ref('editorChord').select(); });
                },
                editorTick() { return (this.editor.bar * this.perBar + this.editor.beat) * 2 + this.editor.half; },
                fillEditor() {
                    this.editorError = '';
                    this.ref('editorChord').value = this.transposeName(this.ticks[this.editorTick()]) || '';
                    // The chord shown at first is not a filter: every chord is offered until something is typed.
                    if (this.editor) this.editor.text = '';
                },
                editorBlockName() { return this.editor ? this.blockName(this.blockOfTick(this.editorTick()) ?? { data: {} }) : ''; },
                editorChordNow() { return this.editor ? this.transposeName(this.ticks[this.editorTick()]) || null : null; },
                // The song's chords (in the order they first come, as shown in the current key), or the chords of the
                // key that the song does not use yet; filtered by what is typed.
                pickerChords(kind) {
                    const song = [];
                    this.ticks.forEach(chord => {
                        const name = chord && chord !== '?' ? this.transposeName(chord) : null;
                        if (name && !song.includes(name)) song.push(name);
                    });
                    let list = song;
                    if (kind === 'key') {
                        if (!this.key || typeof ChordTransposer === 'undefined') return [];
                        const minor = /m$/.test(this.key);
                        const base = minor ? ['Am', 'C', 'Dm', 'Em', 'E', 'F', 'G'] : ['C', 'Dm', 'Em', 'F', 'G', 'Am'];
                        list = base.map(chord => ChordTransposer.transposeChord(chord, minor ? 'Am' : 'C', this.key)).filter(chord => !song.includes(chord));
                    }
                    const typed = (this.editor?.text || '').trim().toLowerCase();
                    return typed ? list.filter(chord => chord.toLowerCase().startsWith(typed)) : list;
                },
                pickChord(chord) {
                    this.ref('editorChord').value = chord;
                    this.applyChord();
                },
                canRemoveChange() { return !!this.editor && this.isChangeAt(this.editorTick()); },
                // As typed in the cell: a chord that starts there is renamed, otherwise a new one starts.
                applyChord() {
                    const typed = this.ref('editorChord').value.trim();
                    if (!this.isChordName(typed)) {
                        this.editorError = @js(__('Use a chord name, e.g. G, Am7, C/E, D4.'));
                        return;
                    }
                    const tick = this.editorTick();
                    this.editor = null;
                    if (this.chordStartsAt(tick)) {
                        if (typed !== this.transposeName(this.ticks[tick])) this.renameChordAt(tick, typed);
                    } else {
                        this.typeChordAt(tick, typed);
                    }
                },
                removeChange() {
                    const tick = this.editorTick();
                    this.editor = null;
                    this.removeChangeAt(tick);
                },
                // A chord starts on this tick (a change, a chord repeated right after itself, or the start of a block).
                chordStartsAt(tick) {
                    return tick === 0 || this.startsChord(tick) || this.timedBlocks().some(block => block.startTick === tick);
                },
                // `existing`: a chord starts there (its name comes selected; what is typed renames it).
                openInline(tick, text, existing = this.chordStartsAt(tick)) {
                    this.copyOffer = null;
                    this.editor = null;
                    this.inline = { tick, text, original: text, existing, select: existing && text === this.transposeName(this.ticks[tick]), invalid: false };
                },
                // Enter (or leaving the box with a chord name in it) applies. On a chord that is there, the new name
                // replaces it (all its beats) and an emptied box removes it; on an empty beat a new chord starts.
                commitInline(leaving = false) {
                    const inline = this.inline;
                    if (!inline) return;
                    const typed = inline.text.trim();
                    if (typed === inline.original && inline.existing) { this.inline = null; return; }
                    if (typed === '') {
                        this.inline = null;
                        if (inline.existing && !leaving) this.removeChangeAt(inline.tick);
                        return;
                    }
                    if (!this.isChordName(typed)) {
                        if (leaving) this.inline = null;
                        else inline.invalid = true;
                        return;
                    }
                    this.inline = null;
                    if (inline.existing) this.renameChordAt(inline.tick, typed);
                    else this.typeChordAt(inline.tick, typed);
                },
                // The chord starting at `tick` gets another name, over all its beats; the equal blocks are offered.
                renameChordAt(tick, typed) {
                    const chord = this.key && this.savedKey && this.key !== this.savedKey ? ChordTransposer.transposeChord(typed, this.key, this.savedKey) : typed;
                    const found = this.copiesBefore(tick);
                    this.remember();
                    [this.ticks, this.splits] = this.withRenamed(this.ticks, this.splits, tick, chord);
                    this.revision++;
                    this.offerCopies(found, tick, start => {
                        const at = start + tick - found.block.startTick;
                        if (this.chordStartsAt(at)) [this.ticks, this.splits] = this.withRenamed(this.ticks, this.splits, at, chord);
                    });
                },
                // [ticks, splits] with the chord starting at `at` renamed; next to the same chord it stays a chord of its own.
                withRenamed(ticks, splits, at, chord) {
                    const [blockStart, blockEnd] = this.blockBounds(at);
                    const end = this.chordEnd(ticks, splits, at);
                    const out = [...ticks];
                    for (let t = at; t < end; t++) out[t] = chord;
                    const outSplits = [...splits];
                    if (at > blockStart && out[at - 1] === chord && !outSplits.includes(at)) outSplits.push(at);
                    if (end < blockEnd && out[end] === chord && !outSplits.includes(end)) outSplits.push(end);
                    return [out, outSplits];
                },
                removeChangeAt(tick) {
                    if (!this.isChangeAt(tick)) return;
                    const found = this.copiesBefore(tick);
                    this.remember();
                    this.removeStart(tick);
                    this.offerCopies(found, tick, start => {
                        const at = start + tick - found.block.startTick;
                        if (at > 0 && this.startsChord(at)) this.removeStart(at);
                    });
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
                menuBarsLabel() { if (!this.barMenu) return ''; const { from, to } = this.barsFor(this.barMenu.bar); return this.barsLabel(from, to); },
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
                    const found = this.copiesBefore(from);
                    this.remember();
                    pasted.forEach((chord, i) => { this.ticks[from + i] = chord; });
                    this.revision++;
                    this.offerRegion(found, from, to);
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
                    const found = this.copiesBefore(from * size);
                    this.remember();
                    const end = Math.min((to + 1) * size, this.ticks.length);
                    for (let tick = from * size; tick < end; tick++) this.ticks[tick] = this.originalTicks[tick];
                    this.splits = [...this.splits.filter(tick => tick < from * size || tick >= end), ...this.originalSplits.filter(tick => tick >= from * size && tick < end)];
                    this.revision++;
                    this.offerRegion(found, from * size, end);
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
                        this.loopCooldown = performance.now() + (audio || this.clickOnly ? 250 : 1000);
                        this.playFromTick(this.loop.from * size);
                    }
                },
                // Metronome: the clicks of the next moments are scheduled ahead on the audio clock, so each one
                // sounds right on its beat (clicking when a frame notices the beat passed comes late).
                clickUntil: null,
                scheduleClicks() {
                    if (!(this.metronome || this.clickOnly) || !this.isPlaying()) { this.clickUntil = null; return; }
                    let context;
                    try {
                        context = this.clickContext = this.clickContext || new AudioContext();
                        if (context.state === 'suspended') context.resume();
                    } catch (e) { return; }
                    const now = this.clock;
                    const rate = this.clickOnly ? 1 : audio ? (audio.playbackRate || 1) : (this.video?.speed || 1);
                    // Started, or jumped (seek, repeat): scheduling starts again from here.
                    if (this.clickUntil === null || now < this.clickUntil - 0.25 || now > this.clickUntil + 0.25) this.clickUntil = now - 0.005;
                    let until = now + 0.15;
                    if (this.loop) until = Math.min(until, this.timeOfTick(Math.min((this.loop.to + 1) * this.perBar * 2, this.ticks.length)) - 0.001);
                    for (let tick = Math.max(0, this.tickAt(this.clickUntil)); tick < this.ticks.length; tick++) {
                        const time = this.timeOfTick(tick);
                        if (time > until) break;
                        // Each beat, and each "and" too when the click is doubled.
                        if ((tick % 2 && !this.clickDouble) || time <= this.clickUntil) continue;
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
                // Enter: the chord of the selected beat (or of its "and"), typed again in the cell.
                editCurrentBeat() {
                    if (this.currentTick < 0) return;
                    const tick = this.currentTick;
                    const onBeat = tick - (tick % 2);
                    this.editTick(tick % 2 || this.chordStartsAt(onBeat) || !this.chordStartsAt(onBeat + 1) ? tick : onBeat + 1);
                },

                // ---- Lines of the map and passing chords (the bars do not change) ----
                // The chords of a block, from its ticks (a chord held over a bar line counts once).
                blockChords(block) {
                    const timed = this.timedBlocks();
                    const next = timed[timed.indexOf(block) + 1];
                    const chords = [];
                    this.ticks.slice(block.startTick, next ? next.startTick : this.ticks.length).forEach((chord, i) => {
                        if (chord && (chord !== chords.at(-1) || this.splits.includes(block.startTick + i))) chords.push(chord);
                    });
                    return chords;
                },
                // The tick where each chord of blockChords(block) starts.
                blockChordTicks(block) {
                    const timed = this.timedBlocks();
                    const next = timed[timed.indexOf(block) + 1];
                    const ticks = [];
                    let last = null;
                    for (let tick = block.startTick; tick < (next ? next.startTick : this.ticks.length); tick++) {
                        const chord = this.ticks[tick];
                        if (chord && (chord !== last || this.splits.includes(tick))) { ticks.push(tick); last = chord; }
                    }
                    return ticks;
                },
                // Line breaks and passing chords of a block, valid while it has the chords they were set for.
                lineState(block) {
                    const chords = this.blockChords(block);
                    const valid = JSON.stringify(chords) === JSON.stringify(block.data.lineChords || null);
                    return { chords, valid, breaks: valid ? (block.data.breaks || []) : [], passing: valid ? (block.data.passing || []) : [] };
                },
                // The line breaks set before (kept as they were when the block's chords did not change).
                withBreaks(chords, breaks) { return chords.flatMap((chord, i) => (breaks.includes(i) && i > 0 ? ['|', chord] : [chord])); },
                // Starts marking the passing chords of a block whose chords changed: none set yet.
                linesFor(block) {
                    const state = this.lineState(block);
                    if (!state.valid) block.data = { ...block.data, lineChords: state.chords, breaks: [], passing: [] };
                    return state;
                },
                // ---- Passing chords: marked in the bar menu, underlined in the bars and on the public page ----
                passingCache: { key: null, value: new Set() },
                // Ticks where a passing chord starts.
                get passingTicks() {
                    const key = `${this.revision}:${this.ticks.length}:${this.splits.join(',')}:${this.blocks.map(block => `${block.id}@${block.startTick}:${(block.data.passing || []).join('.')}`).join(',')}`;
                    if (this.passingCache.key === key) return this.passingCache.value;
                    const ticks = new Set();
                    this.timedBlocks().forEach(block => {
                        const state = this.lineState(block);
                        if (!state.passing.length) return;
                        const starts = this.blockChordTicks(block);
                        state.passing.forEach(position => { if (starts[position] !== undefined) ticks.add(starts[position]); });
                    });
                    this.passingCache = { key, value: ticks };
                    return ticks;
                },
                // The chords starting in a bar, for its menu.
                barChords(bar) {
                    const size = this.perBar * 2;
                    const chords = [];
                    for (let tick = bar * size; tick < Math.min((bar + 1) * size, this.ticks.length); tick++) {
                        if (this.ticks[tick] && this.startsChord(tick)) chords.push({ tick, name: this.ticks[tick], passing: this.passingTicks.has(tick) });
                    }
                    return chords;
                },
                // A chord marked or unmarked as passing, in its block and in the same place of the blocks repeated with it.
                togglePassing(tick) {
                    const block = this.blockOfTick(tick);
                    if (!block) return;
                    const position = this.blockChordTicks(block).indexOf(tick);
                    if (position < 0) return;
                    this.forGroup(block, target => {
                        this.linesFor(target);
                        const passing = target.data.passing || [];
                        target.data = { ...target.data, passing: passing.includes(position) ? passing.filter(p => p !== position) : [...passing, position].sort((a, b) => a - b) };
                    });
                    this.revision++;
                },

                // ---- Blocks ----
                // A new block at the end of the song: `count` bars with a "?" each (chords to be written by ear), from the
                // end of the song. Returns whether it was added.
                addBlockAtEnd(typed, count) {
                    const name = String(typed ?? '').trim().toUpperCase().replace(/\s+/g, ' ');
                    const bars = Math.max(1, Math.min(64, Math.round(Number(count) || 0)));
                    if (!name || this.versionId) return false;
                    const size = this.perBar * 2;
                    this.remember();
                    const startTick = this.ticks.length;
                    for (let bar = 0; bar < bars; bar++) {
                        // Each bar its own "?" (the same chord again would join the bars into one chord).
                        if (bar > 0 || this.ticks[startTick - 1] === '?') this.splits.push(this.ticks.length);
                        for (let tick = 0; tick < size; tick++) this.ticks.push('?');
                    }
                    this.blocks.push({ id: nextId++, startTick, data: { section: name, start: formatStart(this.timeOfTick(startTick)) } });
                    this.originalTicks = [];
                    this.revision++;
                    this.notice = @js(__(':name added at the end with :n bars: write their chords in place of the "?".')).replace(':name', name).replace(':n', bars);
                    this.$nextTick(() => document.querySelector(`[data-group-block="${this.blocks[this.blocks.length - 1].id}"]`)?.scrollIntoView({ block: 'center', behavior: 'smooth' }));
                    return true;
                },
                // Where a block's ticks end (where the next block starts, or the song's end), and how many bars it has.
                blockEnd(block) {
                    const timed = this.timedBlocks();
                    return timed[timed.indexOf(block) + 1]?.startTick ?? this.ticks.length;
                },
                blockBarCount(block) { return Math.max(1, Math.ceil((this.blockEnd(block) - block.startTick) / (this.perBar * 2))); },
                // The block made `value` bars long: bars with a "?" each added at its end, or its last bars taken out
                // (one bar at least). The blocks after it follow.
                setBlockBars(block, value) {
                    if (!block || this.versionId) return;
                    const wanted = Math.max(1, Math.min(128, Math.round(Number(value) || 0)));
                    const current = this.blockBarCount(block);
                    if (!wanted || wanted === current) return;
                    const size = this.perBar * 2;
                    const end = this.blockEnd(block);
                    this.remember();
                    if (wanted > current) {
                        const add = (wanted - current) * size;
                        const added = [];
                        // Each new bar its own "?" (the same chord again would join them into one chord).
                        for (let bar = 0; bar < wanted - current; bar++) {
                            if (bar > 0 || this.ticks[end - 1] === '?') added.push(end + bar * size);
                        }
                        this.ticks = [...this.ticks.slice(0, end), ...new Array(add).fill('?'), ...this.ticks.slice(end)];
                        this.splits = [...this.splits.map(tick => (tick >= end ? tick + add : tick)), ...added];
                        if (this.ticks[end + add] === '?' && !this.splits.includes(end + add)) this.splits.push(end + add);
                        this.blocks = this.blocks.map(item => (item !== block && item.startTick >= end ? { ...item, startTick: item.startTick + add } : item));
                        this.originalTicks = [];
                    } else {
                        this.removeTicks(Math.max(block.startTick + 2, end - (current - wanted) * size), end);
                    }
                    this.revision++;
                    this.notice = @js(__(':name now has :n bars.')).replace(':name', this.blockName(block)).replace(':n', this.blockBarCount(block));
                },
                // ---- The menu of a block ----
                blockMenu: null,
                openBlockMenu(block, element) {
                    if (!block || this.isMarker(block)) return;
                    this.barMenu = null;
                    this.inline = null;
                    this.editor = null;
                    this.blockMenu = { id: block.id };
                    this.place(this.ref('blockMenu'), element);
                },
                menuBlock() { return this.blockMenu ? this.blocks.find(block => block.id === this.blockMenu.id) ?? null : null; },
                renameMenuBlock(typed) {
                    const block = this.menuBlock();
                    const name = String(typed ?? '').trim().toUpperCase().replace(/\s+/g, ' ');
                    this.blockMenu = null;
                    if (!block || !name || name === this.blockName(block)) return;
                    this.remember();
                    block.data = { ...block.data, section: name };
                    this.revision++;
                },
                // Name, times played in a row and place of the menu's block (safe while the menu closes).
                menuBlockInfo() {
                    const block = this.menuBlock();
                    if (!block) return { name: '', repeats: 1, index: -1, bars: 1, from: 0, to: 0, number: 0, total: 0 };
                    const size = this.perBar * 2;
                    const timed = this.timedBlocks();
                    return {
                        name: this.blockName(block), repeats: this.groupOf(block).length, index: timed.indexOf(block), bars: this.blockBarCount(block),
                        from: Math.floor(block.startTick / size) + 1, to: Math.ceil(this.blockEnd(block) / size), number: timed.indexOf(block) + 1, total: timed.length,
                    };
                },
                // What the start time of the menu's block follows.
                menuTimeHelp() {
                    const block = this.menuBlock();
                    if (!block) return '';
                    if (this.timedBlocks().indexOf(block) === 0) return @js(__('Where the song starts in the recording (bar 1); the other blocks follow it.'));
                    if (!this.freeTiming) return @js(__('Follows the BPM. Changing it turns on free timing for the song.'));
                    return block.data.free_start !== undefined
                        ? @js(__('Marked time: the blocks after it count from here.'))
                        : @js(__('Right after the block before, at the BPM.'));
                },
                // A block's start time typed in (m:ss.cc or seconds), or null: back to right after the block before it.
                // The first block's is the song's start; another one's is a free-timing mark (turned on if it is off).
                setBlockTime(block, value) {
                    if (!block || this.versionId) return;
                    const timed = this.timedBlocks();
                    const i = timed.indexOf(block);
                    if (i < 0) return;
                    if (value === null) {
                        if (i === 0 || block.data.free_start === undefined) return;
                        this.remember();
                        const data = { ...block.data };
                        delete data.free_start;
                        block.data = data;
                        this.revision++;
                        return;
                    }
                    const seconds = typeof value === 'number' ? Math.round(value * 100) / 100 : toSeconds(String(value).trim().replace(',', '.'));
                    if (seconds === null || seconds < 0) {
                        this.notice = @js(__('Type the time as m:ss.cc, e.g. 1:23.50.'));
                        return;
                    }
                    if (i === 0) {
                        this.remember();
                        block.data = { ...block.data, start: formatFineStart(seconds) };
                    } else {
                        if (seconds <= this.blockStart(timed[i - 1]) + 0.05) {
                            this.notice = @js(__(':name has to start after the block before it (:time).')).replace(':name', this.blockName(block)).replace(':time', this.formatClock(this.blockStart(timed[i - 1])));
                            this.revision++;
                            return;
                        }
                        const turnedOn = !this.freeTiming;
                        // Turning free timing on is remembered too, so one undo takes both back.
                        if (turnedOn) this.toggleFreeTiming();
                        else this.remember();
                        const starts = this.freeStarts(timed);
                        const expected = starts[i - 1] + (block.startTick - timed[i - 1].startTick) * this.halfBeat;
                        const data = { ...block.data };
                        if (Math.abs(seconds - expected) < 0.005) delete data.free_start;
                        else data.free_start = formatFineStart(seconds);
                        block.data = data;
                        if (turnedOn) {
                            this.revision++;
                            this.notice = @js(__(':name starts at :time (free timing turned on).')).replace(':name', this.blockName(block)).replace(':time', this.formatClock(seconds));
                            return;
                        }
                    }
                    this.revision++;
                    this.notice = @js(__(':name starts at :time.')).replace(':name', this.blockName(block)).replace(':time', this.formatClock(seconds));
                },
                // ---- Dragging a block by its head: while dragging, the bars hide and only the heads show, so the order is
                // easy to see; dropped before or after another block ----
                groupDrag: null,
                startGroupDrag(event, block) {
                    if (this.versionId) return;
                    event.preventDefault();
                    this.blockMenu = null;
                    this.barMenu = null;
                    this.groupDrag = { id: block.id, x: event.clientX, y: event.clientY, moved: false, target: null, before: true };
                },
                onGroupDragMove(event) {
                    const drag = this.groupDrag;
                    if (!drag) return;
                    if (!drag.moved && Math.hypot(event.clientX - drag.x, event.clientY - drag.y) < 6) return;
                    if (!drag.moved) {
                        // The bars fold away: the dragged head is brought back under the pointer.
                        drag.moved = true;
                        this.$nextTick(() => {
                            const element = document.querySelector(`[data-group-block="${drag.id}"]`);
                            if (element) window.scrollBy(0, element.getBoundingClientRect().top - (event.clientY - 12));
                        });
                        return;
                    }
                    const heads = [...document.querySelectorAll('[data-group-block]')].map(element => ({ id: Number(element.dataset.groupBlock), box: element.getBoundingClientRect() }));
                    const over = heads.find(item => event.clientY < item.box.bottom) ?? heads[heads.length - 1];
                    if (over) {
                        drag.target = over.id;
                        drag.before = event.clientY < over.box.top + over.box.height / 2;
                    }
                    if (event.clientY < 60) window.scrollBy(0, -12);
                    else if (event.clientY > window.innerHeight - 60) window.scrollBy(0, 12);
                },
                onGroupDragEnd() {
                    const drag = this.groupDrag;
                    this.groupDrag = null;
                    if (!drag?.moved || drag.target === null || drag.target === drag.id) return;
                    const block = this.blocks.find(item => item.id === drag.id);
                    const target = this.blocks.find(item => item.id === drag.target);
                    if (!block || !target) return;
                    // A grouped run moves (and is passed over) as a whole.
                    const ids = this.isCollapsed(block) ? this.groupOf(block) : [block.id];
                    if (ids.includes(target.id)) return;
                    const targetIds = this.isCollapsed(target) ? this.groupOf(target) : [target.id];
                    const anchor = drag.before ? targetIds[0] : targetIds[targetIds.length - 1];
                    this.reshapeBlocks(items => {
                        const moved = items.filter(item => ids.includes(item.block?.id));
                        const rest = items.filter(item => !ids.includes(item.block?.id));
                        const at = rest.findIndex(item => item.block?.id === anchor);
                        rest.splice(drag.before ? at : at + 1, 0, ...moved);
                        return rest;
                    });
                    if (block) this.notice = @js(__(':name moved.')).replace(':name', this.blockName(block));
                },

                startPress(bar, event) {
                    if (event.pointerType !== 'touch') return;
                    const target = event.currentTarget;
                    this.pressTimer = setTimeout(() => { this.pressTimer = null; this.pressOpened = true; this.openBarMenu(bar, target); }, 550);
                },
                endPress() { clearTimeout(this.pressTimer); },
                openBarMenu(bar, element) {
                    this.inline = null;
                    this.editor = null;
                    this.barMenu = { bar };
                    this.place(this.ref('barMenu'), element);
                    const block = this.blockAtBar(bar);
                    this.ref('blockName').value = block ? this.blockName(block) : '';
                    this.$nextTick(() => this.ref('blockName').focus());
                },
                barMenuTitle() {
                    if (!this.barMenu) return '';
                    const block = this.blockAtBar(this.barMenu.bar);
                    const offset = this.blockOffset(this.barMenu.bar);
                    const where = offset ? ` (${@js(__('beat'))} ${Math.floor(offset / 2) + 1}${offset % 2 ? ' e' : ''})` : '';
                    return `${@js(__('Bar'))} ${this.barMenu.bar + 1}` + (block ? ` · ${this.blockName(block)}${where}` : ` · ${@js(__('new block from here'))}`);
                },
                saveBlockName() {
                    const name = this.ref('blockName').value.trim().toUpperCase().replace(/\s+/g, ' ');
                    if (!name) return this.ref('blockName').focus();
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
                // ---- Bars added or removed: the chords stay the same (only how long they last changes), so the chord
                // sheet keeps them ----
                // A bar before or after bar `bar`: the chord sounding at its start (or at its end) lasts one bar longer.
                // A new bar before a block's first bar belongs to that block; after a block's last bar, to that block.
                insertBar(bar, after) {
                    const size = this.perBar * 2;
                    const at = Math.min((after ? bar + 1 : bar) * size, this.ticks.length);
                    if ((after ? this.ticks[at - 1] : this.ticks[at]) === undefined) return;
                    const found = this.copiesBefore(after ? at - 1 : at);
                    this.remember();
                    this.insertTicks(at, after);
                    this.revision++;
                    this.notice = @js(__('Bar :n added.')).replace(':n', (after ? bar + 2 : bar + 1));
                    this.offerBarCopies(found, at, tick => this.insertTicks(tick, after));
                },
                // The bar copied right after itself (its chords and where they change), in its block; the equal blocks
                // are offered the same.
                // The ticks of `count` bars from bar `bar` taken inside one block (the block playing on the bar's first
                // beat, or the song's first block), counted from the block's start: when they would go past the block's
                // end, its last bars instead, so a duplicate or a removal stays in that block and the next blocks only
                // move by whole bars.
                barsInBlock(bar, count = 1) {
                    const size = this.perBar * 2;
                    const block = this.blockOfTick(bar * size) ?? this.timedBlocks()[0];
                    if (!block) return { from: bar * size, to: Math.min((bar + count) * size, this.ticks.length) };
                    const timed = this.timedBlocks();
                    const end = timed[timed.indexOf(block) + 1]?.startTick ?? this.ticks.length;
                    // Bars counted from the block's start (a block starting on beat 3 has its bars from beat 3).
                    let from = block.startTick + Math.max(0, Math.floor((bar * size - block.startTick) / size)) * size;
                    let to = from + count * size;
                    if (to > end) {
                        to = end;
                        from = Math.max(block.startTick, end - count * size);
                    }
                    return { from, to };
                },
                duplicateBar(bar) {
                    // A block starting within the bar (the bar begins with the chord of the block before it): one more
                    // bar of the chord that goes on at the bar's end, in that block ("D / A /" -> "D / A /", "A / / /").
                    const size = this.perBar * 2;
                    const barEnd = Math.min((bar + 1) * size, this.ticks.length);
                    const starting = this.timedBlocks().find(block => block.startTick > bar * size && block.startTick < barEnd);
                    if (starting && this.ticks[barEnd - 1]) {
                        const found = this.copiesBefore(starting.startTick);
                        this.remember();
                        this.insertTicks(barEnd, true);
                        this.revision++;
                        this.notice = @js(__('Bar :n duplicated.')).replace(':n', bar + 1);
                        if (found) this.offerBarCopies(found, barEnd, tick => this.insertTicks(tick, true));
                        return;
                    }
                    const { from, to } = this.barsInBlock(bar);
                    if (from >= to) return;
                    const found = this.copiesBefore(from);
                    this.remember();
                    this.copyTicks(from, to);
                    this.revision++;
                    this.notice = @js(__('Bar :n duplicated.')).replace(':n', bar + 1);
                    if (found && to <= found.end) this.offerBarCopies(found, from, tick => this.copyTicks(tick, tick + (to - from)));
                },
                // Ticks [from, to) repeated right after themselves (belonging to the same block).
                copyTicks(from, to) {
                    const slice = this.ticks.slice(from, to);
                    const size = to - from;
                    const inside = this.splits.filter(tick => tick >= from && tick < to).map(tick => tick - from);
                    // A copy starting with the chord the bar ends on just makes that chord longer (as "insert after"),
                    // except a "?" (one per bar, each to be written on its own).
                    const own = slice[0] === '?' && this.ticks[to - 1] === '?' ? [to] : [];
                    this.ticks = [...this.ticks.slice(0, to), ...slice, ...this.ticks.slice(to)];
                    this.splits = [...this.splits.map(tick => (tick >= to ? tick + size : tick)), ...inside.map(tick => tick + to), ...own]
                        .filter((tick, i, list) => list.indexOf(tick) === i && this.ticks[tick] === this.ticks[tick - 1]);
                    this.blocks = this.blocks.map(block => (block.startTick >= to ? { ...block, startTick: block.startTick + size } : block));
                    this.originalTicks = [];
                },
                // A bar of the chord sounding there, at `at` (in the block before it when `after`, else in the one there).
                // Every chord twice as long (the map in half beats: each one after the empty beats of bar 1 doubled); the
                // blocks and the chords that repeat keep their places in the longer map. The BPM stays.
                doubleBars() {
                    if (this.versionId || !this.ticks.length) return;
                    const lead = this.leadTicks;
                    const place = (tick) => (tick <= lead ? tick : lead + (tick - lead) * 2);
                    this.remember();
                    this.ticks = this.ticks.flatMap((chord, tick) => (tick < lead ? [chord] : [chord, chord]));
                    this.splits = this.splits.map(place);
                    this.blocks = this.blocks.map(block => ({ ...block, startTick: place(block.startTick) }));
                    this.originalTicks = [];
                    this.selection = { anchor: null, from: null, to: null };
                    this.loop = null;
                    this.currentTick = this.currentTick < 0 ? this.currentTick : place(this.currentTick);
                    this.revision++;
                    this.notice = @js(__('Every bar doubled: remove the extra ones (right click on a bar). Undo goes back.'));
                },
                insertTicks(at, after) {
                    const size = this.perBar * 2;
                    const chord = after ? this.ticks[at - 1] : this.ticks[at];
                    if (chord === undefined) return;
                    this.ticks = [...this.ticks.slice(0, at), ...new Array(size).fill(chord), ...this.ticks.slice(at)];
                    const moves = (tick) => (after ? tick >= at : tick > at);
                    this.splits = this.splits.map(tick => (moves(tick) ? tick + size : tick));
                    this.blocks = this.blocks.map(block => (moves(block.startTick) ? { ...block, startTick: block.startTick + size } : block));
                    this.originalTicks = [];
                },
                // Bar `bar` taken out: the chords inside it go (the sheet loses them when saved); a block left without bars goes.
                // The bar, or the marked bars when it is one of them (or none is given).
                removeBar(bar) {
                    const { from: first, to: last } = this.barsFor(bar);
                    // Inside the block of the first bar (see barsInBlock).
                    const { from, to } = this.barsInBlock(first, last - first + 1);
                    if (this.ticks.length - (to - from) < 1) return;
                    const found = this.copiesBefore(from);
                    this.remember();
                    this.removeTicks(from, to);
                    this.selection = { anchor: first, from: null, to: null };
                    this.revision++;
                    this.notice = first === last ? @js(__('Bar :n removed.')).replace(':n', first + 1)
                        : @js(__('Bars :from–:to removed (:count).')).replace(':from', first + 1).replace(':to', last + 1).replace(':count', last - first + 1);
                    if (found && to <= found.end) this.offerBarCopies(found, from, tick => this.removeTicks(tick, tick + (to - from)));
                },
                removeTicks(from, to) {
                    if (from < 0 || to > this.ticks.length || this.ticks.length - (to - from) < 1) return;
                    this.ticks = [...this.ticks.slice(0, from), ...this.ticks.slice(to)];
                    this.splits = this.splits.filter(tick => tick < from || tick >= to).map(tick => (tick >= to ? tick - (to - from) : tick));
                    const moved = this.blocks.map(block => ({ ...block, startTick: block.startTick >= to ? block.startTick - (to - from) : Math.min(block.startTick, from) }));
                    // Two blocks starting at the same place: the one that lost all its bars goes (a "back to the start" mark stays).
                    this.blocks = moved.filter((block, i) => this.isMarker(block)
                        || !moved.some((other, k) => k > i && !this.isMarker(other) && other.startTick === block.startTick)
                        && block.startTick < this.ticks.length);
                    this.originalTicks = [];
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
                // Tapping the beat: the average of the last taps (from the third one; a pause of 2 s starts over).
                taps: [],
                tapTempo() {
                    const now = performance.now();
                    this.taps = [...this.taps.filter(at => now - at < 2000), now].slice(-6);
                    if (this.taps.length < 3) return;
                    const gaps = this.taps.slice(1).map((at, i) => at - this.taps[i]);
                    this.setBpm(60000 / (gaps.reduce((a, b) => a + b, 0) / gaps.length));
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
                        this.ticks.slice(block.startTick, next ? next.startTick : this.ticks.length).forEach((chord, i) => {
                            const split = this.splits.includes(block.startTick + i);
                            if (chords.length && (!chord || (chord === chords.at(-1) && !split))) durations[durations.length - 1] += 0.5;
                            else if (chord) { chords.push(chord); durations.push(0.5); }
                        });
                        // Line breaks and passing chords go back while the block has the chords they were set for.
                        const state = this.lineState(block);
                        if (state.valid && state.passing.length) data.passing = state.passing;
                        else delete data.passing;
                        const out = state.valid ? this.withBreaks(chords, state.breaks) : chords;
                        // The first chord on a later beat of bar 1: the empty beats before it.
                        const lead = block === timed[0] && block.startTick > 0 ? { lead_beats: block.startTick / 2 } : {};
                        return { ...data, chords: out.map(chord => (chord === '|' ? chord : transpose(chord))), durations, beats_per_bar: this.perBar, ...lead, ...(this.clickDouble ? { click_double: true } : {}), ...(this.freeTiming ? { free_timing: true } : {}), start: formatStart(this.blockStart(block)) };
                    });
                },
                // `options.reviewed`: saved as reviewed (true) or its review undone (false).
                async save(options = {}) {
                    if (this.saving) return;
                    if (this.versionId) return this.saveVersion();
                    this.saving = true;
                    this.saveState = @js(__('Saving…'));
                    try {
                        const response = await fetch(config.saveUrl, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ blocks: this.toBlocks(), updated_at: this.updatedAt, bpm: this.bpm, musical_key: this.key, chord_sheet: this.sheetToSave(),
                                ...(options.reviewed !== undefined ? { reviewed: options.reviewed } : {}) }),
                        });
                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(Object.values(result.errors || {})[0]?.[0] || result.message || @js(__('Something went wrong')));
                        this.sheetMoves = {};
                        this.sheetBase = null;
                        this.sheetBaseRevision++;
                        this.updatedAt = result.updated_at;
                        this.reviewedAt = result.reviewed_at ?? null;
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
                        this.originalSplits = [...this.splits];
                        this.baseStructure = this.toBlocks();
                        this.dirty = false;
                        this.saveError = false;
                        this.saveState = options.reviewed === true ? @js(__('Saved as reviewed ✓'))
                            : options.reviewed === false ? @js(__('Review undone.'))
                            : result.sheet_updated ? @js(__('Saved ✓ (chord sheet updated too)')) : @js(__('Saved ✓'));
                    } catch (error) {
                        this.saveError = true;
                        this.saveState = error.message;
                    } finally {
                        this.saving = false;
                    }
                },

                // The chord sheet sent with the map when chords were moved on it: as shown (the grid's chord changes and
                // the moves), in the key on screen like the map; null otherwise (the server follows the map's changes).
                sheetToSave() {
                    if (!this.sheetLinked || (!Object.keys(this.sheetMoves).length && !this.sheetBase && !this.previewSheet.changed)) return null;
                    const transpose = (chord) => (this.key && this.savedKey && this.key !== this.savedKey ? ChordTransposer.transposeChord(chord, this.savedKey, this.key) : chord);
                    return this.previewSheet.view.filter(section => !section.marker).map(section => ({
                        section: section.section ?? null,
                        label: section.label ?? null,
                        lines: section.lines.map(line => (line.units
                            ? lineText(line.units.map(unit => (unit.chord === null ? unit : { ...unit, chord: transpose(unit.chord) })))
                            : line.raw ?? '')),
                    }));
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
                    // Keys typed in a box or on a button of a popover belong to them.
                    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName) || event.target.closest?.('.popover')) return;
                    if (this.chordMove && event.key === 'Escape') { event.preventDefault(); this.chordMove = null; return; }
                    // The question open: Enter is "In all of them", Esc "Only this one".
                    if (this.copyOffer && (event.key === 'Enter' || event.key === 'Escape')) {
                        event.preventDefault();
                        if (event.key === 'Enter') this.applyToCopies();
                        else this.copyOffer = null;
                        return;
                    }
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
                    if (event.altKey || this.inline || this.editor || this.barMenu || this.sheetEdit) return;
                    if (event.code === 'Space' && this.canPlay) { event.preventDefault(); return this.togglePlay(); }
                    if (event.shiftKey && key === 'r') { event.preventDefault(); return this.restoreBars(); }
                    if (event.key === 'Escape') return this.clearSelection();
                    if (event.key === 'Enter' && this.mode === 'edit') { event.preventDefault(); return this.editCurrentBeat(); }
                    if (/^[a-g]$/i.test(event.key) && !event.shiftKey && this.mode === 'edit' && this.currentTick >= 0 && !this.isPlaying()) {
                        event.preventDefault();
                        return this.openInline(this.currentTick, event.key.toUpperCase());
                    }
                    if ((event.key === 'Backspace' || event.key === 'Delete') && this.mode === 'edit' && this.currentTick >= 0) {
                        event.preventDefault();
                        // The change selected: on the "and" when that was clicked, otherwise on the beat (or on its "and").
                        const tick = (this.currentTick % 2 ? [this.currentTick] : [this.currentTick, this.currentTick + 1]).find(at => this.isChangeAt(at));
                        return tick === undefined ? null : this.removeChangeAt(tick);
                    }
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
