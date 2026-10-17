<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Chord sheet') }} · {{ $song->title }}</h2>
    </x-slot>

    <style>
        .sheet-page { --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; padding-bottom: 6rem;
            --type-intro: #0369a1; --type-verse: #047857; --type-prechorus: #b45309; --type-chorus: #be123c; --type-bridge: #6d28d9; --type-instrumental: #4338ca; --type-ending: #475569; --type-other: #111827; }
        .sheet-toolbar { position: sticky; top: 0; z-index: 30; display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; padding: .5rem 0; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .sheet-toolbar .spacer { flex: 1; }
        .sb { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .7rem; border: 1px solid #d1d5db; border-radius: .45rem; background: #fff; font-size: .85rem; font-weight: 600; color: #374151; cursor: pointer; white-space: nowrap; }
        .sb:hover { border-color: #a5b4fc; }
        .sb.primary { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .sb.green { color: #15803d; border-color: #86efac; }
        .sb:disabled { opacity: .45; cursor: default; }
        .sb.small { padding: .2rem .45rem; font-size: .75rem; font-weight: 600; }
        .sheet-hint { font-size: .8rem; color: #6b7280; margin: .5rem 0 1rem; }
        .sheet-state { font-size: .8rem; color: #6b7280; }
        .sheet-state.is-error { color: #dc2626; }

        .sheet-block { margin: 1rem 0; padding: .6rem .8rem .8rem; border: 1px solid #e5e7eb; border-left: 4px solid var(--type-color, #4f46e5); border-radius: .6rem; background: #fff; }
        .sheet-block.is-drop-before { box-shadow: 0 -3px 0 #4f46e5; }
        .sheet-block.is-drop-after { box-shadow: 0 3px 0 #4f46e5; }
        .sheet-block.is-dragging { opacity: .45; }
        /* Moving a block: every block shows only its head, so the order is easy to see */
        .sheet-page.is-moving-block .sheet-block { margin: .35rem 0; padding: .35rem .8rem; }
        .sheet-page.is-moving-block .sheet-block > :not(.block-head) { display: none; }
        .sheet-page.is-moving-block .block-head { margin-bottom: 0; }
        .sheet-page.is-moving-block, .sheet-page.is-moving-block * { cursor: grabbing !important; user-select: none; }
        .block-head { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-bottom: .4rem; }
        .block-grip { cursor: grab; color: #9ca3af; padding: .1rem .3rem; touch-action: none; }
        .block-name { font-size: .8rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--type-color, #4f46e5); background: none; border: 0; cursor: pointer; padding: .1rem .2rem; }
        .block-name-input { font-size: .8rem; font-weight: 800; text-transform: uppercase; width: 12rem; padding: .15rem .35rem; border: 1px solid #4f46e5; border-radius: .35rem; }
        .block-head .tools { margin-left: auto; display: flex; flex-wrap: wrap; gap: .25rem; }

        /* A line: its chords above the lyrics, both in a monospaced font so a chord sits over its letter. */
        .sl { position: relative; padding-left: 1.6rem; font-family: var(--mono); font-size: 1rem; }
        .sl + .sl { margin-top: .15rem; }
        .sl-chords { position: relative; height: 1.35em; }
        .sl-chord { position: absolute; top: 0; padding: 0 .1em; border-radius: .25rem; font-weight: 800; color: #4338ca; cursor: grab; white-space: nowrap; touch-action: none; user-select: none; line-height: 1.35; }
        .sl-chord:hover { background: #eef2ff; }
        .sl-chord.is-unknown { color: #c2410c; background: #fff7ed; }
        .sl-chord.is-selected { outline: 2px solid #4f46e5; background: #eef2ff; }
        .sl-chord.is-dragging { cursor: grabbing; background: #e0e7ff; }
        .sl-text { white-space: pre; color: #111827; cursor: text; min-height: 1.35em; line-height: 1.35; }
        .sl-text:hover { background: #f9fafb; }
        .sl-comment { font-family: var(--mono); font-size: .85rem; color: #6b7280; font-style: italic; padding-left: 1.6rem; }
        .sl-split { position: absolute; left: 0; top: 1.35em; border: 0; background: none; color: #d1d5db; cursor: pointer; font-size: .75rem; padding: .1rem; }
        .sl-split:hover { color: #4f46e5; }
        .chord-pop { position: absolute; z-index: 50; display: flex; gap: .3rem; align-items: center; padding: .35rem; background: #fff; border: 1px solid #c7d2fe; border-radius: .5rem; box-shadow: 0 8px 20px rgba(0,0,0,.12); }
        .chord-pop input { width: 7rem; padding: .25rem .4rem; border: 1px solid #d1d5db; border-radius: .35rem; font-family: var(--mono); font-weight: 700; }
        .block-text { width: 100%; font-family: var(--mono); font-size: .9rem; border: 1px solid #c7d2fe; border-radius: .4rem; padding: .4rem; }

        .player-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 35; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .4rem; padding: .5rem .75rem; background: #111827; color: #f9fafb; }
        .player-bar .sb { background: #1f2937; border-color: #374151; color: #f9fafb; }
        .player-bar .sb.is-on { background: #4f46e5; border-color: #4f46e5; }
        .player-bar .time { font-variant-numeric: tabular-nums; font-size: .85rem; min-width: 6.5rem; text-align: center; }
        .player-bar select { background: #1f2937; color: #f9fafb; border: 1px solid #374151; border-radius: .4rem; font-size: .8rem; padding: .3rem 1.6rem .3rem .5rem; }
    </style>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-3 sm:p-6">
                @include('songs.partials.tabs', ['song' => $song, 'current' => 'sheet'])
                @include('layouts.alert')

                <div class="sheet-page" :class="{ 'is-moving-block': blockDrag?.moved }" x-data="songSheet(@js([
                    'sections' => $sections,
                    'updatedAt' => $song->updated_at?->toIso8601String(),
                    'saveUrl' => route('songs.sheet.update', $song),
                    'barsUrl' => route('songs.sheet.bars', $song),
                    'audioUrl' => $song->audioUrl(),
                    'youtubeUrl' => $song->youtube_url,
                    'hasBars' => $song->hasBars(),
                ]))" @keydown.window="onKey($event)">
                    <div class="sheet-toolbar">
                        <button type="button" class="sb" @click="undo()" :disabled="!history.length" title="{{ __('Undo') }} (Ctrl/⌘ + Z)"><i class="fa-solid fa-rotate-left"></i></button>
                        <button type="button" class="sb" @click="addBlock()"><i class="fa-solid fa-plus"></i> {{ __('Block') }}</button>
                        <span class="spacer"></span>
                        <span class="sheet-state" :class="{ 'is-error': stateError }" x-text="state"></span>
                        <button type="button" class="sb green" @click="generateBars()" :disabled="saving" title="{{ __('Make the bars from this chord sheet: one bar per chord (the map is made again from scratch)') }}"><i class="fa-solid fa-table-cells"></i> {{ __('Make bars') }}</button>
                        <button type="button" class="sb primary" @click="save()" :disabled="saving || !dirty"><i class="fa-solid fa-floppy-disk"></i> {{ __('Save') }}</button>
                    </div>
                    <p class="sheet-hint">{{ __('Click a letter to put a "?" on it (a chord there, to name later). Double-click a chord (or select it and press Enter) to name it; drag it along its line; Delete removes the selected one. Space plays and pauses, ← → go back and forward 5 s.') }}</p>

                    <template x-for="(block, b) in blocks" :key="block.id">
                        <div class="sheet-block" :style="`--type-color: var(--type-${typeOf(block)})`" :data-block="block.id"
                            :class="{ 'is-dragging': blockDrag?.id === block.id && blockDrag.moved, 'is-drop-before': blockDrag?.moved && blockDrag.target === block.id && blockDrag.before, 'is-drop-after': blockDrag?.moved && blockDrag.target === block.id && !blockDrag.before }">
                            <div class="block-head">
                                <span class="block-grip" @pointerdown="startBlockDrag($event, block)" title="{{ __('Drag to move the block') }}"><i class="fa-solid fa-grip-vertical"></i></span>
                                <template x-if="renaming !== block.id">
                                    <button type="button" class="block-name" @click="renaming = block.id" title="{{ __('Rename the block') }}" x-text="nameOf(block)"></button>
                                </template>
                                <template x-if="renaming === block.id">
                                    <input type="text" class="block-name-input" :value="nameOf(block)" x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                        @keydown.enter.prevent="rename(block, $event.target.value)" @keydown.escape.stop="renaming = null" @blur="rename(block, $event.target.value)">
                                </template>
                                <span class="tools">
                                    <button type="button" class="sb small" @click="editText(block)" :class="{ primary: textEdit?.id === block.id }"><i class="fa-solid fa-pen"></i> {{ __('Text') }}</button>
                                    <button type="button" class="sb small" @click="duplicate(block)" title="{{ __('Duplicate') }}"><i class="fa-solid fa-clone"></i></button>
                                    <button type="button" class="sb small" @click="mergeNext(block)" :disabled="b === blocks.length - 1" title="{{ __('Join with the next') }}"><i class="fa-solid fa-object-group"></i></button>
                                    <button type="button" class="sb small" @click="removeBlock(block)" :disabled="blocks.length < 2" title="{{ __('Remove') }}"><i class="fa-solid fa-trash"></i></button>
                                </span>
                            </div>

                            {{-- The whole block as text (lyrics fixes): chords in brackets, e.g. Esp[G]írito Santo --}}
                            <template x-if="textEdit?.id === block.id">
                                <div>
                                    <textarea class="block-text" rows="6" x-model="textEdit.text" @keydown.escape.stop="textEdit = null"></textarea>
                                    <p class="sheet-hint" style="margin: .2rem 0 0;">{{ __('Chords in brackets (Esp[G]írito Santo), or pasted over the lyrics (a line of chords, the lyrics under it): they are put into the lyrics when applied.') }}</p>
                                    <div style="display: flex; gap: .4rem; margin-top: .3rem;">
                                        <button type="button" class="sb small primary" @click="applyText()">{{ __('Apply') }}</button>
                                        <button type="button" class="sb small" @click="textEdit = null">{{ __('Cancel') }}</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="textEdit?.id !== block.id">
                                <div>
                                    <template x-for="(line, l) in block.lines" :key="line.id">
                                        <div>
                                            <template x-if="line.comment !== undefined"><div class="sl-comment" x-text="line.comment"></div></template>
                                            <template x-if="line.comment === undefined">
                                                <div class="sl" :data-line="line.id">
                                                    <button type="button" class="sl-split" x-show="l > 0" @click="split(block, l)" title="{{ __('Split the block here') }}"><i class="fa-solid fa-scissors"></i></button>
                                                    <div class="sl-chords">
                                                        <template x-for="item in placed(line)" :key="item.chord.id">
                                                            <span class="sl-chord" :class="{ 'is-unknown': item.chord.name === '?', 'is-selected': selected?.chord === item.chord.id, 'is-dragging': chordDrag?.chord === item.chord.id && chordDrag.moved }"
                                                                :style="`left: ${item.x}ch`" x-text="item.chord.name"
                                                                @pointerdown="startChordDrag($event, block, line, item.chord)" @dblclick="selected = { block: block.id, line: line.id, chord: item.chord.id }; $nextTick(() => renameSelected())"></span>
                                                        </template>
                                                    </div>
                                                    <div class="sl-text" @click="clickText($event, block, line)" x-text="line.text || ' '"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- A chord typed on a letter (new or renamed) --}}
                    <div class="chord-pop" x-show="pop" x-cloak :style="pop ? `left: ${pop.x}px; top: ${pop.y}px` : ''" @click.outside="if (pop && performance.now() - pop.openedAt > 300) pop = null">
                        <input type="text" x-ref="popInput" placeholder="G, Am7, ?" autocomplete="off" spellcheck="false"
                            @keydown.enter.prevent="applyPop()" @keydown.escape.stop="pop = null">
                        <button type="button" class="sb small primary" @click="applyPop()">OK</button>
                        <button type="button" class="sb small" @click="$refs.popInput.value = '?'; applyPop()" title="{{ __('Chord not known yet') }}">?</button>
                    </div>

                    {{-- Player: the song's MP3, or its YouTube video --}}
                    <div class="player-bar" x-show="audioUrl || videoId">
                        <button type="button" class="sb" @click="seekBy(-5)" title="-5 s"><i class="fa-solid fa-backward"></i></button>
                        <button type="button" class="sb is-on" @click="togglePlay()" :aria-label="isPlaying ? @js(__('Pause')) : @js(__('Play'))"><i class="fa-solid" :class="isPlaying ? 'fa-pause' : 'fa-play'"></i></button>
                        <button type="button" class="sb" @click="seekBy(5)" title="+5 s"><i class="fa-solid fa-forward"></i></button>
                        <span class="time" x-text="`${formatTime(time)} / ${formatTime(duration)}`"></span>
                        <select x-model.number="speed" @change="setSpeed(speed)" aria-label="{{ __('Speed') }}">
                            <option value="0.5">0.5×</option><option value="0.75">0.75×</option><option value="0.9">0.9×</option><option value="1">1×</option>
                        </select>
                        {{-- Repeat a passage: A at its start, B at its end --}}
                        <button type="button" class="sb" :class="{ 'is-on': loopA !== null }" @click="markLoop()" :title="@js(__('Repeat a passage: click at its start (A) and at its end (B); again to stop'))"
                            x-text="loopA === null ? 'A–B' : (loopB === null ? 'B…' : `↻ ${formatTime(loopA)}–${formatTime(loopB)}`)"></button>
                    </div>

                    @include('songs.partials.youtube-mini', ['id' => 'yt-sheet'])
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/chord-lines.js') }}?v={{ filemtime(public_path('js/chord-lines.js')) }}"></script>
    <script>
        function songSheet(config) {
            let nextId = 1;
            const audio = config.audioUrl ? new Audio(config.audioUrl) : null;
            // "Esp[G]írito [D]Santo" -> { text: "Espírito Santo", chords: [{ at: 3, name: "G" }, { at: 9, name: "D" }] }
            const parseLine = (raw) => {
                const text = String(raw ?? '');
                const comment = /^\s*\{\s*(?:c|comment)\s*:\s*(.*?)\s*\}\s*$/i.exec(text);
                if (comment || /^\s*\{/.test(text)) return { id: nextId++, comment: comment ? comment[1] : text, raw: text };
                const chords = [];
                let plain = '';
                let last = 0;
                for (const match of text.matchAll(/\[([^\]]*)\]/g)) {
                    plain += text.slice(last, match.index);
                    chords.push({ id: nextId++, at: plain.length, name: match[1] });
                    last = match.index + match[0].length;
                }
                plain += text.slice(last);
                return { id: nextId++, text: plain.replace(/\s+$/, ''), chords };
            };
            const lineText = (line) => {
                if (line.comment !== undefined) return line.raw;
                let text = line.text;
                [...line.chords].sort((a, b) => b.at - a.at).forEach(chord => {
                    if (chord.at > text.length) text = text.padEnd(chord.at, ' ');
                    text = text.slice(0, chord.at) + `[${chord.name}]` + text.slice(chord.at);
                });
                return text;
            };
            const toBlock = (section) => ({ id: nextId++, section: section.section || 'PART', label: section.label || null, lines: (section.lines || []).map(parseLine) });

            return withYoutubeMini({
                blocks: (config.sections.length ? config.sections : [{ section: 'VERSE_1', lines: [''] }]).map(toBlock),
                audioUrl: config.audioUrl,
                updatedAt: config.updatedAt,
                history: [],
                dirty: false,
                saving: false,
                state: '',
                stateError: false,
                selected: null,
                pop: null,
                renaming: null,
                textEdit: null,
                chordDrag: null,
                blockDrag: null,
                charWidth: 9.6,
                // Player
                time: 0,
                duration: 0,
                speed: 1,
                isPlaying: false,
                loopA: null,
                loopB: null,

                init() {
                    this.$nextTick(() => {
                        this.measureChar();
                        this.initVideo('yt-sheet');
                    });
                    window.addEventListener('pointermove', event => { this.onChordDragMove(event); this.onBlockDragMove(event); });
                    window.addEventListener('pointerup', () => { this.onChordDragEnd(); this.onBlockDragEnd(); });
                    window.addEventListener('beforeunload', event => { if (this.dirty && !this.saving) event.preventDefault(); });
                    if (audio) {
                        audio.addEventListener('loadedmetadata', () => { this.duration = audio.duration; });
                        audio.addEventListener('play', () => { this.isPlaying = true; });
                        audio.addEventListener('pause', () => { this.isPlaying = false; });
                    }
                    setInterval(() => this.followPlayer(), 200);
                },
                // Width of one letter of the monospaced font (to place chords over letters and read clicks).
                measureChar() {
                    const probe = document.createElement('span');
                    probe.style.cssText = 'position: absolute; visibility: hidden; white-space: pre; font-family: var(--mono); font-size: 1rem;';
                    probe.textContent = 'M'.repeat(50);
                    this.$root.appendChild(probe);
                    this.charWidth = probe.getBoundingClientRect().width / 50 || 9.6;
                    probe.remove();
                },

                typeOf(block) { return typeof blockType === 'function' ? blockType(this.nameOf(block)) : 'other'; },
                nameOf(block) { return block.label || String(block.section || '').replaceAll('_', ' '); },
                // Chords of a line where they are drawn: over their letter, pushed right when the one before is long.
                placed(line) {
                    let end = -1;
                    return [...line.chords].sort((a, b) => a.at - b.at).map(chord => {
                        const x = Math.max(chord.at, end + 1);
                        end = x + chord.name.length;
                        return { chord, x };
                    });
                },

                // ---- Changes (undo keeps the blocks as they were) ----
                remember() {
                    this.history.push(JSON.stringify(this.blocks));
                    if (this.history.length > 100) this.history.shift();
                    this.dirty = true;
                    this.state = '';
                },
                undo() {
                    if (!this.history.length) return;
                    this.blocks = JSON.parse(this.history.pop());
                    this.dirty = true;
                    this.selected = null;
                },

                // ---- Chords: put on a letter, renamed, removed, dragged along the line ----
                // A click on a letter puts a "?" there (selected: Enter or a double click names it); on a letter that
                // already has a chord, that chord is selected.
                clickText(event, block, line) {
                    const box = event.currentTarget.getBoundingClientRect();
                    const at = Math.max(0, Math.round((event.clientX - box.left) / this.charWidth - 0.35));
                    this.pop = null;
                    let chord = line.chords.find(item => item.at === at);
                    if (!chord) {
                        this.remember();
                        chord = { id: nextId++, at, name: '?' };
                        line.chords.push(chord);
                    }
                    this.selected = { block: block.id, line: line.id, chord: chord.id };
                },
                openPop(x, y, target, value) {
                    const page = this.$root.getBoundingClientRect();
                    // (The click that opened it does not close it.)
                    this.pop = { ...target, x: Math.max(0, x - page.left - 20), y: y - page.top - 46, openedAt: performance.now() };
                    // A "?" is named from an empty field; another name comes selected (after the click that opened it).
                    this.$nextTick(() => setTimeout(() => {
                        const input = this.$refs.popInput;
                        input.value = value === '?' ? '' : value;
                        input.focus();
                        input.select();
                    }, 0));
                },
                applyPop() {
                    const pop = this.pop;
                    const name = this.$refs.popInput.value.trim().replace(/\s+/g, '');
                    this.pop = null;
                    if (!pop || !name) return;
                    if (name !== '?' && !/^[A-G]/.test(name)) { this.say(@js(__('This is not a chord name.')), true); return; }
                    const line = this.findLine(pop.block, pop.line);
                    if (!line) return;
                    this.remember();
                    const chord = line.chords.find(item => item.id === pop.chord) ?? line.chords.find(item => item.at === pop.at);
                    if (chord) chord.name = name;
                    else line.chords.push({ id: nextId++, at: pop.at, name });
                },
                findLine(blockId, lineId) { return this.blocks.find(block => block.id === blockId)?.lines.find(line => line.id === lineId) ?? null; },
                startChordDrag(event, block, line, chord) {
                    event.preventDefault();
                    this.pop = null;
                    const text = event.currentTarget.closest('.sl').querySelector('.sl-text').getBoundingClientRect();
                    this.chordDrag = { block: block.id, line: line.id, chord: chord.id, left: text.left, x: event.clientX, from: chord.at, moved: false, remembered: false };
                },
                onChordDragMove(event) {
                    const drag = this.chordDrag;
                    if (!drag) return;
                    if (!drag.moved && Math.abs(event.clientX - drag.x) < 4) return;
                    drag.moved = true;
                    const line = this.findLine(drag.block, drag.line);
                    const chord = line?.chords.find(item => item.id === drag.chord);
                    if (!chord) return;
                    // Letter by letter, never onto another chord's letter.
                    const at = Math.max(0, Math.round((event.clientX - drag.left) / this.charWidth - 0.35));
                    if (at === chord.at || line.chords.some(item => item !== chord && item.at === at)) return;
                    if (!drag.remembered) { this.remember(); drag.remembered = true; }
                    chord.at = at;
                },
                onChordDragEnd() {
                    const drag = this.chordDrag;
                    if (!drag) return;
                    this.chordDrag = null;
                    // A click (no drag): the chord is selected.
                    if (!drag.moved) this.selected = { block: drag.block, line: drag.line, chord: drag.chord };
                },
                removeSelected() {
                    const line = this.selected && this.findLine(this.selected.block, this.selected.line);
                    if (!line) return;
                    this.remember();
                    line.chords = line.chords.filter(chord => chord.id !== this.selected.chord);
                    this.selected = null;
                },
                renameSelected() {
                    const line = this.selected && this.findLine(this.selected.block, this.selected.line);
                    const chord = line?.chords.find(item => item.id === this.selected.chord);
                    const element = chord && this.$root.querySelector(`[data-line="${line.id}"] .sl-chord.is-selected`);
                    if (!element) return;
                    const box = element.getBoundingClientRect();
                    this.openPop(box.left, box.top + box.height, { block: this.selected.block, line: line.id, at: chord.at, chord: chord.id }, chord.name);
                },

                // ---- Blocks ----
                rename(block, value) {
                    if (this.renaming !== block.id) return;
                    this.renaming = null;
                    const name = String(value || '').trim().toUpperCase().replace(/\s+/g, ' ');
                    if (!name || name === this.nameOf(block)) return;
                    this.remember();
                    block.section = name.replace(/ /g, '_');
                    block.label = name;
                },
                editText(block) {
                    if (this.textEdit?.id === block.id) { this.textEdit = null; return; }
                    this.textEdit = { id: block.id, text: block.lines.map(lineText).join('\n') };
                },
                applyText() {
                    const block = this.blocks.find(item => item.id === this.textEdit.id);
                    if (!block) return;
                    this.remember();
                    block.lines = chordsOverLyrics(this.textEdit.text.replace(/\s+$/, '')).split('\n').map(parseLine);
                    this.textEdit = null;
                },
                copyOf(block) { return { ...JSON.parse(JSON.stringify(block)), id: nextId++, lines: block.lines.map(line => ({ ...JSON.parse(JSON.stringify(line)), id: nextId++, chords: (line.chords || []).map(chord => ({ ...chord, id: nextId++ })) })) }; },
                duplicate(block) {
                    this.remember();
                    this.blocks.splice(this.blocks.indexOf(block) + 1, 0, this.copyOf(block));
                },
                removeBlock(block) {
                    if (this.blocks.length < 2 || !confirm(@js(__('Remove this block?')))) return;
                    this.remember();
                    this.blocks = this.blocks.filter(item => item !== block);
                },
                mergeNext(block) {
                    const index = this.blocks.indexOf(block);
                    const next = this.blocks[index + 1];
                    if (!next) return;
                    this.remember();
                    block.lines = [...block.lines, ...next.lines];
                    this.blocks.splice(index + 1, 1);
                },
                split(block, at) {
                    this.remember();
                    const index = this.blocks.indexOf(block);
                    const rest = { id: nextId++, section: 'PART', label: null, lines: block.lines.slice(at) };
                    block.lines = block.lines.slice(0, at);
                    this.blocks.splice(index + 1, 0, rest);
                    this.renaming = rest.id;
                },
                addBlock() {
                    this.remember();
                    const block = { id: nextId++, section: 'PART', label: null, lines: [parseLine('')] };
                    this.blocks.push(block);
                    this.textEdit = { id: block.id, text: '' };
                },
                startBlockDrag(event, block) {
                    event.preventDefault();
                    this.blockDrag = { id: block.id, y: event.clientY, moved: false, target: null, before: true };
                },
                onBlockDragMove(event) {
                    const drag = this.blockDrag;
                    if (!drag) return;
                    if (!drag.moved && Math.abs(event.clientY - drag.y) < 6) return;
                    if (!drag.moved) {
                        // The blocks fold to their heads: the dragged one is brought back under the pointer.
                        drag.moved = true;
                        this.$nextTick(() => {
                            const element = document.querySelector(`.sheet-block[data-block="${drag.id}"]`);
                            if (element) window.scrollBy(0, element.getBoundingClientRect().top - (event.clientY - 16));
                        });
                        return;
                    }
                    const element = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-block]');
                    if (!element) return;
                    const box = element.getBoundingClientRect();
                    drag.target = Number(element.dataset.block);
                    drag.before = event.clientY < box.top + box.height / 2;
                    if (event.clientY < 60) window.scrollBy(0, -12);
                    else if (event.clientY > window.innerHeight - 90) window.scrollBy(0, 12);
                },
                onBlockDragEnd() {
                    const drag = this.blockDrag;
                    this.blockDrag = null;
                    if (!drag?.moved || drag.target === null || drag.target === drag.id) return;
                    const block = this.blocks.find(item => item.id === drag.id);
                    this.remember();
                    const rest = this.blocks.filter(item => item !== block);
                    const index = rest.findIndex(item => item.id === drag.target);
                    rest.splice(drag.before ? index : index + 1, 0, block);
                    this.blocks = rest;
                },

                // ---- Saving, and the bars made from the chord sheet ----
                sections() {
                    return this.blocks.map(block => ({ section: block.section, label: block.label, lines: block.lines.map(lineText) }));
                },
                say(text, error = false) { this.state = text; this.stateError = error; },
                async send(url, method) {
                    const response = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ sections: this.sections(), updated_at: this.updatedAt }),
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(Object.values(result.errors || {})[0]?.[0] || result.message || @js(__('Something went wrong')));
                    return result;
                },
                async save() {
                    if (this.saving) return;
                    this.saving = true;
                    this.say(@js(__('Saving…')));
                    try {
                        const result = await this.send(config.saveUrl, 'PUT');
                        this.updatedAt = result.updated_at;
                        this.dirty = false;
                        this.say(@js(__('Saved ✓')));
                    } catch (error) {
                        this.say(error.message, true);
                    } finally {
                        this.saving = false;
                    }
                },
                async generateBars() {
                    if (this.saving) return;
                    const question = config.hasBars
                        ? @js(__('Make the bars again from this chord sheet? The chord map is made from scratch (one bar per chord); the one now stays in the history.'))
                        : @js(__('Make the bars from this chord sheet (one bar per chord)?'));
                    if (!confirm(question)) return;
                    this.saving = true;
                    this.say(@js(__('Making the bars…')));
                    try {
                        const result = await this.send(config.barsUrl, 'POST');
                        this.dirty = false;
                        location.href = result.redirect;
                    } catch (error) {
                        this.say(error.message, true);
                        this.saving = false;
                    }
                },

                // ---- Player ----
                followPlayer() {
                    if (audio) {
                        this.time = audio.currentTime;
                    } else if (this.playerReady) {
                        this.time = this.videoCurrentTime();
                        this.duration = this.videoDuration() === Infinity ? 0 : this.videoDuration();
                        this.isPlaying = this.playing;
                    }
                    if (this.loopA !== null && this.loopB !== null && this.isPlaying && this.time >= this.loopB) this.seekTo(this.loopA);
                },
                togglePlay() {
                    if (audio) return audio.paused ? audio.play() : audio.pause();
                    this.toggleVideo();
                },
                seekTo(time) {
                    const target = Math.max(0, time);
                    if (audio) audio.currentTime = target;
                    else this.seekVideo(target, this.isPlaying);
                },
                seekBy(seconds) { this.seekTo(this.time + seconds); },
                setSpeed(speed) {
                    if (audio) audio.playbackRate = speed;
                    else this.setVideoSpeed(speed);
                },
                markLoop() {
                    if (this.loopA === null) this.loopA = this.time;
                    else if (this.loopB === null && this.time > this.loopA) this.loopB = this.time;
                    else { this.loopA = null; this.loopB = null; }
                },
                formatTime(seconds) {
                    const total = Math.max(0, Math.floor(seconds || 0));
                    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
                },

                onKey(event) {
                    if (event.target.closest('input, textarea, select')) return;
                    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); return this.undo(); }
                    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') { event.preventDefault(); return this.save(); }
                    if (event.key === ' ') { event.preventDefault(); return this.togglePlay(); }
                    if (event.key === 'ArrowLeft') { event.preventDefault(); return this.seekBy(-5); }
                    if (event.key === 'ArrowRight') { event.preventDefault(); return this.seekBy(5); }
                    if (this.selected && (event.key === 'Backspace' || event.key === 'Delete')) { event.preventDefault(); return this.removeSelected(); }
                    if (this.selected && event.key === 'Enter') { event.preventDefault(); return this.renameSelected(); }
                    if (event.key === 'Escape') { this.selected = null; this.pop = null; }
                },
            }, config.youtubeUrl);
        }
    </script>
</x-app-layout>
