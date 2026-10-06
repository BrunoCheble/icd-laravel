<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Section times') }} · {{ $song->title }}
        </h2>
    </x-slot>

    <style>
        .timing-row { cursor: pointer; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem; padding: .5rem; border: 1px solid #d1d5db; border-radius: .5rem; }
        .timing-row.is-current { border-color: #4f46e5; box-shadow: inset 4px 0 0 #4f46e5; background: #eef2ff; }
        .timing-mark { flex: 1 1 14rem; min-height: 3.25rem; text-align: left; padding: .5rem .75rem; border-radius: .5rem; background: #f3f4f6; border: 1px solid #e5e7eb; cursor: pointer; }
        .timing-mark:hover { background: #e5e7eb; }
        .timing-mark:active { background: #c7d2fe; }
        .timing-controls { display: flex; align-items: center; gap: .35rem; }
        .timing-input { width: 5.5rem; text-align: center; font-variant-numeric: tabular-nums; }
        .timing-btn { min-width: 2.75rem; min-height: 2.5rem; padding: 0 .5rem; border: 1px solid #d1d5db; border-radius: .375rem; background: #fff; font-weight: 600; font-size: .85rem; }
        .timing-btn:hover { background: #f9fafb; }
        .timing-lyrics { flex-basis: 100%; padding: 0 .75rem .25rem; font-size: .9rem; line-height: 1.45; color: #374151; white-space: pre-line; }
        .timing-row:not(.is-current) .timing-lyrics { color: #6b7280; }
        .timing-chords { display: block; }
        /* The block button may shrink below its text (long chord lines), so it never widens the page. */
        .timing-mark { min-width: 0; max-width: 100%; overflow: hidden; }
        /* Phones: one line of chords and smaller controls, so each block stays short */
        @media (max-width: 639px) {
            .timing-row { padding: .4rem; gap: .4rem; }
            .timing-mark { flex-basis: 100%; min-height: 0; padding: .4rem .6rem; }
            .timing-chords { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .timing-controls { width: 100%; gap: .25rem; }
            .timing-btn { flex: 1; min-width: 0; min-height: 2.25rem; padding: 0 .25rem; }
            .timing-input { width: 4.25rem; flex: 0 0 auto; padding-left: .25rem; padding-right: .25rem; }
            .timing-lyrics { padding: 0 .4rem .2rem; }
        }
        .timing-link { margin-top: .15rem; font-size: .8rem; font-weight: 600; color: #4f46e5; background: none; border: 0; padding: 0; cursor: pointer; }
    </style>

    @include('songs.partials.workspace')

    <div class="ws-page">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="ws-card bg-white shadow sm:rounded-lg">
                @include('songs.partials.tabs', ['song' => $song, 'current' => 'timing'])
                @include('layouts.alert')

                <div class="ws-desktop">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">{{ $song->title }}</h1>
                    <p class="mt-2 text-sm text-gray-700">{{ __('Play the song and tap each block when it starts. Times can be adjusted before saving.') }}</p>
                </div>

                @if ($sections->isEmpty())
                    <p class="mt-6 text-sm text-gray-500">{{ __('No structure available for this song.') }}</p>
                @else
                    <form id="timing-form" method="POST" action="{{ route('songs.timing.update', $song) }}" class="mt-2 sm:mt-6 max-w-3xl space-y-3"
                        x-data="songTiming(@js(['sections' => $sections, 'youtubeUrl' => $song->youtube_url]))">
                        @csrf
                        @method('PUT')

                        {{-- Toolbar: clock, restart, save, options --}}
                        <div class="ws-toolbar">
                            @include('songs.partials.play-button', ['toggle' => 'togglePlay()', 'time' => 'format(now)'])
                            <button type="button" class="ws-btn" @click="seek(0)" title="{{ __('Restart') }}" aria-label="{{ __('Restart') }}"><i class="fa-solid fa-rotate-left"></i></button>
                            <span class="ws-spacer ws-note ws-desktop">{{ __('Tap each block when it starts') }}</span>
                            <span class="ws-spacer ws-mobile"></span>
                            <button type="submit" class="ws-btn ws-btn-primary" title="{{ __('Save') }}"><i class="fa-solid fa-floppy-disk"></i><span class="ws-label">{{ __('Save') }}</span></button>
                            <button type="button" class="ws-btn" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" title="{{ __('Options') }}" aria-label="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
                        </div>

                        <x-input-error :messages="collect($errors->get('starts.*'))->flatten()->unique()->all()" />

                        <div class="space-y-3">
                            <template x-for="(section, index) in sections" :key="index">
                                <div class="timing-row" :class="{ 'is-current': index === currentIndex }" @click="mark(index)" :title="@js(__('Mark the start of this block now'))">
                                    <button type="button" class="timing-mark">
                                        <span class="block text-sm font-semibold text-gray-900" x-text="section.name"></span>
                                        <span class="block text-sm italic text-gray-500" x-show="section.anchor && !section.lyrics" x-text="section.anchor"></span>
                                        <span class="timing-chords text-sm text-gray-600" x-text="section.chords"></span>
                                    </button>
                                    <div class="timing-controls" @click.stop>
                                        <button type="button" class="timing-btn" @click="nudge(index, -1)" :aria-label="@js(__('Minus one second'))">−1s</button>
                                        <input type="text" inputmode="numeric" class="timing-input border-gray-300 rounded-md shadow-sm" placeholder="m:ss"
                                            :name="`starts[${index}]`" x-model="section.start" :aria-label="`{{ __('Start') }}: ${section.name}`">
                                        <button type="button" class="timing-btn" @click="nudge(index, 1)" :aria-label="@js(__('Plus one second'))">+1s</button>
                                        <button type="button" class="timing-btn" @click="playFrom(index)" :disabled="parse(section.start) === null" :title="@js(__('Play from here'))" :aria-label="@js(__('Play from here'))"><i class="fa-solid fa-play"></i></button>
                                        <button type="button" class="timing-btn" @click="section.start = ''" :title="@js(__('Clear'))" :aria-label="@js(__('Clear'))"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                    <div class="timing-lyrics" x-show="section.lyrics">
                                        <div x-text="lyricsExpanded(index) ? section.lyrics : section.lyrics.split('\n')[0]"></div>
                                        <button type="button" class="timing-link" x-show="section.lyrics.includes('\n')" @click.stop="toggleLyrics(index)"
                                            x-text="lyricsExpanded(index) ? @js(__('Hide lyrics')) : @js(__('Show lyrics'))"></button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>

                        <x-song-options>
                            <div class="ws-panel-section">
                                <div class="ws-panel-label"><i class="fa-solid fa-circle-info"></i> {{ __('How it works') }}</div>
                                <p class="ws-panel-help">{{ __('Play the song and tap each block when it starts. Times can be adjusted before saving.') }}</p>
                                <p class="ws-panel-help mt-2" x-show="!videoId">{{ __('This song has no YouTube video: use the stopwatch below.') }}</p>
                            </div>
                            @include('songs.partials.video-options')
                        </x-song-options>

                        @include('songs.partials.youtube-mini', ['id' => 'yt-timing'])
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Clock for marking section starts: the YouTube video time when the song has a video, otherwise a stopwatch.
        function songTiming(config) {
            return withYoutubeMini({
                sections: config.sections,
                // Stopwatch (songs without a video); with a video, "playing" follows the player.
                playing: false,
                optionsOpen: false,
                stopwatchStartedAt: 0,
                stopwatchElapsed: 0,
                now: 0,

                init() {
                    this.$nextTick(() => this.initVideo('yt-timing'));
                    setInterval(() => this.now = this.time(), 200);
                },
                get usingVideo() { return this.playerReady; },

                time() {
                    if (this.usingVideo) return this.videoCurrentTime();
                    return this.playing ? (performance.now() - this.stopwatchStartedAt) / 1000 : this.stopwatchElapsed;
                },
                togglePlay() {
                    if (this.usingVideo) return this.toggleVideo();
                    if (this.playing) {
                        this.stopwatchElapsed = this.time();
                        this.playing = false;
                    } else {
                        this.stopwatchStartedAt = performance.now() - this.stopwatchElapsed * 1000;
                        this.playing = true;
                    }
                },
                seek(seconds) {
                    if (this.usingVideo) return this.seekVideo(seconds);
                    this.stopwatchElapsed = seconds;
                    this.stopwatchStartedAt = performance.now() - seconds * 1000;
                    this.now = seconds;
                },

                // Lyrics open by themselves up to the next block that has lyrics and never close by themselves,
                // so the list above the block being marked does not move; any block can be opened/closed by hand.
                lyricsToggled: {},
                get nextLyricsIndex() {
                    const from = (this.currentIndex ?? -1) + 1;
                    const index = this.sections.findIndex((section, i) => i >= from && section.lyrics);
                    return index === -1 ? null : index;
                },
                lyricsExpanded(index) {
                    if (index in this.lyricsToggled) return this.lyricsToggled[index];
                    return index <= Math.max(this.currentIndex ?? -1, this.nextLyricsIndex ?? -1);
                },
                toggleLyrics(index) {
                    this.lyricsToggled = { ...this.lyricsToggled, [index]: !this.lyricsExpanded(index) };
                },

                mark(index) { this.sections[index].start = this.format(this.time()); },
                nudge(index, delta) {
                    const current = this.parse(this.sections[index].start) ?? this.time();
                    this.sections[index].start = this.format(Math.max(0, current + delta));
                },
                playFrom(index) {
                    const start = this.parse(this.sections[index].start);
                    if (start === null) return;
                    this.seek(start);
                    if (!this.usingVideo && !this.playing) this.togglePlay();
                },

                // Section being played: the one with the latest start not after the current time.
                get currentIndex() {
                    let found = null;
                    this.sections.forEach((section, index) => {
                        const start = this.parse(section.start);
                        if (start !== null && start <= this.now && (found === null || start >= this.parse(this.sections[found].start))) found = index;
                    });
                    return found;
                },

                parse(value) {
                    if (typeof value !== 'string' || !/^\d+(:\d{1,2}){0,2}(\.\d+)?$/.test(value.trim())) return null;
                    return value.trim().split(':').reduce((total, part) => total * 60 + Number(part), 0);
                },
                format(seconds) {
                    const total = Math.max(0, Math.round(seconds));
                    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
                },
            }, config.youtubeUrl, { captions: true });
        }
    </script>
</x-app-layout>
