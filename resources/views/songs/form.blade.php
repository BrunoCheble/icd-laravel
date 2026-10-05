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
            $selectedKey = old('musical_key', $song?->musical_key);
            $isListedKey = collect($keyOptions)->flatten()->contains($selectedKey);
        @endphp
        <select id="musical_key" name="musical_key"
            class="block w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 mt-1">
            <option value="">{{ __('Select') }}</option>
            @if ($selectedKey && ! $isListedKey)
                <option value="{{ $selectedKey }}" selected>{{ $selectedKey }}</option>
            @endif
            @foreach ($keyOptions as $group => $keys)
                <optgroup label="{{ $group }}">
                    @foreach ($keys as $key)
                        <option value="{{ $key }}" @selected($key === $selectedKey)>{{ $key }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('musical_key')" />
    </div>

    <div>
        <x-input-label for="youtube_url" :value="__('YouTube')" />
        <x-text-input id="youtube_url" name="youtube_url" type="url" class="mt-1 block w-full" :value="old('youtube_url', $song?->youtube_url)" />
        <x-input-error class="mt-2" :messages="$errors->get('youtube_url')" />
    </div>

    <div>
        <x-input-label for="source_url" :value="__('Source')" />
        <x-text-input id="source_url" name="source_url" type="url" class="mt-1 block w-full" :value="old('source_url', $song?->source_url)" />
        <x-input-error class="mt-2" :messages="$errors->get('source_url')" />
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

