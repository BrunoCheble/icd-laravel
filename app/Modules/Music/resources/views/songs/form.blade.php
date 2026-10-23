@include('partials.ui')

<div class="m-form">

    {{-- Filled by the "Send to ICD" bookmarklet import (see the script at the end of this form) --}}
    <div id="import-notice" class="m-card m-section text-sm text-gray-700" style="display: none;"></div>

    <section class="m-card m-section">
        <h2 class="m-section-title"><i class="fa-solid fa-music"></i> {{ __('Song') }}</h2>
        <div class="m-grid cols-2">
            <div class="m-field">
                <label class="m-label" for="title">{{ __('Title') }}</label>
                <input id="title" name="title" type="text" class="m-input" value="{{ old('title', $song?->title) }}" required>
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div class="m-field">
                <label class="m-label" for="artist">{{ __('Artist') }}</label>
                <input id="artist" name="artist" type="text" class="m-input" value="{{ old('artist', $song?->artist) }}" required>
                <x-input-error :messages="$errors->get('artist')" />
            </div>
        </div>
    </section>

    <section class="m-card m-section">
        <h2 class="m-section-title"><i class="fa-solid fa-sliders"></i> {{ __('Key and tempo') }}</h2>
        <div class="m-grid cols-3">
            <div class="m-field">
                <label class="m-label" for="musical_key">{{ __('Key') }}</label>
                @php
                    // One option per pair of relative keys ("C / Am"), storing the key of the song's mode (new songs: major).
                    $selectedKey = old('musical_key', $song?->musical_key);
                    $minorSong = \App\Modules\Music\Enums\MusicalKey::isMinor($selectedKey);
                    $isListedKey = collect($keyOptions)->contains(fn ($pair) => $pair[$minorSong ? 'minor' : 'major'] === $selectedKey);
                @endphp
                <select id="musical_key" name="musical_key" class="m-input">
                    <option value="">{{ __('Select') }}</option>
                    @if ($selectedKey && ! $isListedKey)
                        <option value="{{ $selectedKey }}" selected>{{ $selectedKey }}</option>
                    @endif
                    @foreach ($keyOptions as $pair)
                        <option value="{{ $pair[$minorSong ? 'minor' : 'major'] }}" @selected($pair[$minorSong ? 'minor' : 'major'] === $selectedKey)>{{ $pair['label'] }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('musical_key')" />
            </div>

            {{-- Tempo: the live mode of the setlist app counts the bars with it --}}
            <div class="m-field">
                <label class="m-label" for="bpm">{{ __('BPM') }}</label>
                <input id="bpm" name="bpm" type="number" step="0.1" min="30" max="300" class="m-input" value="{{ old('bpm', $song?->bpm ?? \App\Modules\Music\Services\CreateSongBarsService::DEFAULT_BPM) }}">
                <x-input-error :messages="$errors->get('bpm')" />
            </div>

            {{-- Time signature: blocks without bars get them (one bar per chord) when the song is saved --}}
            <div class="m-field" x-data="{ meter: @js(old('meter', $song ? \App\Modules\Music\Services\CreateSongBarsService::meterOf($song->structure) : '4/4')) }">
                <label class="m-label" for="meter">{{ __('Time signature') }}</label>
                <select id="meter" name="meter" x-model="meter" class="m-input">
                    @foreach (array_keys(\App\Modules\Music\Services\CreateSongBarsService::METERS) as $meter)
                        <option value="{{ $meter }}">{{ $meter }}</option>
                    @endforeach
                </select>
                <p class="m-help" style="color: var(--brand-strong);" x-show="meter === '6/8'" x-cloak>{{ __('In 6/8 the bars count the eighth notes: the BPM is of the eighths (a song felt at 60 is 180).') }}</p>
                <x-input-error :messages="$errors->get('meter')" />
            </div>
        </div>
        <p id="key-transpose-notice" class="m-help mt-3" style="display: none; color: var(--brand-strong);"></p>
        <p class="m-help mt-3">{{ __('Beats per minute. Filled in by the study app (icd-chords) when it saves the map; the live mode of the setlist follows it.') }} {{ __('Blocks without bars get them when the song is saved: the chords of each block, one bar each, to be adjusted on the grid.') }} {{ __('Changing it moves only the bar lines: every chord stays on its beat.') }}</p>
    </section>

    <section class="m-card m-section">
        <h2 class="m-section-title"><i class="fa-solid fa-link"></i> {{ __('Audio and links') }}</h2>
        <div class="m-grid cols-2">
            <div class="m-field">
                <label class="m-label" for="youtube_url">YouTube <span class="m-optional">· {{ __('optional') }}</span></label>
                <input id="youtube_url" name="youtube_url" type="url" class="m-input" value="{{ old('youtube_url', $song?->youtube_url) }}" placeholder="https://youtu.be/…">
                <x-input-error :messages="$errors->get('youtube_url')" />
            </div>
            <div class="m-field">
                <label class="m-label" for="source_url">{{ __('Source') }} <span class="m-optional">· {{ __('optional') }}</span></label>
                <input id="source_url" name="source_url" type="url" class="m-input" value="{{ old('source_url', $song?->source_url) }}" placeholder="https://www.cifraclub.com.br/…">
                <x-input-error :messages="$errors->get('source_url')" />
                <p id="source-duplicate" class="m-help" style="display: none; font-weight: 600; color: var(--tx-b45309);"></p>
            </div>

            {{-- Audio file: played instead of the YouTube video in the setlist app, also offline --}}
            <div class="m-field m-span-2" x-data="songAudioField()">
                <label class="m-label" for="audio">{{ __('Audio (MP3)') }} <span class="m-optional">· {{ __('optional') }}</span></label>
                @if ($song?->audioUrl())
                    <div class="flex flex-wrap items-center gap-3" x-show="!remove">
                        <audio controls preload="none" src="{{ $song->audioUrl() }}" style="max-width: 100%;"></audio>
                        <button type="button" class="m-btn is-ghost" style="color: var(--tx-dc2626);" @click="remove = true"><i class="fa-solid fa-trash"></i> {{ __('Remove audio') }}</button>
                    </div>
                    <p class="m-help" style="color: var(--tx-dc2626);" x-show="remove" x-cloak>
                        {{ __('The audio will be removed when you save.') }}
                        <button type="button" class="m-link" @click="remove = false">{{ __('Undo') }}</button>
                    </p>
                    <input type="hidden" name="remove_audio" :value="remove ? 1 : 0">
                @endif
                <input id="audio" name="audio" type="file" accept=".mp3,.m4a,.aac,.ogg,.wav,audio/*" class="m-input" style="padding: .45rem;" @change="convert($event.target)" :disabled="converting">
                <p class="m-help" style="font-weight: 600; color: var(--brand-strong);" x-show="status" x-text="status" x-cloak></p>
                <p class="m-help">{{ __('Up to :size MB. When the song has an audio file, the setlist app plays it instead of the YouTube video, also offline.', ['size' => \App\Modules\Music\Services\SaveSongAudioService::maxUploadMegabytes()]) }}</p>
                <x-input-error :messages="$errors->get('audio')" />
            </div>
        </div>
    </section>

    <section class="m-card m-section">
        <h2 class="m-section-title"><i class="fa-solid fa-code"></i> {{ __('Chord map and chord sheet') }} <small>JSON</small></h2>
        <div class="m-grid">
            <div class="m-field">
                <label class="m-label" for="structure">{{ __('Structure') }}</label>
                <p class="m-help">{{ __('JSON generated by the Música project. It is saved exactly as entered, as long as it is valid JSON.') }}</p>
                <textarea id="structure" name="structure" rows="14" spellcheck="false" required class="m-input is-code">{{ old('structure', $song->exists ? $song->structureAsJson() : '') }}</textarea>
                <x-input-error :messages="$errors->get('structure')" />
            </div>

            <div class="m-grid" x-data="chordSheetImport(@js(route('songs.chord-sheet.parse')), @js(route('songs.chord-sheet.structure')))">
                <div class="m-field">
                    <label class="m-label" for="chord_sheet_source">{{ __('Chord sheet (optional)') }}</label>
                    <p class="m-help">{{ __('Paste the HTML of the chord sheet <pre> or its text. It becomes a separate chord sheet: the structure above is not changed.') }}</p>
                    <textarea id="chord_sheet_source" x-model="source" rows="5" spellcheck="false" class="m-input is-code" placeholder="<pre>…</pre>"></textarea>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" class="m-btn" @click="parse()" :disabled="parsing || !source.trim()"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('Interpret chord sheet') }}</button>
                        <span class="m-help" x-show="message" x-text="message" :class="{ 'text-red-600': failed }"></span>
                    </div>
                </div>

                <div class="m-field">
                    <label class="m-label" for="chord_sheet">{{ __('Chord sheet') }}</label>
                    <textarea id="chord_sheet" name="chord_sheet" x-ref="sheet" rows="10" spellcheck="false" class="m-input is-code">{{ old('chord_sheet', $song->exists ? $song->chordSheetAsJson() : '') }}</textarea>
                    <x-input-error :messages="$errors->get('chord_sheet')" />
                    <div>
                        <button type="button" class="m-btn" @click="toStructure()" :disabled="converting"><i class="fa-solid fa-diagram-project"></i> {{ __('Generate structure from the chord sheet') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Sends the pasted sheet to the parser and puts the resulting JSON in the chord_sheet field for review.
        function chordSheetImport(url, structureUrl) {
            return {
                converting: false,
                // Replaces the structure (chord map) with one generated from the chord sheet field.
                async toStructure() {
                    const structure = document.getElementById('structure');
                    if (!this.$refs.sheet.value.trim()) {
                        this.failed = true;
                        this.message = @js(__('Fill in the chord sheet first.'));
                        return;
                    }
                    if (structure.value.trim() && !(await askConfirm(@js(__('Replace the current structure with one generated from the chord sheet?'))))) return;

                    this.converting = true;
                    this.failed = false;
                    try {
                        const response = await fetch(structureUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ chord_sheet: this.$refs.sheet.value, structure: structure.value }),
                        });
                        const json = await response.json();
                        if (!response.ok) throw new Error(json.errors ? Object.values(json.errors).flat()[0] : json.message);

                        structure.value = JSON.stringify(json.structure, null, 4);
                        this.message = @js(__('Structure generated from the chord sheet: :blocks blocks. Review and save.')).replace(':blocks', json.structure.length);
                    } catch (error) {
                        this.failed = true;
                        this.message = error.message || @js(__('Something went wrong'));
                    } finally {
                        this.converting = false;
                    }
                },
                source: '',
                parsing: false,
                structureGenerated: false,
                expectedChords: 0,
                init() {
                    // Data sent by the bookmarklet: fill the chord sheet and interpret it right away.
                    window.addEventListener('icd-import', event => {
                        this.source = event.detail.sheet || '';
                        this.expectedChords = event.detail.chordCount || 0;
                        this.transpose = event.detail.transpose || null;
                        if (this.source) this.parse();
                    });
                },
                message: '',
                failed: false,
                async parse() {
                    this.parsing = true;
                    this.failed = false;
                    this.message = @js(__('Interpreting...'));
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ source: this.source }),
                        });
                        const json = await response.json();
                        if (!response.ok) throw new Error(json.errors ? Object.values(json.errors).flat()[0] : json.message);

                        // A chord sheet imported in another key than the song's (replacing it): in the song's key.
                        let moved = 0;
                        if (this.transpose && window.ChordTransposer && ChordTransposer.parseKey(this.transpose.from) && ChordTransposer.parseKey(this.transpose.to)) {
                            (json.chord_sheet?.sections || []).forEach(section => {
                                section.lines = (section.lines || []).map(line => (/^\s*\{/.test(String(line)) ? line : String(line).replace(/\[([^\]]+)\]/g, (all, name) => {
                                    moved++;
                                    return `[${ChordTransposer.transposeChord(name, this.transpose.from, this.transpose.to)}]`;
                                })));
                            });
                        }
                        this.$refs.sheet.value = JSON.stringify(json.chord_sheet, null, 4);

                        // A song without a chord map gets an initial one generated from the chord sheet.
                        const structure = document.getElementById('structure');
                        if (structure && !structure.value.trim() && json.structure?.length) {
                            structure.value = JSON.stringify(json.structure, null, 4);
                            this.structureGenerated = true;
                        }
                        const stats = json.stats;
                        this.message = @js(__(':sections blocks, :lines lines, :chords chords. Review and save.'))
                            .replace(':sections', stats.sections).replace(':lines', stats.lines).replace(':chords', stats.chords)
                            + (this.structureGenerated ? ' ' + @js(__('The structure was generated from the chord sheet.')) : '')
                            + (moved ? ' ' + @js(__('Chords transposed from :from to :to, the key of this song.')).replace(':from', this.transpose.from).replace(':to', this.transpose.to) : '');

                        // The bookmarklet counted the chords on the page: warn when some were not interpreted.
                        if (this.expectedChords && stats.chords < this.expectedChords) {
                            this.failed = true;
                            this.message = @js(__('Attention: the page has :expected chords but only :found were interpreted. Check the chord sheet.'))
                                .replace(':expected', this.expectedChords).replace(':found', stats.chords) + ' ' + this.message;
                        }
                    } catch (error) {
                        this.failed = true;
                        this.message = error.message || @js(__('Something went wrong'));
                    } finally {
                        this.parsing = false;
                    }
                },
            };
        }
    </script>

    <div class="m-form-actions">
        <a href="{{ $song->exists ? route('songs.show', $song) : route('songs.index') }}" class="m-btn is-ghost">{{ __('Cancel') }}</a>
        <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
    </div>
