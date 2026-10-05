<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Section times') }} · {{ $song->title }}
        </h2>
    </x-slot>

    <style>
        .timing-video { position: relative; width: 100%; padding-top: 56.25%; background: #000; border-radius: .5rem; overflow: hidden; }
        .timing-video > * { position: absolute; inset: 0; width: 100%; height: 100%; }
        .timing-bar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; gap: .75rem; padding: .5rem 0; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .timing-time { min-width: 4rem; font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; }
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
        .timing-link { margin-top: .15rem; font-size: .8rem; font-weight: 600; color: #4f46e5; background: none; border: 0; padding: 0; cursor: pointer; }
    </style>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('layouts.alert')

                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-base font-semibold leading-6 text-gray-900">{{ $song->title }}</h1>
                        <p class="mt-2 text-sm text-gray-700">{{ __('Play the song and tap each block when it starts. Times can be adjusted before saving.') }}</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex sm:flex-none sm:gap-4">
                        <a type="button" href="{{ route('songs.edit', $song) }}" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50">{{ __('Edit') }}</a>
                        <a type="button" href="{{ route('songs.show', $song) }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">{{ __('Back') }}</a>
                    </div>
                </div>

                @if ($sections->isEmpty())
                    <p class="mt-6 text-sm text-gray-500">{{ __('No structure available for this song.') }}</p>
                @else
                    <form method="POST" action="{{ route('songs.timing.update', $song) }}" class="mt-6 max-w-3xl space-y-4"
                        x-data="songTiming(@js(['sections' => $sections, 'youtubeUrl' => $song->youtube_url]))">
                        @csrf
                        @method('PUT')

                        <template x-if="videoId">
                            <div class="timing-video"><div x-ref="player"></div></div>
                        </template>
                        <p class="text-sm text-gray-500" x-show="!videoId">{{ __('This song has no YouTube video: use the stopwatch below.') }}</p>

                        <div class="timing-bar">
                            <button type="button" class="timing-btn" style="min-width: 3.5rem; font-size: 1.1rem;" @click="togglePlay()" x-text="playing ? '⏸' : '▶'" :aria-label="playing ? @js(__('Pause')) : @js(__('Play'))"></button>
                            <span class="timing-time" x-text="format(now)"></span>
                            <button type="button" class="timing-btn" @click="seek(0)" title="{{ __('Restart') }}">↺</button>
                            <span class="text-sm text-gray-500">{{ __('Tap each block when it starts') }}</span>
                        </div>

                        <x-input-error :messages="collect($errors->get('starts.*'))->flatten()->unique()->all()" />

                        <div class="space-y-3">
                            <template x-for="(section, index) in sections" :key="index">
                                <div class="timing-row" :class="{ 'is-current': index === currentIndex }" @click="mark(index)" :title="@js(__('Mark the start of this block now'))">
                                    <button type="button" class="timing-mark">
                                        <span class="block text-sm font-semibold text-gray-900" x-text="section.name"></span>
                                        <span class="block text-sm italic text-gray-500" x-show="section.anchor && !section.lyrics" x-text="section.anchor"></span>
                                        <span class="block text-sm text-gray-600" x-text="section.chords"></span>
                                    </button>
                                    <div class="timing-controls" @click.stop>
                                        <button type="button" class="timing-btn" @click="nudge(index, -1)" :aria-label="@js(__('Minus one second'))">−1s</button>
                                        <input type="text" inputmode="numeric" class="timing-input border-gray-300 rounded-md shadow-sm" placeholder="m:ss"
                                            :name="`starts[${index}]`" x-model="section.start" :aria-label="`{{ __('Start') }}: ${section.name}`">
                                        <button type="button" class="timing-btn" @click="nudge(index, 1)" :aria-label="@js(__('Plus one second'))">+1s</button>
                                        <button type="button" class="timing-btn" @click="playFrom(index)" :disabled="parse(section.start) === null" :title="@js(__('Play from here'))">▶</button>
                                        <button type="button" class="timing-btn" @click="section.start = ''" :title="@js(__('Clear'))">✕</button>
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
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Clock for marking section starts: the YouTube video time when the song has a video, otherwise a stopwatch.
        function songTiming(config) {
            let player = null; // kept outside Alpine's reactive state

            const videoIdFrom = (url) => {
                const match = /(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([\w-]{11})/.exec(url || '');
                return match ? match[1] : null;
            };

            return {
                sections: config.sections,
                videoId: videoIdFrom(config.youtubeUrl),
                playerReady: false,
                playing: false,
                stopwatchStartedAt: 0,
                stopwatchElapsed: 0,
                now: 0,

                init() {
                    if (this.videoId) this.$nextTick(() => this.loadPlayer());
                    setInterval(() => {
                        this.now = this.time();
                        if (player && this.playerReady) this.playing = player.getPlayerState() === 1;
                    }, 200);
                },
                loadPlayer() {
                    const create = () => {
                        player = new YT.Player(this.$refs.player, {
                            videoId: this.videoId,
                            // Captions (lyrics) on by default, preferring Portuguese; only shown when the video has them.
                            playerVars: { playsinline: 1, rel: 0, cc_load_policy: 1, cc_lang_pref: 'pt', hl: 'pt' },
                            events: { onReady: () => this.playerReady = true },
                        });
                    };
                    if (window.YT?.Player) return create();
                    window.onYouTubeIframeAPIReady = create;
                    const script = document.createElement('script');
                    script.src = 'https://www.youtube.com/iframe_api';
                    document.head.appendChild(script);
                },
                get usingVideo() { return !!player && this.playerReady; },

                time() {
                    if (this.usingVideo) return player.getCurrentTime();
                    return this.playing ? (performance.now() - this.stopwatchStartedAt) / 1000 : this.stopwatchElapsed;
                },
                togglePlay() {
                    if (this.usingVideo) {
                        player.getPlayerState() === 1 ? player.pauseVideo() : player.playVideo();
                        return;
                    }
                    if (this.playing) {
                        this.stopwatchElapsed = this.time();
                        this.playing = false;
                    } else {
                        this.stopwatchStartedAt = performance.now() - this.stopwatchElapsed * 1000;
                        this.playing = true;
                    }
                },
                seek(seconds) {
                    if (this.usingVideo) {
                        player.seekTo(seconds, true);
                        player.playVideo();
                        return;
                    }
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
            };
        }
    </script>
</x-app-layout>
