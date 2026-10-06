/*
 * Small YouTube player shared by the song pages (section times, layout, practice): it shows up when played (a hidden
 * YouTube player ignores play), can be dragged by its handle, put back in its corner with a double click and closed.
 * Its position and speed are kept in this browser, the same for every page.
 *
 * Use: withYoutubeMini(component, youtubeUrl, options) adds the player's state and methods to an Alpine component;
 * the page includes the songs.partials.youtube-mini view and calls initVideo(elementId) from its init().
 * Options: captions (show the video's captions, Portuguese first), onEnded (called when the video ends),
 * bottomOffset (function returning a CSS bottom for the default corner, e.g. above a bottom panel).
 */
const YOUTUBE_MINI_KEY = 'youtube-mini';

function youtubeIdFrom(url) {
    const match = /(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([\w-]{11})/.exec(url || '');
    return match ? match[1] : null;
}

// Loads the YouTube IFrame API once; callbacks wait for it.
function loadYoutubeApi(callback) {
    if (window.YT?.Player) return callback();
    window.__youtubeApiCallbacks = window.__youtubeApiCallbacks || [];
    window.__youtubeApiCallbacks.push(callback);
    if (window.__youtubeApiCallbacks.length > 1) return;
    window.onYouTubeIframeAPIReady = () => window.__youtubeApiCallbacks.forEach(waiting => waiting());
    const script = document.createElement('script');
    script.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(script);
}

function readYoutubeMini() {
    try {
        return { position: null, speed: 1, ...JSON.parse(localStorage.getItem(YOUTUBE_MINI_KEY) || '{}') };
    } catch (e) {
        return { position: null, speed: 1 };
    }
}

function withYoutubeMini(component, url, options = {}) {
    let player = null; // outside Alpine's reactive state

    const mini = {
        videoId: youtubeIdFrom(url),
        showVideo: false,
        playerReady: false,
        playing: false,
        videoTime: 0,
        draggingVideo: false,
        // { position: { x, y } | null, speed }
        video: readYoutubeMini(),
        videoSpeeds: [0.5, 0.75, 0.9, 1],

        initVideo(elementId) {
            if (!this.videoId) return;
            loadYoutubeApi(() => {
                player = new YT.Player(elementId, {
                    videoId: this.videoId,
                    playerVars: options.captions
                        ? { playsinline: 1, rel: 0, cc_load_policy: 1, cc_lang_pref: 'pt', hl: 'pt' }
                        : { playsinline: 1, rel: 0 },
                    events: {
                        onReady: () => {
                            this.playerReady = true;
                            player.setPlaybackRate(this.video.speed);
                        },
                        onStateChange: (event) => {
                            if (event.data === 0 && options.onEnded) options.onEnded.call(this);
                        },
                    },
                });
            });
            setInterval(() => this.readVideo(), 250);
        },
        readVideo() {
            if (!player || !this.playerReady) return;
            this.videoTime = player.getCurrentTime() || 0;
            this.playing = player.getPlayerState() === 1;
        },
        // Exact time now (videoTime is refreshed every 250 ms).
        videoCurrentTime() { return player && this.playerReady ? (player.getCurrentTime() || 0) : 0; },
        videoState() { return player && this.playerReady ? player.getPlayerState() : -1; },
        videoDuration() { return (player && this.playerReady && player.getDuration()) || Infinity; },

        withVisiblePlayer(action) {
            if (!player || !this.playerReady) return;
            if (this.showVideo) return action();
            this.showVideo = true;
            this.$nextTick(action);
        },
        toggleVideo() {
            this.withVisiblePlayer(() => player.getPlayerState() === 1 ? player.pauseVideo() : player.playVideo());
        },
        playVideo() { this.withVisiblePlayer(() => player.playVideo()); },
        pauseVideo() { if (player && this.playerReady) player.pauseVideo(); },
        seekVideo(seconds, play = true) {
            this.withVisiblePlayer(() => {
                player.seekTo(seconds, true);
                if (play) player.playVideo();
            });
        },
        closeVideo() {
            this.pauseVideo();
            this.showVideo = false;
        },
        setVideoSpeed(speed) {
            this.video.speed = speed;
            this.saveVideo();
            if (player && this.playerReady) player.setPlaybackRate(speed);
        },

        // ---- Position: bottom right by default, or where it was dragged to ----
        get videoStyle() {
            const position = this.video.position;
            if (position) return `left: ${position.x}px; top: ${position.y}px; right: auto; bottom: auto;`;
            const bottom = options.bottomOffset ? options.bottomOffset.call(this) : null;
            return bottom ? `bottom: ${bottom};` : '';
        },
        clampVideo(x, y) {
            const box = this.$refs.video.getBoundingClientRect();
            return {
                x: Math.round(Math.min(Math.max(0, x), window.innerWidth - box.width)),
                y: Math.round(Math.min(Math.max(0, y), window.innerHeight - box.height)),
            };
        },
        dragVideo(event) {
            event.preventDefault();
            const box = this.$refs.video.getBoundingClientRect();
            const offset = { x: event.clientX - box.left, y: event.clientY - box.top };
            this.draggingVideo = true;
            const move = (moveEvent) => {
                this.video.position = this.clampVideo(moveEvent.clientX - offset.x, moveEvent.clientY - offset.y);
            };
            const end = () => {
                window.removeEventListener('pointermove', move);
                window.removeEventListener('pointerup', end);
                window.removeEventListener('pointercancel', end);
                this.draggingVideo = false;
                this.saveVideo();
            };
            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', end);
            window.addEventListener('pointercancel', end);
        },
        keepVideoInView() {
            if (!this.video.position || !this.showVideo) return;
            this.video.position = this.clampVideo(this.video.position.x, this.video.position.y);
        },
        resetVideoPosition() {
            this.video.position = null;
            this.saveVideo();
        },
        saveVideo() {
            try {
                localStorage.setItem(YOUTUBE_MINI_KEY, JSON.stringify(this.video));
            } catch (e) {
                // Storage unavailable: position and speed last only while the page is open.
            }
        },
    };

    // Copies getters as getters (a spread would freeze their values).
    Object.defineProperties(component, Object.getOwnPropertyDescriptors(mini));
    return component;
}