</div>

<script>
    // Receives the data of the "Send to ICD" bookmarklet from the URL fragment (#icd-import=...).
    // Values are only placed into form fields (never as HTML); nothing is saved automatically.
    (function () {
        const match = location.hash.match(/^#icd-import=(.+)$/);
        if (!match) return;

        let data;
        try {
            data = JSON.parse(decodeURIComponent(match[1]));
        } catch (e) {
            return;
        }
        history.replaceState(null, '', location.pathname + location.search);

        // Keys written with sharps/flats not used in the selector.
        const enharmonic = { 'A#': 'Bb', 'D#': 'Eb', 'G#': 'Ab', 'C#': 'Db', 'Gb': 'F#', 'A#m': 'Bbm', 'D#m': 'Ebm', 'Dbm': 'C#m', 'Gbm': 'F#m', 'Abm': 'G#m' };
        const missing = [];

        const fill = (id, value, label) => {
            const field = document.getElementById(id);
            if (!field) return;
            if (!value) {
                missing.push(label);
                return;
            }
            if (!field.value) field.value = value;
        };

        const apply = () => {
            fill('title', data.title, @js(__('Title')));
            fill('artist', data.artist, @js(__('Artist')));
            fill('source_url', data.source, @js(__('Source')));
            fill('youtube_url', data.youtube, @js(__('YouTube')));

            const keySelect = document.getElementById('musical_key');
            const key = enharmonic[data.key] || data.key;
            if (keySelect && key && [...keySelect.options].some(option => option.value === key)) {
                if (!keySelect.value) keySelect.value = key;
            } else {
                missing.push(@js(__('Key')) + (data.key ? ` (${data.key})` : ''));
            }

            // Replacing the chord sheet of a song already here: in that song's key.
            const songKey = keySelect?.value || '';
            const importedKey = enharmonic[data.key] || data.key;
            const transpose = data.replace && songKey && importedKey && songKey !== importedKey ? { from: importedKey, to: songKey } : null;
            if (data.sheet) {
                window.dispatchEvent(new CustomEvent('icd-import', { detail: { sheet: data.sheet, chordCount: data.chordCount, transpose } }));
            } else {
                missing.push(@js(__('Chord sheet')));
            }

            const notice = document.getElementById('import-notice');
            if (notice) {
                const outdated = data.version !== @js(\App\Modules\Music\Http\Controllers\SongChordSheetController::bookmarkletVersion());
                notice.style.display = '';
                notice.textContent = (outdated ? @js(__('Your "Send to ICD" bookmark is outdated: delete it and drag it again from the import page.')) + ' ' : '')
                    + @js(__('Imported from :source. Review the fields and save.')).replace(':source', data.source || '')
                    + (missing.length ? ' ' + @js(__('Not found on the page:')) + ' ' + missing.join(', ') + '.' : '');
                if (outdated) notice.style.borderColor = '#dc2626';
                if (data.replace) {
                    notice.textContent = @js(__('Replacing the chord sheet of this song with the one imported from :source: the chord map (structure) does not change. Review and save (the current version stays in the history).')).replace(':source', data.source || '');
                    notice.style.borderColor = 'var(--brand)';
                } else {
                    offerReplace(notice);
                }
            }
        };

        // New song with the chord sheet of a song already here (same address or same title): it can go to that song
        // instead (replacing its chord sheet), or be created anyway.
        const offerReplace = (notice) => {
            if (@js(isset($song) && $song->exists)) return;
            const sources = @js($existingSources ?? []);
            const titles = @js($existingTitles ?? []);
            const plainUrl = (url) => {
                try {
                    const parsed = new URL(/^[a-z][a-z0-9+.-]*:\/\//i.test(url) ? url : 'http://' + url);
                    return parsed.hostname.toLowerCase().replace(/^www\./, '') + parsed.pathname.toLowerCase().replace(/\/+$/, '');
                } catch (e) {
                    return String(url || '').toLowerCase();
                }
            };
            const plainTitle = (title) => String(title || '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]/g, '');
            const existing = (data.source && sources[plainUrl(data.source)]) || titles[plainTitle(data.title)];
            if (!existing) return;
            // A song already here without a chord sheet (pre-registered): filled right away, then its layout opens.
            if (existing.pending && existing.import && data.sheet) {
                notice.textContent = @js(__('Saving the chord sheet, the chord map and the bars of ":title"…')).replace(':title', existing.title);
                notice.style.borderColor = 'var(--brand)';
                fetch(existing.import, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ sheet: data.sheet, key: data.key || null, youtube: data.youtube || null }),
                }).then(async response => {
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(json.errors ? Object.values(json.errors).flat()[0] : (json.message || @js(__('Something went wrong'))));
                    location.href = json.redirect;
                }).catch(error => {
                    notice.textContent = error.message;
                    notice.style.borderColor = '#dc2626';
                });
                return;
            }
            const box = document.createElement('div');
            box.style.cssText = 'margin-top: .6rem; padding: .6rem .7rem; border-radius: .4rem; background: var(--brand-soft); color: var(--brand-strong);';
            box.append(@js(__('This song is already here:')) + ' "' + existing.title + '". ');
            const replace = document.createElement('a');
            replace.href = existing.edit + '#icd-import=' + encodeURIComponent(JSON.stringify({ ...data, replace: true }));
            replace.textContent = @js(__('Replace its chord sheet'));
            replace.style.cssText = 'font-weight: 700; text-decoration: underline; margin-right: 1rem;';
            const create = document.createElement('a');
            create.href = '#';
            create.textContent = @js(__('Create a new song anyway'));
            create.style.textDecoration = 'underline';
            create.addEventListener('click', event => { event.preventDefault(); box.remove(); });
            box.append(replace, create);
            notice.append(box);
        };

        // Wait for Alpine so the chord sheet component is listening.
        document.addEventListener('alpine:initialized', apply);
    })();
