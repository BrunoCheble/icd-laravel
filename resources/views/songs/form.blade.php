@php
    $videoLessons = session()->hasOldInput()
        ? array_values(old('video_lesson', []))
        : ($song->video_lesson ?? []);
@endphp

<div class="space-y-6">

    {{-- Filled by the "Send to ICD" bookmarklet import (see the script at the end of this form) --}}
    <div id="import-notice" class="rounded-md border border-gray-300 bg-gray-50 p-4 text-sm text-gray-700" style="display: none;"></div>

    <div>
        <x-input-label for="title" :value="__('Title')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $song?->title)" required />
        <x-input-error class="mt-2" :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="artist" :value="__('Artist')" />
        <x-text-input id="artist" name="artist" type="text" class="mt-1 block w-full" :value="old('artist', $song?->artist)" required />
        <x-input-error class="mt-2" :messages="$errors->get('artist')" />
    </div>

    <div>
        <x-input-label for="musical_key" :value="__('Key')" />
        @php
            // One option per pair of relative keys ("C / Am"), storing the key of the song's mode (new songs: major).
            $selectedKey = old('musical_key', $song?->musical_key);
            $minorSong = \App\Enums\MusicalKey::isMinor($selectedKey);
            $isListedKey = collect($keyOptions)->contains(fn ($pair) => $pair[$minorSong ? 'minor' : 'major'] === $selectedKey);
        @endphp
        <select id="musical_key" name="musical_key"
            class="block w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 mt-1">
            <option value="">{{ __('Select') }}</option>
            @if ($selectedKey && ! $isListedKey)
                <option value="{{ $selectedKey }}" selected>{{ $selectedKey }}</option>
            @endif
            @foreach ($keyOptions as $pair)
                <option value="{{ $pair[$minorSong ? 'minor' : 'major'] }}" @selected($pair[$minorSong ? 'minor' : 'major'] === $selectedKey)>{{ $pair['label'] }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('musical_key')" />
        <p id="key-transpose-notice" class="mt-2 text-sm" style="display: none; color: #4338ca;"></p>
    </div>

    {{-- Tempo: the live mode of the setlist app counts the bars with it --}}
    <div>
        <x-input-label for="bpm" :value="__('BPM')" />
        <x-text-input id="bpm" name="bpm" type="number" step="0.1" min="30" max="300" class="mt-1 block w-full" :value="old('bpm', $song?->bpm)" />
        <p class="mt-1 text-sm text-gray-500">{{ __('Beats per minute. Filled in by the study app (icd-chords) when it saves the map; the live mode of the setlist follows it.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('bpm')" />
    </div>

    <div>
        <x-input-label for="youtube_url" :value="__('YouTube')" />
        <x-text-input id="youtube_url" name="youtube_url" type="url" class="mt-1 block w-full" :value="old('youtube_url', $song?->youtube_url)" />
        <x-input-error class="mt-2" :messages="$errors->get('youtube_url')" />
    </div>

    {{-- Audio file: played instead of the YouTube video in the setlist app, also offline --}}
    <div x-data="songAudioField()">
        <x-input-label for="audio" :value="__('Audio (MP3)')" />
        @if ($song?->audioUrl())
            <div class="mt-1 flex flex-wrap items-center gap-3" x-show="!remove">
                <audio controls preload="none" src="{{ $song->audioUrl() }}" style="max-width: 100%;"></audio>
                <button type="button" class="text-sm font-semibold" style="color: #dc2626;" @click="remove = true"><i class="fa-solid fa-trash"></i> {{ __('Remove audio') }}</button>
            </div>
            <p class="mt-1 text-sm" style="color: #dc2626;" x-show="remove" x-cloak>
                {{ __('The audio will be removed when you save.') }}
                <button type="button" class="font-semibold text-indigo-600" @click="remove = false">{{ __('Undo') }}</button>
            </p>
            <input type="hidden" name="remove_audio" :value="remove ? 1 : 0">
        @endif
        <input id="audio" name="audio" type="file" accept=".mp3,.m4a,.aac,.ogg,.wav,audio/*" class="mt-2 block w-full text-sm" @change="convert($event.target)" :disabled="converting">
        <p class="mt-1 text-sm font-semibold" style="color: #4338ca;" x-show="status" x-text="status" x-cloak></p>
        <p class="mt-1 text-sm text-gray-500">{{ __('Up to :size MB. When the song has an audio file, the setlist app plays it instead of the YouTube video, also offline.', ['size' => \App\Services\SaveSongAudioService::maxUploadMegabytes()]) }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('audio')" />
    </div>

    <div>
        <x-input-label for="source_url" :value="__('Source')" />
        <x-text-input id="source_url" name="source_url" type="url" class="mt-1 block w-full" :value="old('source_url', $song?->source_url)" />
        <x-input-error class="mt-2" :messages="$errors->get('source_url')" />
        <p id="source-duplicate" class="mt-2 text-sm font-semibold" style="display: none; color: #b45309;"></p>
    </div>

    <div x-data="{
            lessons: @js($videoLessons),
            instruments: @js($instrumentOptions),
            errors: @js($errors->getMessages()),
            add() { this.lessons.push({ instrument: '', title: '', link: '' }); },
            remove(index) { this.lessons.splice(index, 1); this.errors = {}; },
            error(index, field) { return (this.errors[`video_lesson.${index}.${field}`] || [])[0]; },
        }">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Video Lessons') }}</h3>
        <x-input-error class="mt-2" :messages="$errors->get('video_lesson')" />

        <div class="mt-3 space-y-4">
            <template x-for="(lesson, index) in lessons" :key="index">
                <div class="rounded-md border border-gray-300 p-4 space-y-4">
                    <div>
                        <label class="block font-medium text-sm text-gray-700" :for="`video_lesson_${index}_instrument`">{{ __('Instrument') }}</label>
                        <select :id="`video_lesson_${index}_instrument`" :name="`video_lesson[${index}][instrument]`" x-model="lesson.instrument" required
                            class="block w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 mt-1">
                            <option value="">{{ __('Select') }}</option>
                            <template x-if="lesson.instrument && !(lesson.instrument in instruments)">
                                <option :value="lesson.instrument" x-text="lesson.instrument"></option>
                            </template>
                            <template x-for="(label, value) in instruments" :key="value">
                                <option :value="value" x-text="label" :selected="value === lesson.instrument"></option>
                            </template>
                        </select>
                        <p class="text-sm text-red-600 mt-2" x-show="error(index, 'instrument')" x-text="error(index, 'instrument')"></p>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700" :for="`video_lesson_${index}_title`">{{ __('Title') }}</label>
                        <input type="text" :id="`video_lesson_${index}_title`" :name="`video_lesson[${index}][title]`" x-model="lesson.title" required
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" />
                        <p class="text-sm text-red-600 mt-2" x-show="error(index, 'title')" x-text="error(index, 'title')"></p>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700" :for="`video_lesson_${index}_link`">{{ __('Link') }}</label>
                        <input type="url" :id="`video_lesson_${index}_link`" :name="`video_lesson[${index}][link]`" x-model="lesson.link" required
                            placeholder="https://youtube.com/..."
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" />
                        <p class="text-sm text-red-600 mt-2" x-show="error(index, 'link')" x-text="error(index, 'link')"></p>
                    </div>

                    <div class="flex justify-end">
                        <x-danger-button type="button" @click="remove(index)">{{ __('Remove') }}</x-danger-button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-4">
            <x-secondary-button type="button" @click="add()">+ {{ __('Add video lesson') }}</x-secondary-button>
        </div>
    </div>

    <div>
        <x-input-label for="structure" :value="__('Structure')" />
        <p class="mt-1 text-sm text-gray-500">{{ __('JSON generated by the Música project. It is saved exactly as entered, as long as it is valid JSON.') }}</p>
        <textarea id="structure" name="structure" rows="20" spellcheck="false" required
            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">{{ old('structure', $song->exists ? $song->structureAsJson() : '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('structure')" />
    </div>

    <div x-data="chordSheetImport(@js(route('songs.chord-sheet.parse')), @js(route('songs.chord-sheet.structure')))">
        <x-input-label for="chord_sheet_source" :value="__('Chord sheet (optional)')" />
        <p class="mt-1 text-sm text-gray-500">{{ __('Paste the HTML of the chord sheet <pre> or its text. It becomes a separate chord sheet: the structure above is not changed.') }}</p>
        <textarea id="chord_sheet_source" x-model="source" rows="5" spellcheck="false"
            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;"
            placeholder="<pre>…</pre>"></textarea>
        <div class="mt-2 flex items-center gap-4">
            <x-secondary-button type="button" @click="parse()" x-bind:disabled="parsing || !source.trim()">{{ __('Interpret chord sheet') }}</x-secondary-button>
            <span class="text-sm text-gray-600" x-show="message" x-text="message" :class="{ 'text-red-600': failed }"></span>
        </div>

        <x-input-label for="chord_sheet" :value="__('Chord sheet')" class="mt-4" />
        <textarea id="chord_sheet" name="chord_sheet" x-ref="sheet" rows="12" spellcheck="false"
            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">{{ old('chord_sheet', $song->exists ? $song->chordSheetAsJson() : '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('chord_sheet')" />
        <div class="mt-2 flex items-center gap-4">
            <x-secondary-button type="button" @click="toStructure()" x-bind:disabled="converting">{{ __('Generate structure from the chord sheet') }}</x-secondary-button>
        </div>
    </div>

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
                    if (structure.value.trim() && !confirm(@js(__('Replace the current structure with one generated from the chord sheet?')))) return;

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
                            + (this.structureGenerated ? ' ' + @js(__('The structure was generated from the chord sheet.')) : '');

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

    <div class="flex items-center gap-4">
        <x-primary-button>{{ __('Save') }}</x-primary-button>
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

            if (data.sheet) {
                window.dispatchEvent(new CustomEvent('icd-import', { detail: { sheet: data.sheet, chordCount: data.chordCount } }));
            } else {
                missing.push(@js(__('Chord sheet')));
            }

            const notice = document.getElementById('import-notice');
            if (notice) {
                const outdated = data.version !== @js(\App\Http\Controllers\SongChordSheetController::bookmarkletVersion());
                notice.style.display = '';
                notice.textContent = (outdated ? @js(__('Your "Send to ICD" bookmark is outdated: delete it and drag it again from the import page.')) + ' ' : '')
                    + @js(__('Imported from :source. Review the fields and save.')).replace(':source', data.source || '')
                    + (missing.length ? ' ' + @js(__('Not found on the page:')) + ' ' + missing.join(', ') + '.' : '');
                if (outdated) notice.style.borderColor = '#dc2626';
            }
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
