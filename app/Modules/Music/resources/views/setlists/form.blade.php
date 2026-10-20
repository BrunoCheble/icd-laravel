@include('partials.ui')

<style>
    .sf-song { padding: .75rem; border: 1px solid var(--bd-e5e7eb); border-radius: .75rem; background: var(--bg-fff); }
    .sf-song-head { display: flex; align-items: center; gap: .6rem; }
    .sf-n { flex: none; width: 1.75rem; height: 1.75rem; display: grid; place-items: center; border-radius: 999px; background: var(--brand-soft); color: var(--brand-strong); font-size: .8rem; font-weight: 800; }
    .sf-song-main { flex: 1; min-width: 0; display: grid; }
    .sf-song-main strong { color: var(--tx-111827); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sf-song-main small { font-size: .8rem; color: var(--tx-6b7280); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sf-song-tools { display: flex; gap: .15rem; flex: none; }
    .sf-song-fields { display: grid; grid-template-columns: minmax(0, 1fr) 9rem; gap: .6rem; margin-top: .65rem; }
    @media (max-width: 479px) { .sf-song-fields { grid-template-columns: minmax(0, 1fr) 7rem; } }
    .sf-results { position: absolute; z-index: 10; left: 0; right: 0; margin-top: .3rem; max-height: 16rem; overflow: auto; border: 1px solid var(--bd-e5e7eb); border-radius: .75rem; background: var(--bg-fff); box-shadow: 0 12px 30px rgba(0, 0, 0, .12); }
    .sf-results button { display: block; width: 100%; padding: .55rem .85rem; border: 0; border-bottom: 1px solid var(--bd-f3f4f6); background: none; text-align: left; cursor: pointer; }
    .sf-results button:hover:not(:disabled) { background: var(--brand-soft); }
</style>

<div class="m-form">

    <section class="m-card m-section">
        <h2 class="m-section-title"><i class="fa-solid fa-calendar-day"></i> {{ __('Setlist') }}</h2>
        <div class="m-grid cols-2">
            <div class="m-field">
                <label class="m-label" for="title">{{ __('Title') }}</label>
                <input id="title" name="title" type="text" class="m-input" value="{{ old('title', $setlist?->title) }}" required placeholder="{{ __('E.g. Sunday service') }}">
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div class="m-field">
                <label class="m-label" for="event_date">{{ __('Event Date') }}</label>
                <input id="event_date" name="event_date" type="date" class="m-input" value="{{ old('event_date', $setlist?->event_date?->format('Y-m-d')) }}">
                <x-input-error :messages="$errors->get('event_date')" />
            </div>
        </div>
    </section>

    <section class="m-card m-section" x-data="{
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
            // One option per pair of relative keys (C / Am), storing the key of the song's mode (major/minor),
            // based on its original key.
            keyChoices(song) {
                const base = song.original_key || song.musical_key;
                const minor = !!base && base.endsWith('m');
                return this.keyOptions.map(pair => ({ value: minor ? pair.minor : pair.major, label: pair.label }));
            },
            extraKeys(song) {
                const listed = this.keyChoices(song).map(choice => choice.value);
                return [...new Set([song.musical_key, song.original_key])].filter(key => key && !listed.includes(key));
            },
            error(index, field) { return (this.errors[`songs.${index}.${field}`] || [])[0]; },
            openSearch() { this.open = true; this.search(); this.$nextTick(() => this.$refs.search.focus()); },
        }" @click.outside="open = false">
        <h2 class="m-section-title"><i class="fa-solid fa-music"></i> {{ __('Songs') }} <small x-text="songs.length"></small></h2>
        <x-input-error :messages="$errors->get('songs')" />

        <div class="relative">
            <label class="m-search" style="display: block;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" x-ref="search" x-model="term" @input.debounce.300ms="search()" @focus="openSearch()"
                    @keydown.escape="open = false" placeholder="{{ __('Search song...') }}" autocomplete="off" aria-label="{{ __('Add song') }}">
            </label>

            <div x-show="open" class="sf-results" style="display: none;">
                <template x-for="song in results" :key="song.id">
                    <button type="button" @click="add(song)" :disabled="isAdded(song)" :style="isAdded(song) ? 'opacity: .5; cursor: default;' : ''">
                        <span class="block text-sm font-semibold text-gray-900" x-text="song.title"></span>
                        <span class="block text-xs text-gray-500" x-text="song.artist + (isAdded(song) ? ' · ' + @js(__('Already in the setlist')) : '')"></span>
                    </button>
                </template>
                <p class="px-4 py-3 text-sm text-gray-500" x-show="!searching && results.length === 0">{{ __('No songs found.') }}</p>
            </div>
        </div>

        <ol class="mt-3 grid gap-2" x-show="songs.length > 0">
            <template x-for="(song, index) in songs" :key="song.id">
                <li class="sf-song">
                    <input type="hidden" :name="`songs[${index}][song_id]`" :value="song.id">

                    <div class="sf-song-head">
                        <span class="sf-n" x-text="index + 1"></span>
                        <div class="sf-song-main">
                            <strong x-text="song.title"></strong>
                            <small x-text="song.artist + (song.original_key ? ` · {{ __('Original key') }}: ${song.original_key}` : '')"></small>
                        </div>
                        <div class="sf-song-tools">
                            <button type="button" class="m-btn is-ghost is-icon" @click="move(index, -1)" :disabled="index === 0" title="{{ __('Move up') }}" aria-label="{{ __('Move up') }}"><i class="fa-solid fa-arrow-up"></i></button>
                            <button type="button" class="m-btn is-ghost is-icon" @click="move(index, 1)" :disabled="index === songs.length - 1" title="{{ __('Move down') }}" aria-label="{{ __('Move down') }}"><i class="fa-solid fa-arrow-down"></i></button>
                            <button type="button" class="m-btn is-ghost is-icon" style="color: var(--tx-dc2626);" @click="remove(index)" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>

                    <div class="sf-song-fields">
                        <div class="m-field">
                            <label class="m-label" :for="`songs_${index}_minister_name`">{{ __('Minister') }}</label>
                            <input type="text" :id="`songs_${index}_minister_name`" :name="`songs[${index}][minister_name]`" x-model="song.minister_name" maxlength="255" class="m-input">
                            <p class="m-error" x-show="error(index, 'minister_name')" x-text="error(index, 'minister_name')"></p>
                        </div>
                        <div class="m-field">
                            <label class="m-label" :for="`songs_${index}_musical_key`">{{ __('Key') }}</label>
                            <select :id="`songs_${index}_musical_key`" :name="`songs[${index}][musical_key]`" x-model="song.musical_key" class="m-input">
                                <option value="">{{ __('Select') }}</option>
                                <template x-for="key in extraKeys(song)" :key="'extra-' + key">
                                    <option :value="key" x-text="key" :selected="key === song.musical_key"></option>
                                </template>
                                <template x-for="choice in keyChoices(song)" :key="choice.value">
                                    <option :value="choice.value" x-text="choice.label" :selected="choice.value === song.musical_key"></option>
                                </template>
                            </select>
                            <p class="m-error" x-show="error(index, 'musical_key')" x-text="error(index, 'musical_key')"></p>
                        </div>
                    </div>
                    <p class="m-error mt-2" x-show="error(index, 'song_id')" x-text="error(index, 'song_id')"></p>
                </li>
            </template>
        </ol>
        <p class="mt-3 m-help" x-show="songs.length === 0">{{ __('No songs in this setlist yet.') }} {{ __('Search above to add them.') }}</p>
    </section>

    <div class="m-form-actions">
        <a href="{{ route('setlists.index') }}" class="m-btn is-ghost">{{ __('Cancel') }}</a>
        <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
    </div>
</div>