</script>

<script src="https://cdn.jsdelivr.net/npm/lamejs@1.2.1/lame.min.js"></script>
<script>
    // Audio file of the song: before sending, a file heavier than needed (above about 128 kbps, or not MP3) is turned
    // into MP3 at 128 kbps with a constant bitrate, in the browser. Smaller uploads, and a constant bitrate keeps
    // jumping to a block's start exact. If the conversion fails, the original file is sent.
    function songAudioField() {
        const TARGET_KBPS = 128;
        const megabytes = (bytes) => (bytes / 1048576).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        return {
            remove: false,
            converting: false,
            status: '',
            init() {
                this.$el.closest('form')?.addEventListener('submit', (event) => {
                    if (this.converting) event.preventDefault();
                });
            },
            async convert(input) {
                const file = input.files?.[0];
                this.status = '';
                if (!file || !window.lamejs) return;
                this.converting = true;
                try {
                    const context = new (window.AudioContext || window.webkitAudioContext)();
                    const decoded = await context.decodeAudioData(await file.arrayBuffer());
                    context.close();
                    const kbps = file.size * 8 / decoded.duration / 1000;
                    if (/\.mp3$/i.test(file.name) && kbps <= TARGET_KBPS * 1.1) {
                        this.status = @js(__('The file is already light: it will be sent as it is.'));
                        return;
                    }

                    // 44.1 kHz, up to 2 channels, as the MP3 encoder expects.
                    const channels = Math.min(2, decoded.numberOfChannels);
                    const offline = new OfflineAudioContext(channels, Math.ceil(decoded.duration * 44100), 44100);
                    const source = offline.createBufferSource();
                    source.buffer = decoded;
                    source.connect(offline.destination);
                    source.start();
                    const audio = await offline.startRendering();

                    const encoder = new lamejs.Mp3Encoder(channels, 44100, TARGET_KBPS);
                    const toInt16 = (samples) => {
                        const out = new Int16Array(samples.length);
                        for (let i = 0; i < samples.length; i++) out[i] = Math.max(-1, Math.min(1, samples[i])) * 0x7fff;
                        return out;
                    };
                    const left = toInt16(audio.getChannelData(0));
                    const right = channels > 1 ? toInt16(audio.getChannelData(1)) : null;
                    const parts = [];
                    const block = 1152 * 100;
                    for (let start = 0; start < left.length; start += block) {
                        const end = start + block;
                        const chunk = right ? encoder.encodeBuffer(left.subarray(start, end), right.subarray(start, end)) : encoder.encodeBuffer(left.subarray(start, end));
                        if (chunk.length) parts.push(new Int8Array(chunk));
                        this.status = @js(__('Converting to MP3 :kbps kbps… :percent%')).replace(':kbps', TARGET_KBPS).replace(':percent', Math.min(100, Math.round(end / left.length * 100)));
                        // Lets the page update between chunks.
                        await new Promise(resolve => setTimeout(resolve));
                    }
                    const last = encoder.flush();
                    if (last.length) parts.push(new Int8Array(last));

                    const mp3 = new File(parts, file.name.replace(/\.[^.]+$/, '') + '.mp3', { type: 'audio/mpeg' });
                    if (mp3.size >= file.size) {
                        this.status = @js(__('The file is already light: it will be sent as it is.'));
                        return;
                    }
                    const files = new DataTransfer();
                    files.items.add(mp3);
                    input.files = files.files;
                    this.status = @js(__('Converted to MP3 :kbps kbps: :before MB → :after MB.'))
                        .replace(':kbps', TARGET_KBPS).replace(':before', megabytes(file.size)).replace(':after', megabytes(mp3.size));
                } catch (error) {
                    this.status = @js(__('The file could not be converted: it will be sent as it is.'));
                } finally {
                    this.converting = false;
                }
            },
        };
    }
