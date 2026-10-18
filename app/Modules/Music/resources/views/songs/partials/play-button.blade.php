{{-- Play/pause with the time, the same on every song work page.
     $toggle: what a click does; $time: the time to show (Alpine expressions); $show: when it is shown --}}
<button type="button" class="ws-btn ws-play" @click="{{ $toggle }}" x-show="{{ $show ?? 'true' }}"
    :aria-label="playing ? @js(__('Pause')) : @js(__('Play'))" :title="playing ? @js(__('Pause')) : @js(__('Play'))">
    <i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-play'"></i>
    <span class="ws-time" x-text="{{ $time }}"></span>
</button>
