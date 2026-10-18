{{-- Video section of the options panel: speed and showing the small player (see youtube-mini.js) --}}
<div class="ws-panel-section" x-show="videoId">
    <div class="ws-panel-label"><i class="fa-brands fa-youtube"></i> {{ __('Video') }}</div>
    <div class="ws-panel-row">
        <span class="ws-seg" role="group" aria-label="{{ __('Speed') }}">
            <template x-for="speed in videoSpeeds" :key="speed">
                <button type="button" :class="{ 'is-active': video.speed === speed }" @click="setVideoSpeed(speed)" x-text="`${speed}×`"></button>
            </template>
        </span>
        <button type="button" class="ws-btn" @click="showVideo ? closeVideo() : (showVideo = true)">
            <i class="fa-solid" :class="showVideo ? 'fa-eye-slash' : 'fa-eye'"></i>
            <span x-text="showVideo ? @js(__('Hide video')) : @js(__('Show video'))"></span>
        </button>
    </div>
</div>