</script>

<script src="{{ asset('js/chord-transposer.js') }}?v={{ filemtime(public_path('js/chord-transposer.js')) }}"></script>
<script>
    // Changing the key transposes the chords of the structure and of the chord sheet fields (only the chords:
    // lyrics, names, times and the rest of the JSON stay as they are). Nothing is saved until the form is.
    // A minor key counts as its relative major (see chord-transposer.js): Am -> C changes no chord.
    (function () {
        const select = document.getElementById('musical_key');
        const notice = document.getElementById('key-transpose-notice');
        if (!select || !window.ChordTransposer) return;
        let previous = select.value;

        // Rewrites a JSON field; returns how many chords changed, or null when the field is empty or not valid JSON.
        const transposeField = (id, transpose) => {
            const field = document.getElementById(id);
            if (!field || !field.value.trim()) return null;
            let data;
            try {
                data = JSON.parse(field.value);
            } catch (e) {
                return null;
            }
            const count = transpose(data);
            if (count) field.value = JSON.stringify(data, null, 4);
            return count;
        };

        select.addEventListener('change', () => {
            const from = previous;
            const to = select.value;
            previous = to;
            if (!from || !to || from === to || !ChordTransposer.parseKey(from) || !ChordTransposer.parseKey(to)) return;
            const chord = (name) => ChordTransposer.transposeChord(name, from, to);

            // Structure: each section's "chords" (line breaks "|" kept).
            const inStructure = transposeField('structure', (data) => {
                let count = 0;
                (Array.isArray(data) ? data : []).forEach(section => {
                    if (!section || !Array.isArray(section.chords)) return;
                    section.chords = section.chords.map(name => {
                        if (typeof name !== 'string' || name === '|') return name;
                        const moved = chord(name);
                        if (moved !== name) count++;
                        return moved;
                    });
                });
                return count;
            });

            // Chord sheet: the [Chord] marks of each line ("{c: ...}" notes untouched).
            const inSheet = transposeField('chord_sheet', (data) => {
                let count = 0;
                (Array.isArray(data?.sections) ? data.sections : []).forEach(section => {
                    if (!Array.isArray(section?.lines)) return;
                    section.lines = section.lines.map(line => {
                        if (typeof line !== 'string' || /^\s*\{/.test(line)) return line;
                        return line.replace(/\[([^\]]+)\]/g, (match, name) => {
                            const moved = chord(name);
                            if (moved !== name) count++;
                            return `[${moved}]`;
                        });
                    });
                });
                return count;
            });

            notice.style.display = '';
            if (!inStructure && !inSheet) {
                // Relative keys (Am / C): same scale, only the key name changes.
                notice.textContent = ChordTransposer.parseKey(from).minor !== ChordTransposer.parseKey(to).minor
                    ? @js(__(':from and :to are relative keys (same chords): only the key changes.')).replace(':from', from).replace(':to', to)
                    : '';
                notice.style.display = notice.textContent ? '' : 'none';
                return;
            }
            notice.textContent = @js(__('Chords transposed from :from to :to (structure: :structure, chord sheet: :sheet). Review and save.'))
                .replace(':from', from).replace(':to', to)
                .replace(':structure', inStructure ?? 0).replace(':sheet', inSheet ?? 0);
        });
    })();
