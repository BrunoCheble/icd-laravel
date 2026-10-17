/*
 * Player of a song lesson (lessons tab and public setlist page): its YouTube video or audio file, with a stretch to
 * repeat (A–B), the speed, and the lesson's saved stretches, shared by everyone: on the lessons tab (`saveUrl`) the
 * team saves, changes and removes them; on the public page (`contributeUrl`) a musician adds one (an intro, a solo…),
 * marked as theirs, and cannot change or remove it.
 *
 * Use: x-data="lessonPlayer({ lessonId, videoUrl, audioUrl, loops, saveUrl, contributeUrl, csrf })" around markup with x-ref="video" (a box
 * where the YouTube player goes) or x-ref="audio" (an <audio> element). The YouTube player is made on the first play,
 * so a page with many lessons does not load every video.
 */
function lessonYoutubeId(url) {
    const match = /(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([\w-]{11})/.exec(url || '');
    return match ? match[1] : null;
}

// Loads the YouTube IFrame API once (the same queue as youtube-mini.js, so both can be on a page).
function lessonYoutubeApi(callback) {
    if (window.YT?.Player) return callback();
    window.__youtubeApiCallbacks = window.__youtubeApiCallbacks || [];
    window.__youtubeApiCallbacks.push(callback);
    if (window.__youtubeApiCallbacks.length > 1) return;
    window.onYouTubeIframeAPIReady = () => window.__youtubeApiCallbacks.forEach(waiting => waiting());
    const script = document.createElement('script');
    script.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(script);
}

// The lesson that is open (one at a time; the others are closed and their players paused).
function lessonStore() {
    if (!Alpine.store('lessons')) {
        // The lesson in the address (#lesson-12, after saving it on the lessons tab) starts open.
        const linked = /^#lesson-(\d+)$/.exec(location.hash);
        Alpine.store('lessons', {
            open: linked ? Number(linked[1]) : null,
            toggle(id) { this.open = this.open === id ? null : id; },
        });
    }
    return Alpine.store('lessons');
}
document.addEventListener('alpine:init', lessonStore);

function lessonPlayer(options = {}) {
    let player = null; // the YouTube player, outside Alpine's reactive state
    let timer = null;

    return {
        videoId: lessonYoutubeId(options.videoUrl),
        hasAudio: !!options.audioUrl,
        started: false,
        ready: false,
        playing: false,
        time: 0,
        duration: 0,
        speed: 1,
        speeds: [0.5, 0.6, 0.75, 0.9, 1],
        a: null,
        b: null,
        looping: false,
        // The lesson's stretches, for everyone (`contributed`: saved by a musician on the public page).
        loops: Array.isArray(options.loops) ? options.loops : [],
        loopLabel: '',
        // The team's page: stretches saved, changed and removed there.
        savesLesson: !!options.saveUrl,
        // A stretch can be saved: by the team, or added by a musician on the public page.
        get canSave() { return this.savesLesson || !!options.contributeUrl; },
        saving: false,
        message: '',

        get kind() { return this.videoId ? 'video' : this.hasAudio ? 'audio' : null; },
        get audio() { return this.$refs.audio || null; },

        init() {
            // Closed (another lesson opened, or this one closed): it stops.
            this.$watch(() => lessonStore().open, open => { if (open !== options.lessonId) this.pause(); });
            if (this.kind === 'audio') {
                const audio = this.audio;
                audio.addEventListener('play', () => { this.playing = true; this.watch(); });
                audio.addEventListener('pause', () => { this.playing = false; });
                audio.addEventListener('loadedmetadata', () => { this.duration = audio.duration || 0; this.ready = true; });
                audio.addEventListener('timeupdate', () => { this.time = audio.currentTime; });
            }
        },
        destroy() {
            clearInterval(timer);
            try { player?.destroy(); } catch (e) {}
            player = null;
        },

        // The YouTube player, made on the first play; `then` runs once it is ready.
        start(then) {
            if (this.kind !== 'video') return then?.();
            if (player && this.ready) return then?.();
            if (this.started) return;
            this.started = true;
            const box = document.createElement('div');
            this.$refs.video.appendChild(box);
            lessonYoutubeApi(() => {
                player = new YT.Player(box, {
                    videoId: this.videoId,
                    playerVars: { playsinline: 1, rel: 0 },
                    events: {
                        onReady: () => {
                            this.ready = true;
                            this.duration = player.getDuration() || 0;
                            player.setPlaybackRate(this.speed);
                            then?.();
                        },
                        onStateChange: (event) => {
                            this.playing = event.data === 1;
                            if (this.playing) this.watch();
                        },
                    },
                });
            });
        },
        now() {
            if (this.kind === 'audio') return this.audio?.currentTime || 0;
            return player && this.ready ? (player.getCurrentTime() || 0) : 0;
        },
        // While playing: the time shown, and back to A at the end of the stretch.
        watch() {
            clearInterval(timer);
            timer = setInterval(() => {
                if (!this.playing) return clearInterval(timer);
                this.time = this.now();
                if (this.looping && this.a !== null && this.b !== null && this.time >= this.b - 0.03) this.seek(this.a);
            }, 40);
        },
        play() {
            if (this.kind === 'audio') return this.audio.play();
            this.start(() => player.playVideo());
        },
        pause() {
            if (this.kind === 'audio') return this.audio.pause();
            if (player && this.ready) player.pauseVideo();
        },
        toggle() { this.playing ? this.pause() : this.play(); },
        seek(seconds) {
            const time = Math.max(0, seconds);
            this.time = time;
            if (this.kind === 'audio') { this.audio.currentTime = time; return; }
            this.start(() => player.seekTo(time, true));
        },
        back(seconds) { this.seek(this.now() - seconds); },
        setSpeed(speed) {
            this.speed = speed;
            if (this.kind === 'audio' && this.audio) this.audio.playbackRate = speed;
            else if (player && this.ready) player.setPlaybackRate(speed);
        },

        // ---- The stretch to repeat: A and B marked while it plays (or nudged), then it goes round ----
        markA() {
            this.a = Math.round(this.now() * 100) / 100;
            if (this.b !== null && this.b <= this.a) this.b = null;
            this.looping = false;
        },
        markB() {
            const time = Math.round(this.now() * 100) / 100;
            if (this.a === null || time <= this.a + 0.2) return;
            this.b = time;
            this.looping = true;
            this.seek(this.a);
            this.play();
        },
        nudge(which, delta) {
            const value = Math.max(0, Math.round(((this[which] ?? this.now()) + delta) * 100) / 100);
            if (which === 'a' && this.b !== null && value >= this.b - 0.2) return;
            if (which === 'b' && this.a !== null && value <= this.a + 0.2) return;
            this[which] = value;
        },
        toggleLoop() {
            if (this.a === null || this.b === null) return;
            this.looping = !this.looping;
            if (this.looping) { this.seek(this.a); this.play(); }
        },
        clearLoop() { this.a = null; this.b = null; this.looping = false; },
        playLoop(loop) {
            this.a = loop.start;
            this.b = loop.end;
            this.looping = true;
            this.seek(loop.start);
            this.play();
        },
        isCurrentLoop(loop) { return this.looping && this.a === loop.start && this.b === loop.end; },

        // ---- Saved stretches, in the lesson for everyone ----
        async send(url, method, body) {
            this.saving = true;
            this.message = '';
            try {
                const response = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': options.csrf || '' },
                    body: JSON.stringify(body),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error((data.errors && Object.values(data.errors).flat()[0]) || data.message || response.statusText);
                this.loops = data.loops || [];
                return true;
            } catch (error) {
                this.message = error.message;
                return false;
            } finally {
                this.saving = false;
            }
        },
        // The A–B stretch, with the name typed (or its times): saved by the team, or added by a musician.
        async addLoop() {
            if (this.a === null || this.b === null || !this.canSave) return;
            const loop = { start: this.a, end: this.b, label: this.loopLabel.trim() || `${this.format(this.a)}–${this.format(this.b)}` };
            const saved = this.savesLesson
                ? await this.send(options.saveUrl, 'PUT', { loops: [...this.loops, loop] })
                : await this.send(options.contributeUrl, 'POST', loop);
            if (saved) this.loopLabel = '';
        },
        removeLoop(index) {
            if (this.savesLesson) this.send(options.saveUrl, 'PUT', { loops: this.loops.filter((loop, i) => i !== index) });
        },

        format(seconds) {
            if (seconds === null || seconds === undefined || !Number.isFinite(seconds)) return '–';
            const tenths = Math.max(0, Math.round(seconds * 10));
            const whole = Math.floor(tenths / 10);
            return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}.${tenths % 10}`;
        },
    };
}
