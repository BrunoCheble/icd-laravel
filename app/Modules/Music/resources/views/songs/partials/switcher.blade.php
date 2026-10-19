{{-- The song's title in the top bar, which opens a picker to go to another song on the same tab: search, the songs
     opened lately (on this device) and those of the next setlist. Ctrl/⌘+K opens it.
     $song (model), $current (tab: show, edit, sheet, layout, practice, lessons, history), $label (the tab's name),
     $suffix (optional, after the title) --}}
@include('partials.ui')
@php
    // The song as a model, or as the practice page has it (an array).
    $songInfo = is_array($song)
        ? ['id' => $song['id'], 'title' => $song['title'], 'artist' => $song['artist'] ?? '', 'key' => $song['original_key'] ?? null]
        : ['id' => $song->id, 'title' => $song->title, 'artist' => $song->artist, 'key' => $song->musical_key];
    $tabRoutes = [
        'show' => 'songs.show', 'edit' => 'songs.edit', 'sheet' => 'songs.sheet.edit', 'layout' => 'songs.layout.edit',
        'practice' => 'songs.practice', 'lessons' => 'songs.lessons.index', 'history' => 'songs.history',
    ];
    $switcher = [
        'url' => route($tabRoutes[$current] ?? 'songs.show', ['song' => '__SONG__']),
        'search' => route('setlists.songs.search'),
        'song' => $songInfo,
        'setlist' => $switcherSetlist ?? null,
    ];
@endphp
<div class="song-switcher" x-data="songSwitcher(@js($switcher))" @keydown.window.prevent.ctrl.k="show()" @keydown.window.prevent.meta.k="show()">
    <button type="button" class="song-switcher-button" @click="show()" title="{{ __('Change song') }} (Ctrl/⌘ K)">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if ($label)<span class="song-switcher-tab">{{ $label }} ·</span>@endif
            {{ $songInfo['title'] }}@if (! empty($suffix)) <span class="text-gray-500 font-normal">· {{ $suffix }}</span>@endif
            <i class="fa-solid fa-chevron-down"></i>
        </h2>
    </button>

    <template x-teleport="body">
        <div class="m-sheet" x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('Change song') }}" @keydown.escape="open = false">
            <div class="m-sheet-overlay" @click="open = false"></div>
            <div class="m-sheet-box song-switcher-box" x-show="open" x-transition.opacity>
                <div class="m-sheet-head">
                    <div>
                        <h2>{{ __('Change song') }}</h2>
                        <p>{{ __('Opens on the same tab.') }}</p>
                    </div>
                    <button type="button" class="m-btn is-ghost is-icon" @click="open = false" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <label class="m-search" style="display: block;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" x-ref="term" x-model="term" @input.debounce.200ms="search()" @keydown.enter.prevent="go(list[0])"
                        placeholder="{{ __('Title or artist') }}" autocomplete="off" aria-label="{{ __('Search') }}">
                </label>

                <template x-for="group in groups" :key="group.title">
                    <div class="song-switcher-group">
                        <p class="song-switcher-title" x-text="group.title"></p>
                        <template x-for="item in group.songs" :key="group.title + item.id">
                            <a :href="urlOf(item)" class="song-switcher-item" :class="{ 'is-current': item.id === song.id }" @click="remember(item)">
                                <span class="song-switcher-main">
                                    <strong x-text="item.title"></strong>
                                    <small x-text="item.artist || '—'"></small>
                                </span>
                                <span class="m-tag is-key" x-show="item.key" x-text="item.key"></span>
                            </a>
                        </template>
                    </div>
                </template>
                <p class="m-help mt-3" x-show="term.trim() && !searching && !results.length">{{ __('No songs found.') }}</p>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        .song-switcher-button { display: block; max-width: 100%; padding: .25rem .5rem; margin: -.25rem -.5rem; border: 0; border-radius: .5rem; background: none; text-align: left; cursor: pointer; }
        .song-switcher-button:hover { background: #f3f4f6; }
        .song-switcher-button h2 { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .song-switcher-button i { margin-left: .2rem; font-size: .7em; color: #9ca3af; }
        .song-switcher-tab { font-weight: 600; color: #6b7280; }
        /* Phones: only the song's name (the tab is in the tabs) */
        @media (max-width: 639px) { .song-switcher-tab { display: none; } }
        .song-switcher-box { width: min(34rem, 100%); }
        .song-switcher-group { margin-top: 1rem; }
        .song-switcher-title { margin: 0 0 .35rem; font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #9ca3af; }
        .song-switcher-item { display: flex; align-items: center; gap: .75rem; padding: .55rem .65rem; border-radius: .6rem; color: #111827; text-decoration: none; }
        .song-switcher-item:hover { background: #f9fafb; }
        .song-switcher-item.is-current { background: var(--brand-soft); }
        .song-switcher-main { flex: 1; min-width: 0; display: grid; }
        .song-switcher-main strong { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .song-switcher-main small { font-size: .8rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
    <script>
        // The song picker: lately opened songs (kept on this device), the next setlist's, or the songs found.
        function songSwitcher({ url, search: searchUrl, song, setlist }) {
            const KEY = 'recent-songs';
            let recent = [];
            try { recent = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (error) {}
            recent = [song, ...recent.filter(item => item && item.id !== song.id)].slice(0, 8);
            try { localStorage.setItem(KEY, JSON.stringify(recent)); } catch (error) {}
            return {
                open: false,
                term: '',
                results: [],
                searching: false,
                song,
                show() {
                    this.open = true;
                    this.$nextTick(() => this.$refs.term?.focus());
                },
                urlOf(item) { return url.replace('__SONG__', item.id); },
                remember(item) {
                    const list = [item, ...recent.filter(other => other.id !== item.id)].slice(0, 8);
                    try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (error) {}
                },
                go(item) { if (item) { this.remember(item); location.href = this.urlOf(item); } },
                async search() {
                    const term = this.term.trim();
                    if (!term) { this.results = []; return; }
                    this.searching = true;
                    try {
                        const response = await fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
                        const found = await response.json();
                        if (term === this.term.trim()) this.results = found.map(item => ({ id: item.id, title: item.title, artist: item.artist, key: item.musical_key }));
                    } catch (error) {
                        this.results = [];
                    } finally {
                        this.searching = false;
                    }
                },
                get groups() {
                    if (this.term.trim()) return this.results.length ? [{ title: @js(__('Songs')), songs: this.results }] : [];
                    const groups = [{ title: @js(__('Recent')), songs: recent.filter(item => item.id !== song.id) }];
                    if (setlist) groups.push({ title: `${@js(__('Next setlist'))} · ${setlist.title}`, songs: setlist.songs });
                    return groups.filter(group => group.songs.length);
                },
                // Enter opens the first one shown.
                get list() { return this.groups.flatMap(group => group.songs); },
            };
        }
    </script>
@endonce