</script>

<script>
    // Warns, while filling the form, when another song already has this chord sheet address (the server refuses to
    // save it too). Same comparison as FindSongBySourceService: no http/https, "www.", trailing slash, "?" or "#".
    (function () {
        const sources = @js($existingSources ?? []);
        const field = document.getElementById('source_url');
        const notice = document.getElementById('source-duplicate');
        if (!field || !notice) return;

        const normalize = (url) => {
            const text = String(url || '').trim();
            if (!text) return '';
            try {
                const parsed = new URL(/^[a-z][a-z0-9+.-]*:\/\//i.test(text) ? text : 'http://' + text);
                return parsed.hostname.toLowerCase().replace(/^www\./, '') + parsed.pathname.toLowerCase().replace(/\/+$/, '');
            } catch (e) {
                return text.toLowerCase();
            }
        };

        const check = () => {
            const existing = sources[normalize(field.value)];
            notice.replaceChildren();
            notice.style.display = existing ? '' : 'none';
            if (!existing) return;
            notice.append(@js(__('There is already a song with this source:')) + ' "' + existing.title + '" — ');
            const link = document.createElement('a');
            link.href = existing.url;
            link.textContent = @js(__('Open the existing song'));
            link.style.textDecoration = 'underline';
            notice.append(link);
        };

        field.addEventListener('input', check);
        // After the "Send to ICD" import has filled the fields (it runs on the same event, registered before).
        document.addEventListener('alpine:initialized', () => setTimeout(check, 0));
        check();
    })();
</script>
