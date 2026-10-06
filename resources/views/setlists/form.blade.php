<div class="space-y-6">

    <div>
        <x-input-label for="title" :value="__('Title')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $setlist?->title)" required />
        <x-input-error class="mt-2" :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="event_date" :value="__('Event Date')" />
        <x-text-input id="event_date" name="event_date" type="date" class="mt-1 block w-full" :value="old('event_date', $setlist?->event_date?->format('Y-m-d'))" />
        <x-input-error class="mt-2" :messages="$errors->get('event_date')" />
    </div>

    <div x-data="{
            songs: @js($selectedSongs),
            keyOptions: @js($keyOptions),
            errors: @js($errors->getMessages()),
            searchUrl: @js(route('setlists.songs.search')),
            term: '',
            results: [],
            searching: false,
            open: false,
            async search() {
                this.searching = true;
                try {
                    const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(this.term)}`, { headers: { Accept: 'application/json' } });
                    this.results = await response.json();
                } finally {
                    this.searching = false;
                }
            },
            isAdded(song) { return this.songs.some(selected => selected.id === song.id); },
            add(song) {
                if (!this.isAdded(song)) {
                    // New songs start with their original key; the minister is filled in by the user.
                    this.songs.push({ id: song.id, title: song.title, artist: song.artist, original_key: song.musical_key, musical_key: song.musical_key, minister_name: '' });
                }
                this.term = '';
                this.results = [];
                this.open = false;
                this.errors = {};
            },
            remove(index) { this.songs.splice(index, 1); this.errors = {}; },
            move(index, offset) {
                const target = index + offset;
                if (target < 0 || target >= this.songs.length) return;
                this.songs.splice(target, 0, this.songs.splice(index, 1)[0]);
                this.errors = {};
            },
            // Only keys of the song's mode (major/minor), based on its original key.
            keyGroups(song) {
                const base = song.original_key || song.musical_key;
                if (!base) return this.keyOptions;
                const minor = base.endsWith('m');
                return Object.fromEntries(Object.entries(this.keyOptions)
                    .map(([group, keys]) => [group, keys.filter(key => key.endsWith('m') === minor)])
                    .filter(([, keys]) => keys.length));
            },
            extraKeys(song) {
                const listed = Object.values(this.keyGroups(song)).flat();
                return [...new Set([song.musical_key, song.original_key])].filter(key => key && !listed.includes(key));
            },
            error(index, field) { return (this.errors[`songs.${index}.${field}`] || [])[0]; },
            openSearch() { this.open = true; this.search(); this.$nextTick(() => this.$refs.search.focus()); },
        }" @click.outside="open = false">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Songs') }}</h3>
        <x-input-error class="mt-2" :messages="$errors->get('songs')" />

        <div class="relative mt-3">
            <input type="search" x-ref="search" x-model="term" @input.debounce.300ms="search()" @focus="openSearch()"
                @keydown.escape="open = false" placeholder="{{ __('Search song...') }}" autocomplete="off"
                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />

            <div x-show="open" class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg overflow-auto" style="display: none; max-height: 16rem;">
                <template x-for="song in results" :key="song.id">
                    <button type="button" @click="add(song)" :disabled="isAdded(song)"
                        class="block w-full text-left px-4 py-2 hover:bg-gray-100"
                        :class="isAdded(song) ? 'cursor-default' : ''" :style="isAdded(song) ? 'opacity: .5' : ''">
                        <span class="block text-sm font-semibold text-gray-900" x-text="song.title"></span>
                        <span class="block text-sm text-gray-500" x-text="song.artist"></span>
                        <span class="block text-xs text-gray-400" x-show="isAdded(song)">{{ __('Already in the setlist') }}</span>
                    </button>
                </template>
                <p class="px-4 py-2 text-sm text-gray-500" x-show="!searching && results.length === 0">{{ __('No songs found.') }}</p>
            </div>
        </div>

        <ol class="mt-3 space-y-3" x-show="songs.length > 0">
            <template x-for="(song, index) in songs" :key="song.id">
                <li class="rounded-md border border-gray-300 p-4">
                    <input type="hidden" :name="`songs[${index}][song_id]`" :value="song.id">

                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <span class="block text-sm font-semibold text-gray-900" x-text="`${index + 1}. ${song.title}`"></span>
                            <span class="block text-sm text-gray-500" x-text="song.artist + (song.original_key ? ` · {{ __('Original key') }}: ${song.original_key}` : '')"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-secondary-button type="button" @click="move(index, -1)" x-bind:disabled="index === 0" :title="__('Move up')"><i class="fa-solid fa-arrow-up"></i></x-secondary-button>
                            <x-secondary-button type="button" @click="move(index, 1)" x-bind:disabled="index === songs.length - 1" :title="__('Move down')"><i class="fa-solid fa-arrow-down"></i></x-secondary-button>
                        </div>
                    </div>

                    <div class="mt-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <div class="sm:col-span-2">
                            <label class="block font-medium text-sm text-gray-700" :for="`songs_${index}_minister_name`">{{ __('Minister') }}</label>
                            <input type="text" :id="`songs_${index}_minister_name`" :name="`songs[${index}][minister_name]`" x-model="song.minister_name" maxlength="255"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" />
                            <p class="text-sm text-red-600 mt-2" x-show="error(index, 'minister_name')" x-text="error(index, 'minister_name')"></p>
                        </div>
                        <div class="mt-3 sm:mt-0">
                            <label class="block font-medium text-sm text-gray-700" :for="`songs_${index}_musical_key`">{{ __('Key') }}</label>
                            <select :id="`songs_${index}_musical_key`" :name="`songs[${index}][musical_key]`" x-model="song.musical_key"
                                class="block w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 mt-1">
                                <option value="">{{ __('Select') }}</option>
                                <template x-for="key in extraKeys(song)" :key="'extra-' + key">
                                    <option :value="key" x-text="key" :selected="key === song.musical_key"></option>
                                </template>
                                <template x-for="(keys, group) in keyGroups(song)" :key="group">
                                    <optgroup :label="group">
                                        <template x-for="key in keys" :key="key">
                                            <option :value="key" x-text="key" :selected="key === song.musical_key"></option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>
                            <p class="text-sm text-red-600 mt-2" x-show="error(index, 'musical_key')" x-text="error(index, 'musical_key')"></p>
                        </div>
                    </div>

                    <p class="text-sm text-red-600 mt-2" x-show="error(index, 'song_id')" x-text="error(index, 'song_id')"></p>

                    <div class="mt-3 flex justify-end">
                        <x-danger-button type="button" @click="remove(index)">{{ __('Remove') }}</x-danger-button>
                    </div>
                </li>
            </template>
        </ol>
        <p class="mt-3 text-sm text-gray-500" x-show="songs.length === 0">{{ __('No songs in this setlist yet.') }}</p>

        <div class="mt-4">
            <x-secondary-button type="button" @click.stop="openSearch()">+ {{ __('Add song') }}</x-secondary-button>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</div>
