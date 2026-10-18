{{-- Small YouTube player of the song pages (see public/js/youtube-mini.js); $id: the element the player replaces --}}
<div class="yt-mini" x-ref="video" x-show="videoId && showVideo" x-cloak :class="{ 'is-dragging': draggingVideo }" :style="videoStyle"
    @resize.window.debounce.200ms="keepVideoInView()">
    <button type="button" class="yt-drag" @pointerdown="dragVideo($event)" @dblclick="resetVideoPosition()"
        title="{{ __('Drag to move; double-click to put it back') }}" aria-label="{{ __('Move video') }}"><i class="fa-solid fa-grip"></i></button>
    <button type="button" class="yt-close" @click="closeVideo()" aria-label="{{ __('Close video') }}"><i class="fa-solid fa-xmark"></i></button>
    <div id="{{ $id }}"></div>
</div>

@once
    <script src="{{ asset('js/youtube-mini.js') }}?v={{ filemtime(public_path('js/youtube-mini.js')) }}"></script>
    <style>
        .yt-mini { position: fixed; z-index: 40; right: max(.75rem, env(safe-area-inset-right)); bottom: max(.75rem, env(safe-area-inset-bottom)); width: min(20rem, calc(100vw - 1.5rem)); max-width: 60vw; aspect-ratio: 16 / 9; border-radius: .6rem; overflow: hidden; background: #000; box-shadow: 0 10px 25px rgba(0, 0, 0, .35); }
        .yt-mini > div, .yt-mini iframe { width: 100%; height: 100%; border: 0; }
        .yt-mini .yt-drag, .yt-mini .yt-close { position: absolute; top: .25rem; z-index: 1; height: 28px; border-radius: 999px; border: 0; background: rgba(0, 0, 0, .65); color: #fff; cursor: pointer; }
        .yt-mini .yt-drag { left: .25rem; width: 32px; cursor: grab; touch-action: none; }
        .yt-mini .yt-close { right: .25rem; width: 28px; }
        .yt-mini.is-dragging { opacity: .85; }
        .yt-mini.is-dragging iframe { pointer-events: none; }
        .yt-mini.is-dragging .yt-drag { cursor: grabbing; }
    </style>
@endonce
