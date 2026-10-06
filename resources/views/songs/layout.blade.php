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
        .layout-empty { font-size: .85rem; color: #9ca3af; }
        .layout-lyrics { margin-top: .5rem; }
        .layout-lyrics textarea { width: 100%; font-size: .85rem; line-height: 1.4; }
        .layout-link { font-size: .8rem; font-weight: 600; color: #4f46e5; background: none; border: 0; padding: 0; cursor: pointer; }
        .layout-notice { padding: .75rem 1rem; border: 1px solid #fcd34d; border-radius: .5rem; background: #fffbeb; font-size: .875rem; color: #92400e; }

        /* Chord sheet: each chord over the lyric it starts on; clicking a chord acts on the map. */
        .sheet-section + .sheet-section { margin-top: 1.25rem; }
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
                        @if ($song->reviewed_at)
                            <span class="ml-2 text-sm font-semibold" style="color: #15803d;"><i class="fa-solid fa-check"></i> {{ __('Reviewed on :date', ['date' => $song->reviewed_at->format('d/m/Y')]) }}</span>
                        @endif
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">{{ __('Choose where the chord map breaks lines and blocks. The lines are shown as in the public page; the chords themselves do not change here.') }}</p>
                </div>

                {{-- Review mark: submitted by a button of the options panel (forms cannot be nested) --}}
                <form id="review-form" method="POST" action="{{ route('songs.review.update', $song) }}" hidden>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="reviewed" value="{{ $song->reviewed_at ? 0 : 1 }}">
                </form>

                @if ($blocks === [] && $sheet === [])
                    <p class="mt-6 text-sm text-gray-500">{{ __('No structure available for this song.') }}</p>
                @else
                    <form method="POST" action="{{ route('songs.layout.update', $song) }}" class="mt-2 sm:mt-6 space-y-4"
                        x-data="songLayout(@js(['structure' => $blocks, 'sheet' => $sheet, 'structureUrl' => route('songs.chord-sheet.structure'), 'youtubeUrl' => $song->youtube_url]))"
                        @submit="submitting = true">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="structure" :value="output">
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
                            </span>
                            <button type="button" class="ws-btn" @click="undo()" :disabled="!history.length" title="{{ __('Undo') }}" aria-label="{{ __('Undo') }}"><i class="fa-solid fa-rotate-left"></i><span class="ws-label">{{ __('Undo') }}</span></button>
                            <span class="ws-spacer"></span>
                            @include('songs.partials.play-button', ['toggle' => 'toggleVideo()', 'time' => 'formatVideoTime(videoTime)', 'show' => 'videoId'])
                            <button type="submit" class="ws-btn ws-btn-primary" title="{{ __('Save') }}"><i class="fa-solid fa-floppy-disk"></i><span class="ws-label">{{ __('Save') }}</span></button>
                            <button type="button" class="ws-btn" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" title="{{ __('Options') }}" aria-label="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
                        </div>
                        <p class="ws-note ws-desktop" style="white-space: normal;" x-text="modeHelp"></p>

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
                                <div class="ws-panel-label"><i class="fa-solid fa-circle-check"></i> {{ __('Review') }}</div>
                                @if ($song->reviewed_at)
                                    <button type="submit" form="review-form" class="ws-btn" style="color: #15803d;" title="{{ __('Remove the review mark') }}"><i class="fa-solid fa-check"></i> {{ __('Reviewed on :date', ['date' => $song->reviewed_at->format('d/m/Y')]) }}</button>
                                @else
                                    <button type="submit" form="review-form" class="ws-btn"><i class="fa-regular fa-circle-check"></i> {{ __('Mark as reviewed') }}</button>
                                @endif
                            </div>
                            @include('songs.partials.video-options')
                            <div class="ws-panel-section">
                                <div class="ws-panel-label"><i class="fa-solid fa-circle-info"></i> {{ __('How it works') }}</div>
                                <p class="ws-panel-help">{{ __('Choose where the chord map breaks lines and blocks. The lines are shown as in the public page; the chords themselves do not change here.') }}</p>
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
                                            <div class="sheet-label" x-text="section.label" @click="selectFromSheet(section.lines[0], $event)"
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
                                    <div class="layout-block" :style="typeStyle(block.section)" :class="{ 'is-selected': selected === block.id, 'is-playing': playingBlockId === block.id }" :data-block-card="block.id" @click="selectFromMap(block, $event)">
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
                                            <span class="layout-tag" x-text="block.breaks ? @js(__('Lines set by hand')) : @js(__('Automatic lines'))"></span>
                                            <button type="button" class="layout-link" x-show="block.breaks" @click="automatic(b)">{{ __('Back to automatic') }}</button>
                                            <span style="flex: 1"></span>
                                            <button type="button" class="layout-btn" x-show="b > 0" @click="mergeWithPrevious(b)"><i class="fa-solid fa-arrow-up"></i> {{ __('Join to the block above') }}</button>
                                            <button type="button" class="layout-btn" style="color: #dc2626;" @click="removeBlock(b)" :title="@js(__('Remove block'))" :aria-label="@js(__('Remove block'))"><i class="fa-solid fa-trash"></i></button>
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
                                                :aria-label="@js(__('Lyrics'))"></textarea>
                                        </div>
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

            // Lines and colors of the whole map, recalculated only when the map changes.
            let mapCache = { key: null, value: null };

            const blocks = (Array.isArray(config.structure) ? config.structure : []).map(toBlock);
            const sheet = Array.isArray(config.sheet) ? config.sheet : [];
            const originalSheet = JSON.stringify(sheet);
            const hasSheet = parsedSheet(sheet).sequence.length > 0;

            return withYoutubeMini({
                blocks,
                // Chord sheet sections as stored (ChordPro lines); only removing a block changes them.
                sheet,
                hasSheet,
                view: hasSheet ? 'sheet' : 'chords',
                mode: 'line',
                history: [],
                // Bumped on undo, so the restored blocks are drawn again instead of reusing the elements.
                revision: 0,
                submitting: false,
                building: false,
                message: '',
                // Usual block names, offered while typing a name.
                commonNames: ['INTRO', 'VERSE', 'FIRST PART', 'SECOND PART', 'THIRD PART', 'PRE CHORUS', 'CHORUS', 'BRIDGE',
                    'INTERLUDE', 'SOLO', 'RIFF', 'TAG', 'FINAL', 'OUTRO'],
                // Block highlighted in the map and in the chord sheet (its id).
                selected: null,
                optionsOpen: false,
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
                get sheetSections() { return parsedSheet(this.syncedSheet).sections; },
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
                        line: @js(__('Click a chord to start a new line on it; click the first chord of a line to join it to the line above.')),
                        block: @js(__('Click a chord to start a new block on it; click the first chord of a block to join it to the block above.')),
                        passing: @js(__('Click a chord to mark it as a passing chord (underlined); click it again to unmark it. Passing chords do not count when looking for repetitions.')),
                    }[this.mode];
                },
                isPassing(block, position) { return (block.passing || []).includes(position); },
                // The first chord of the song does nothing; the first chord of a block only joins blocks (any chord
                // can be marked as passing).
                canClick(b, position) {
                    if (b === undefined) return false;
                    return position > 0 || this.mode === 'passing' || (this.mode === 'block' && b > 0);
                },
                chordTitle(b, position, isLineStart) {
                    if (!this.canClick(b, position)) return '';
                    if (this.mode === 'passing') return this.isPassing(this.blocks[b], position) ? @js(__('Not a passing chord')) : @js(__('Mark as passing chord'));
                    if (this.mode === 'block') return position === 0 ? @js(__('Join to the block above')) : @js(__('Start a new block here'));
                    return isLineStart ? @js(__('Join to the line above')) : @js(__('Start a new line here'));
                },
                clickPosition(b, position) {
                    if (!this.canClick(b, position)) return;
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
                    this.history.push(JSON.stringify({ blocks: this.blocks, sheet: this.sheet }));
                    if (this.history.length > 50) this.history.shift();
                    this.message = '';
                },
                undo() {
                    if (!this.history.length) return;
                    const state = JSON.parse(this.history.pop());
                    this.blocks = state.blocks;
                    this.sheet = state.sheet;
                    this.revision++;
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

                // Names by how the lyrics repeat (same rules as the chord sheet import): the most repeated lyrics are
                // the chorus; repeated lyrics always right before the chorus, the pre-chorus; other repeated lyrics first
                // heard after the chorus, the bridge; other lyrics are parts in order (same lyrics, same name); blocks
                // without lyrics are intro (first), final (last) or interlude.
                get suggestedNames() {
                    const keys = this.blocks.map(block => lyricText(block.lyrics).toLowerCase());
                    const counts = {};
                    keys.filter(Boolean).forEach(key => counts[key] = (counts[key] ?? 0) + 1);
                    const repeated = Object.keys(counts).filter(key => counts[key] > 1).sort((a, b) => counts[b] - counts[a]);
                    const chorus = repeated[0] ?? null;
                    const firstChorus = chorus === null ? -1 : keys.indexOf(chorus);
                    const preChorus = repeated.find(key => key !== chorus
                        && keys.every((item, i) => item !== key || keys[i + 1] === chorus)) ?? null;
                    const bridge = repeated.find(key => key !== chorus && key !== preChorus && keys.indexOf(key) > firstChorus) ?? null;
                    const partNames = ['FIRST PART', 'SECOND PART', 'THIRD PART'];
                    const parts = [];
                    const last = this.blocks.length - 1;

                    return keys.map((key, i) => {
                        if (key === '') return i === 0 ? 'INTRO' : (i === last ? 'FINAL' : 'INTERLUDE');
                        if (key === chorus) return 'CHORUS';
                        if (key === preChorus) return 'PRE CHORUS';
                        if (key === bridge) return 'BRIDGE';
                        if (!parts.includes(key)) parts.push(key);
                        const n = parts.indexOf(key);
                        return partNames[n] ?? `PART ${n + 1}`;
                    });
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

                // Removes the block from the map and, when the chord sheet has the same chords, its part of the chord sheet.
                removeBlock(b) {
                    const block = this.blocks[b];
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
                // The joined block keeps the lines both had, with a line break where they meet.
                mergeWithPrevious(b) {
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
