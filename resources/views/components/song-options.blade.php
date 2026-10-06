{{-- Panel of a song work page (bottom sheet on phones, see songs.partials.workspace); $open: the page's flag that shows it --}}
@props(['title' => __('Options'), 'open' => 'optionsOpen'])
<div x-show="{{ $open }}" x-cloak @keydown.escape.window="{{ $open }} = false">
    <div class="ws-panel-backdrop" @click="{{ $open }} = false"></div>
    <div class="ws-panel" role="dialog" aria-modal="true" aria-label="{{ $title }}" x-show="{{ $open }}" x-transition.opacity>
        <div class="ws-panel-head">
            <span>{{ $title }}</span>
            <button type="button" class="ws-btn" @click="{{ $open }} = false" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
        </div>
        {{ $slot }}
    </div>
</div>
