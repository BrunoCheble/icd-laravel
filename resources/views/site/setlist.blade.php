<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $data['title'] ?? __('Setlist') }} · {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Version from the file time, so browsers load the new file after every change --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="{{ asset('js/chord-transposer.js') }}?v={{ filemtime(public_path('js/chord-transposer.js')) }}"></script>
    <script src="{{ asset('js/chord-lines.js') }}?v={{ filemtime(public_path('js/chord-lines.js')) }}"></script>

    @include('site.partials.theme')
    <style>
        html, body { height: 100%; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: Figtree, ui-sans-serif, system-ui, sans-serif; }
        [x-cloak] { display: none !important; }

        .page { display: flex; flex-direction: column; min-height: 100dvh; }

        .chrome { position: sticky; top: 0; z-index: 30; }
        .topbar {
            display: flex; align-items: center; gap: .35rem;
            padding: .3rem max(.5rem, env(safe-area-inset-left)) .3rem max(.5rem, env(safe-area-inset-right));
            background: var(--surface); border-bottom: 1px solid var(--border);
        }
        .topbar .setlist-name { flex: 1; min-width: 0; padding: 0 .35rem; font-size: .85rem; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .counter { font-weight: 700; font-variant-numeric: tabular-nums; font-size: .9rem; color: var(--muted); min-width: 3.2rem; text-align: center; }

        .control {
            min-height: 38px; padding: .3rem 2rem .3rem .65rem;
            background-color: var(--surface-2); color: var(--text);
            border: 1px solid var(--border); border-radius: .5rem; font-size: .95rem;
            max-width: 100%;
        }
        .control:focus { outline: 2px solid var(--accent); outline-offset: 1px; border-color: var(--border); box-shadow: none; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            min-height: 40px; padding: .4rem .8rem; border-radius: .5rem;
            border: 1px solid var(--border); background: var(--surface-2); color: var(--text);
            font-weight: 600; font-size: .95rem; cursor: pointer; user-select: none;
        }
        .btn:hover { filter: brightness(1.1); }
        .btn:disabled { opacity: .35; cursor: default; filter: none; }
        .btn-accent { background: var(--accent); border-color: var(--accent); color: var(--accent-text); }
        .btn-danger { background: var(--danger); border-color: var(--danger); color: #fff; }
        .btn-icon { min-width: 40px; padding: .4rem; font-size: 1.1rem; }
        .btn.is-active { border-color: var(--accent); color: var(--accent); }

        .options {
            display: grid; gap: .6rem; padding: .6rem max(.75rem, env(safe-area-inset-left));
            background: var(--surface); border-bottom: 1px solid var(--border); box-shadow: 0 6px 16px rgba(0, 0, 0, .25);
        }
        .option-row { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem .75rem; }
        .option-row > label, .option-label { font-size: .85rem; color: var(--muted); min-width: 5.5rem; }
        .option-row .control { flex: 0 1 22rem; min-width: 0; }
        .switch { display: inline-flex; align-items: center; gap: .4rem; min-height: 36px; padding: 0 .6rem; border: 1px solid var(--border); border-radius: 999px; background: var(--surface-2); font-size: .85rem; cursor: pointer; user-select: none; }
        .switch input { accent-color: var(--accent); width: 1rem; height: 1rem; margin: 0; }
        .font-size { font-variant-numeric: tabular-nums; font-size: .85rem; min-width: 3rem; text-align: center; }

        .stage { flex: 1; width: 100%; max-width: 72rem; margin: 0 auto; padding: .6rem .9rem 1rem; box-sizing: border-box; }
        .song-head { display: flex; align-items: baseline; gap: .6rem; flex-wrap: wrap; }
        .song-title { margin: 0; font-size: clamp(1.15rem, 3.4vw, 1.75rem); font-weight: 700; line-height: 1.15; }
        .key-badge { border: 1px solid var(--accent); color: var(--accent); background: none; border-radius: 999px; padding: .1rem .6rem; font-weight: 700; font-size: .95rem; cursor: pointer; white-space: nowrap; }
        .key-badge small { font-weight: 500; color: var(--muted); }
        .song-head { position: relative; }
        .key-picker-anchor { display: inline-block; }
        .key-picker {
            position: absolute; top: calc(100% + .35rem); left: 0; z-index: 35; max-width: calc(100vw - 1.5rem); box-sizing: border-box;
            display: grid; grid-template-columns: repeat(4, minmax(3.25rem, 1fr)); gap: .35rem;
            padding: .5rem; background: var(--surface); border: 1px solid var(--border); border-radius: .6rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .35);
        }
        .key-option {
            min-height: 44px; border-radius: .45rem; border: 1px solid var(--border);
            background: var(--surface-2); color: var(--text); font-weight: 700; font-size: 1rem; cursor: pointer;
        }
        .key-option:hover { filter: brightness(1.15); }
        .key-option.is-original { border-style: dashed; border-color: var(--muted); }
        .key-option.is-selected { background: var(--accent); border-color: var(--accent); color: var(--accent-text); }
        .key-option:disabled { opacity: .5; cursor: default; }
        .song-meta { margin: .15rem 0 0; font-size: .85rem; color: var(--muted); }
        .song-meta strong { color: var(--text); font-weight: 600; }
        .hint { font-size: .85rem; color: var(--muted); }

        .sections { margin-top: .6rem; display: grid; grid-template-columns: minmax(0, 1fr); gap: calc(var(--section-gap, 1) * .6rem); font-size: calc(clamp(1.1rem, 3.6vw, 1.7rem) * var(--chord-scale, 1)); }
        /* Each block: a side line and its chords in the color of its kind (intro, chorus...) */
        .sections section {
            break-inside: avoid; position: relative; border-radius: .5rem;
            padding: .2rem .5rem .2rem .6rem; margin: 0 -.5rem; transition: background-color .2s, opacity .2s, box-shadow .2s;
            border-left: 3px solid var(--type-color, transparent);
        }
        .sections section.is-current { background: var(--accent-soft); box-shadow: inset 4px 0 0 var(--accent); }
        .sections section.is-next { box-shadow: inset 4px 0 0 var(--border); }
        .sections section.is-past { opacity: .4; }
        .section-time { margin-left: .4rem; font-weight: 500; letter-spacing: 0; text-transform: none; color: var(--muted); font-size: .9em; font-variant-numeric: tabular-nums; }
        .next-badge { margin-left: .35rem; padding: 0 .35rem; border: 1px solid var(--muted); color: var(--muted); border-radius: 999px; font-size: .65rem; letter-spacing: .02em; text-transform: none; vertical-align: middle; }
        .section-progress { height: 3px; margin-top: .3rem; background: var(--border); border-radius: 2px; overflow: hidden; }
        .section-progress > div, .player-progress > div { height: 100%; background: var(--accent); transition: width .25s linear; }

        .player {
            display: flex; align-items: center; gap: .4rem;
            padding: .25rem max(.5rem, env(safe-area-inset-left)) .25rem max(.5rem, env(safe-area-inset-right));
            background: var(--surface); border-bottom: 1px solid var(--border);
        }
        .player-time { min-width: 3.2rem; text-align: center; font-weight: 700; font-variant-numeric: tabular-nums; }
        .player-section { flex: 1; min-width: 0; font-size: .8rem; color: var(--muted); }
        .player-section .names { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .player-section .names strong { color: var(--text); }
        .player-progress { height: 3px; margin-top: .2rem; background: var(--border); border-radius: 2px; overflow: hidden; }

        /* Block name: small, in the block's color, so it does not get in the way of the chords */
        .section-name { font-size: calc(.68rem * var(--label-scale, 1)); font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--type-color, var(--accent)); line-height: 1.3; opacity: .9; }
        .section-anchor { font-size: calc(.95rem * var(--label-scale, 1)); font-style: italic; color: var(--muted); line-height: 1.25; }
        /* Chord sheet: each chord sits over the piece of lyric it starts on; lines wrap between words. */
        .sheet-lines { display: grid; gap: calc(.15rem + (var(--line-gap, 1) - 1) * .8em); }
        /* Top aligned, with the lyric row always reserved, so chords stay on one row even without text below. */
        .sheet-line { display: flex; flex-wrap: wrap; align-items: flex-start; line-height: 1.2; }
        .seg { display: inline-flex; flex-direction: column; max-width: 100%; }
        .seg-chord { min-height: 1.2em; width: 0; overflow: visible; font-weight: 700; color: var(--type-color, var(--accent)); white-space: pre; font-size: .85em; }
        /* The chord floats over the following text; it only takes room when the next chord would overlap it. */
        .seg.is-wide .seg-chord { width: auto; padding-right: .35em; }
        .seg-text { min-height: 1.2em; white-space: pre-wrap; font-weight: 500; color: var(--chord); font-size: .8em; }
        .sheet-line.no-chords .seg-chord { display: none; }
        .sheet-line.only-chords .seg-text { display: none; }
        .sheet-comment { font-size: calc(.85rem * var(--label-scale, 1)); font-style: italic; color: var(--muted); }
        .mode-toggle { display: inline-flex; border: 1px solid var(--border); border-radius: 999px; overflow: hidden; }
        .mode-toggle button { padding: .15rem .7rem; font-size: .85rem; font-weight: 600; background: none; color: var(--muted); border: 0; cursor: pointer; min-height: 32px; }
        .mode-toggle button.is-active { background: var(--accent); color: var(--accent-text); }
        .section-lyrics { margin-top: .2rem; font-size: calc(.95rem * var(--label-scale, 1)); color: var(--muted); line-height: 1.35; white-space: pre-line; }
        /* Room between chord lines: set in the options ("line spacing"), per song */
        .section-chords { font-weight: 700; color: var(--type-color, var(--chord)); line-height: calc(1.3 * var(--line-gap, 1)); }
        .chord-line { display: flex; flex-wrap: wrap; column-gap: 1em; user-select: none; -webkit-user-select: none; }
        .chord-step { white-space: pre; }
        /* Passing chords: dotted underline */
        .chord-step .is-passing, .seg-chord.is-passing { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .22em; }
        /* Repeated cycles in columns: each column is as wide as its widest chord across the lines. */
        .section-chords.is-aligned { display: grid; grid-template-columns: repeat(var(--chord-columns, 1), max-content); column-gap: 1em; justify-content: start; }
        .section-chords.is-aligned .chord-line { display: grid; grid-column: 1 / -1; grid-template-columns: subgrid; }
        .section-chords { touch-action: manipulation; cursor: text; }
        .chord-edit {
            display: block; width: 100%; box-sizing: border-box; resize: vertical;
            padding: .3rem .5rem; font: inherit; font-weight: 700; line-height: 1.3;
            color: var(--chord); background: var(--surface-2); border: 1px dashed var(--accent); border-radius: .4rem;
        }
        .chord-edit:focus { outline: 2px solid var(--accent); outline-offset: 1px; box-shadow: none; }
        .edited-badge { margin-left: .35rem; padding: 0 .35rem; border: 1px solid var(--accent); border-radius: 999px; font-size: .65rem; letter-spacing: .02em; text-transform: none; vertical-align: middle; }
        .empty { color: var(--muted); text-align: center; padding: 3rem 1rem; }

        /* Wide screens: section label beside the chords, optional two columns. */
        @media (min-width: 768px) {
            .sections section { display: grid; grid-template-columns: 7.5rem minmax(0, 1fr); column-gap: .75rem; align-items: baseline; }
            .section-label { grid-column: 1; }
            .section-chords { grid-column: 2; }
            .section-lyrics { grid-column: 2; }
            .section-progress { grid-column: 1 / -1; }
            .sections.two-columns { display: block; column-count: 2; column-gap: 2.5rem; }
            .sections.two-columns section { margin-bottom: calc(var(--section-gap, 1) * .6rem); }
        }

        .overlay { position: fixed; inset: 0; z-index: 40; background: rgba(0, 0, 0, .5); }
        .panel {
            position: fixed; z-index: 50; top: 0; right: 0; bottom: 0;
            width: min(26rem, 100%); display: flex; flex-direction: column;
            background: var(--surface); border-left: 1px solid var(--border);
            padding-bottom: env(safe-area-inset-bottom);
        }
        .panel-header { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem 1rem; border-bottom: 1px solid var(--border); }
        .panel-header h2 { margin: 0; font-size: 1.1rem; }
        .panel-body { flex: 1; overflow-y: auto; padding: .75rem 1rem 1rem; }

        .song-list { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: minmax(0, 1fr); gap: .4rem; }
        .song-item {
            display: flex; align-items: center; gap: .5rem;
            padding: .35rem .35rem .35rem .25rem; border-radius: .5rem;
            background: var(--surface-2); border: 1px solid var(--border);
        }
        .song-item.is-current { border-color: var(--accent); }
        .song-item.is-dragging { opacity: .6; outline: 2px dashed var(--accent); }
        .handle { touch-action: none; cursor: grab; min-width: 40px; min-height: 44px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 1.25rem; }
        .song-item-main { flex: 1; min-width: 0; text-align: left; background: none; border: 0; color: inherit; padding: .25rem 0; cursor: pointer; }
        .song-item-title { display: block; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .song-item-meta { display: block; font-size: .85rem; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .search { margin-top: 1rem; }
        .search input { width: 100%; box-sizing: border-box; padding-right: .75rem; }
        .results { list-style: none; margin: .5rem 0 0; padding: 0; display: grid; gap: .25rem; }
        .result { width: 100%; text-align: left; padding: .6rem .75rem; border-radius: .5rem; background: none; border: 1px solid transparent; color: inherit; cursor: pointer; }
        .result:hover:not(:disabled) { background: var(--surface-2); border-color: var(--border); }
        .result:disabled { opacity: .5; cursor: default; }

        .modal { position: fixed; z-index: 60; inset: 0; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .modal .overlay { z-index: 0; }
        .modal-box { position: relative; z-index: 1; width: min(24rem, 100%); background: var(--surface); border: 1px solid var(--border); border-radius: .75rem; padding: 1.25rem; }
        .modal-box p { margin: 0 0 1.25rem; font-size: 1.05rem; }
        .modal-actions { display: flex; justify-content: flex-end; gap: .5rem; }

        /* Small YouTube player used for the song sound (the player must stay visible). */
        .yt-mini {
            position: fixed; z-index: 35; right: max(.75rem, env(safe-area-inset-right)); bottom: max(.75rem, env(safe-area-inset-bottom));
            width: 240px; aspect-ratio: 16 / 9; background: #000; border-radius: .5rem; overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
        }
        .yt-mini > div, .yt-mini iframe { width: 100%; height: 100%; display: block; }
        .yt-mini-close {
            position: absolute; top: .25rem; right: .25rem; z-index: 1; width: 28px; height: 28px; border-radius: 999px;
            border: 0; background: rgba(0, 0, 0, .65); color: #fff; font-size: .85rem; cursor: pointer;
        }
        @media (max-width: 480px) { .yt-mini { width: 200px; } }
        /* Above the blocks bar when it is shown */
        .has-jump-bar .yt-mini { bottom: calc(3.4rem + max(.75rem, env(safe-area-inset-bottom))); }

        /* Blocks bar: one button per block of the song; a tap goes to the last block with that name */
        .jump-bar {
            position: fixed; z-index: 34; left: 0; right: 0; bottom: 0;
            display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none;
            padding: .4rem max(.5rem, env(safe-area-inset-left)) max(.4rem, env(safe-area-inset-bottom));
            background: var(--surface); border-top: 1px solid var(--border);
        }
        .jump-bar::-webkit-scrollbar { display: none; }
        .jump-bar button {
            flex-shrink: 0; min-height: 36px; padding: 0 .8rem; border-radius: 999px; cursor: pointer;
            border: 1px solid var(--type-color, var(--border)); background: none; color: var(--type-color, var(--text));
            font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap;
        }
        .jump-bar button.is-current { background: var(--type-color, var(--accent)); color: var(--surface); }
        .has-jump-bar .stage { padding-bottom: 4rem; }
        .toast {
            position: fixed; z-index: 70; left: 50%; bottom: 1rem; transform: translateX(-50%);
            max-width: calc(100% - 2rem); padding: .6rem 1rem; border-radius: .5rem;
            background: var(--text); color: var(--bg); font-weight: 600; box-shadow: 0 4px 16px rgba(0, 0, 0, .3);
        }
        .toast.is-error { background: var(--danger); color: #fff; }

        @media (max-width: 480px) {
            .topbar .setlist-name { display: none; }
            .topbar .spacer-mobile { flex: 1; }
        }
    </style>
</head>

<body>
    <div class="page" x-data="setlistPage(@js([
        'setlist' => $data,
        'keyOptions' => $keyOptions,
        'urls' => [
            'setlist' => url('/repertoire'),
            'search' => route('site.setlist.songs.search'),
        ],
        'messages' => [
            'confirmRemove' => __('Remove ":title" from this setlist?'),
            'saved' => __('Saved'),
            'saveError' => __('Could not save the change.'),
            'added' => __('Song added to the setlist.'),
            'removed' => __('Song removed from the setlist.'),
            'sessionExpired' => __('Your session expired. Please reload the page.'),
        ],
    ]))" @keydown.window="onKeydown($event)" :class="{ 'has-jump-bar': current && jumpTargets.length > 1 }">

        <div class="chrome">
        <header class="topbar">
            @if ($data)
                <button type="button" class="btn btn-icon" @click="openPanel()" :aria-expanded="panelOpen" aria-label="{{ __('Songs') }}" title="{{ __('Songs') }}"><i class="fa-solid fa-bars"></i></button>
                <button type="button" class="btn btn-icon" @click="go(currentIndex - 1)" :disabled="currentIndex <= 0" aria-label="{{ __('Previous') }}" :title="songs[currentIndex - 1]?.title"><i class="fa-solid fa-chevron-left"></i></button>
                <span class="counter" x-text="songs.length ? `${currentIndex + 1} / ${songs.length}` : '0 / 0'"></span>
                <button type="button" class="btn btn-icon" @click="go(currentIndex + 1)" :disabled="currentIndex >= songs.length - 1" aria-label="{{ __('Next') }}" :title="songs[currentIndex + 1]?.title"><i class="fa-solid fa-chevron-right"></i></button>
                <span class="setlist-name">{{ $data['title'] }}{{ $data['event_date'] ? ' · ' . $data['event_date'] : '' }}</span>
                <span class="spacer-mobile"></span>
            @else
                <span class="setlist-name">{{ __('Setlist') }}</span>
            @endif
            <button type="button" class="btn btn-icon" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" :aria-expanded="optionsOpen" aria-label="{{ __('Options') }}" title="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
        </header>

        @if ($data)
            {{-- Section timer: follows the "start" times of the structure; tapping a block re-syncs it --}}
            <div class="player" x-show="current">
                <button type="button" class="btn btn-icon" @click="togglePlay()" :aria-label="playing ? @js(__('Pause')) : @js(__('Play'))" ><i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-play'"></i></button>
                <span class="player-time" x-text="formatTime(elapsed)"></span>
                <div class="player-section">
                    <div class="names">
                        <strong x-text="currentSection !== null ? timedName(currentSection) : '—'"></strong>
                        <template x-if="nextSection !== null">
                            <span> <i class="fa-solid fa-arrow-right" style="font-size: .8em;"></i> <span x-text="timedName(nextSection)"></span></span>
                        </template>
                    </div>
                    <div class="player-progress" x-show="currentProgress !== null"><div :style="`width: ${(currentProgress ?? 0) * 100}%`"></div></div>
                </div>
                <button type="button" class="btn btn-icon" @click="restart()" aria-label="{{ __('Restart') }}" title="{{ __('Restart') }}"><i class="fa-solid fa-rotate-left"></i></button>
            </div>
        @endif

        {{-- Secondary options, hidden by default --}}
        <div class="options" x-show="optionsOpen" x-cloak @keydown.escape="optionsOpen = false">
            <div class="option-row">
                <label for="setlist_select">{{ __('Setlist') }}</label>
                <select id="setlist_select" class="control" @change="openSetlist($event.target.value)">
                    @if (! $data)
                        <option value="">—</option>
                    @endif
                    @foreach ($setlistOptions as $option)
                        <option value="{{ $option['id'] }}" @selected($data && $option['id'] === $data['id'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            @if ($data)
                <div class="option-row" x-show="current">
                    <label for="song_key">{{ __('Key') }}</label>
                    <select id="song_key" x-ref="keySelect" class="control" style="flex: 0 0 auto; font-weight: 700;" :value="selectedKey" @change="changeKey($event.target.value)" :disabled="savingKey">
                        <template x-if="!selectedKey">
                            <option value="" :selected="!selectedKey">—</option>
                        </template>
                        <template x-for="key in extraKeys" :key="'extra-' + key">
                            <option :value="key" x-text="key" :selected="key === selectedKey"></option>
                        </template>
                        <template x-for="(keys, group) in availableKeyOptions" :key="group">
                            <optgroup :label="group">
                                <template x-for="key in keys" :key="key">
                                    <option :value="key" x-text="key" :selected="key === selectedKey"></option>
                                </template>
                            </optgroup>
                        </template>
                    </select>
                    <span class="hint" x-show="current?.original_key && current.original_key !== selectedKey">
                        {{ __('Original key') }}: <strong x-text="current?.original_key"></strong>
                    </span>
                </div>

                <div class="option-row">
                    <span class="option-label">{{ __('Display') }}</span>
                    <label class="switch"><input type="checkbox" x-model="settings.showDetails"> {{ __('Artist and minister') }}</label>
                    <label class="switch"><input type="checkbox" x-model="settings.showAnchors"> {{ __('Anchors') }}</label>
                    <label class="switch"><input type="checkbox" x-model="settings.showLyrics"> {{ __('Lyrics') }}</label>
                    <label class="switch"><input type="checkbox" x-model="settings.twoColumns"> {{ __('Two columns') }}</label>
                    <label class="switch" :title="videoId ? '' : @js(__('This song has no YouTube video.'))"><input type="checkbox" x-model="settings.youtubeSound"> {{ __('YouTube sound') }}</label>
                </div>

                <div class="option-row" x-show="current">
                    <span class="option-label">{{ __('Chords') }}</span>
                    <span class="hint">{{ __('Double-tap a block to edit its chords.') }}</span>
                    <button type="button" class="btn" @click="resetChords()" x-show="hasChordEdits">{{ __('Restore original chords') }}</button>
                    <span class="hint">{{ __('Edits are saved only on this device.') }}</span>
                </div>

                <div class="option-row">
                    <span class="option-label">{{ __('Font size') }}</span>
                    <button type="button" class="btn btn-icon" @click="scaleFont(-1)" :disabled="layout.fontScale <= 0.7" aria-label="{{ __('Smaller') }}">A−</button>
                    <span class="font-size" x-text="Math.round(layout.fontScale * 100) + '%'"></span>
                    <button type="button" class="btn btn-icon" @click="scaleFont(1)" :disabled="layout.fontScale >= 1.6" aria-label="{{ __('Larger') }}">A+</button>
                </div>

                <div class="option-row">
                    <span class="option-label">{{ __('Section text') }}</span>
                    <button type="button" class="btn btn-icon" @click="step('labelScale', -0.1, 0.8, 2)" :disabled="layout.labelScale <= 0.8" aria-label="{{ __('Smaller') }}">A−</button>
                    <span class="font-size" x-text="Math.round(layout.labelScale * 100) + '%'"></span>
                    <button type="button" class="btn btn-icon" @click="step('labelScale', 0.1, 0.8, 2)" :disabled="layout.labelScale >= 2" aria-label="{{ __('Larger') }}">A+</button>
                </div>

                <div class="option-row">
                    <span class="option-label">{{ __('Line spacing') }}</span>
                    <button type="button" class="btn btn-icon" @click="step('lineGap', -0.25, 1, 3)" :disabled="layout.lineGap <= 1" aria-label="{{ __('Less space') }}"><i class="fa-solid fa-minus"></i></button>
                    <span class="font-size" x-text="Math.round(layout.lineGap * 100) + '%'"></span>
                    <button type="button" class="btn btn-icon" @click="step('lineGap', 0.25, 1, 3)" :disabled="layout.lineGap >= 3" aria-label="{{ __('More space') }}"><i class="fa-solid fa-plus"></i></button>
                </div>


                <div class="option-row">
                    <span class="option-label"></span>
                    <button type="button" class="btn" @click="resetLayout()" :disabled="isDefaultLayout">{{ __('Reset this song\'s layout') }}</button>
                </div>
            @endif
        </div>
        </div>

        <main class="stage" @click="optionsOpen = false">
            @if (! $data)
                <p class="empty">{{ __('No setlists available.') }}</p>
            @else
                <template x-if="!current">
                    <div class="empty">
                        <p>{{ __('No songs in this setlist yet.') }}</p>
                        <button type="button" class="btn btn-accent" @click.stop="openPanel(true)">+ {{ __('Add song') }}</button>
                    </div>
                </template>

                <template x-if="current">
                    <article>
                        <div class="song-head">
                            <h1 class="song-title" x-text="current.title"></h1>
                            <span class="key-picker-anchor" @click.outside="keyPickerOpen = false" @keydown.escape.window="keyPickerOpen = false">
                                <button type="button" class="key-badge" @click.stop="keyPickerOpen = !keyPickerOpen" :aria-expanded="keyPickerOpen" :title="@js(__('Change key'))">
                                    <span x-text="selectedKey || '—'"></span>
                                    <small x-show="current.original_key && current.original_key !== selectedKey" x-text="`(${current.original_key})`"></small>
                                </button>
                                <div class="key-picker" x-show="keyPickerOpen" x-cloak @click.stop role="listbox" :aria-label="@js(__('Key'))">
                                    <template x-for="key in [...extraKeys, ...Object.values(availableKeyOptions).flat()]" :key="key">
                                        <button type="button" class="key-option" role="option"
                                            :class="{ 'is-selected': key === selectedKey, 'is-original': key === current.original_key }"
                                            :aria-selected="key === selectedKey" :disabled="savingKey"
                                            :title="key === current.original_key ? @js(__('Original key')) : ''"
                                            @click="pickKey(key)" x-text="key"></button>
                                    </template>
                                </div>
                            </span>
                            <span class="mode-toggle" x-show="hasSheet" role="group" :aria-label="@js(__('View'))">
                                <button type="button" :class="{ 'is-active': !sheetMode }" @click.stop="setViewMode('map')">{{ __('Map') }}</button>
                                <button type="button" :class="{ 'is-active': sheetMode }" @click.stop="setViewMode('sheet')">{{ __('Chord sheet') }}</button>
                            </span>
                        </div>
                        <p class="song-meta" x-show="settings.showDetails">
                            <span x-text="current.artist"></span>
                            <template x-if="current.minister_name">
                                <span> · {{ __('Minister') }}: <strong x-text="current.minister_name"></strong></span>
                            </template>
                        </p>

                        <template x-if="sheetMode">
                        <div class="sections" :class="{ 'two-columns': settings.twoColumns }" :style="`--chord-scale: ${layout.fontScale}; --label-scale: ${layout.labelScale}; --line-gap: ${layout.lineGap}; --section-gap: 3`">
                            <template x-for="(section, index) in current.chord_sheet" :key="`${current.id}-${index}`">
                                <section :class="sectionState(index)" :style="typeStyle(timedName(index))" @click="selectSection(index)">
                                    <div class="section-label">
                                        <div class="section-name">
                                            <span x-text="timedName(index)"></span><span class="section-time" x-show="sectionRange(index)" x-text="sectionRange(index)"></span><span class="next-badge" x-show="index === nextSection">{{ __('next') }}</span>
                                        </div>
                                    </div>
                                    <div class="sheet-lines">
                                        <template x-for="(line, lineIndex) in (section.lines || [])" :key="lineIndex">
                                            <div x-data="{ parsed: sheetLine(line) }"
                                                :class="parsed.type === 'comment' ? 'sheet-comment' : { 'sheet-line': true, 'no-chords': !parsed.hasChords, 'only-chords': !parsed.hasText }">
                                                <span x-show="parsed.type === 'comment'" x-text="parsed.text"></span>
                                                <template x-for="(unit, unitIndex) in (parsed.units || [])" :key="unitIndex">
                                                    <span class="seg" :class="{ 'is-wide': unit.wide }"><span class="seg-chord" :class="{ 'is-passing': unit.chord && sheetChordPassing(index, lineIndex, unit.ordinal) }" x-text="unit.chord ? chordFor(unit.chord) : ''"></span><span class="seg-text" x-text="unit.text"></span></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="section-progress" x-show="index === currentSection && currentProgress !== null">
                                        <div :style="`width: ${(currentProgress ?? 0) * 100}%`"></div>
                                    </div>
                                </section>
                            </template>
                        </div>
                        </template>

                        <template x-if="!sheetMode">
                        <div class="sections" :class="{ 'two-columns': settings.twoColumns }" :style="`--chord-scale: ${layout.fontScale}; --label-scale: ${layout.labelScale}; --line-gap: ${layout.lineGap}; --section-gap: 3`">
                            <template x-for="(section, index) in sections" :key="`${current.id}-${index}`">
                                <section :class="sectionState(index)" :style="typeStyle(sectionName(section))" @click="selectSection(index)">
                                    <div class="section-label">
                                        <div class="section-name">
                                            <span x-text="sectionName(section)"></span><span class="section-time" x-show="sectionRange(index)" x-text="sectionRange(index)"></span><span class="next-badge" x-show="index === nextSection">{{ __('next') }}</span><span class="edited-badge" x-show="section.edited">{{ __('edited') }}</span>
                                        </div>
                                        <div class="section-anchor" x-show="settings.showAnchors && section.anchor && !(settings.showLyrics && lyricsText(section))" x-text="section.anchor"></div>
                                    </div>
                                    <div class="section-chords" @dblclick="startEdit(index)" @pointerup="tap($event, index)"
                                        x-data="{
                                            lines: [],
                                            aligned: true,
                                            // Columns sized by the widest chord of each cycle position; plain wrapping when it does not fit.
                                            fit() {
                                                this.aligned = true;
                                                this.$nextTick(() => this.aligned = this.$el.scrollWidth <= this.$el.clientWidth + 1);
                                            },
                                        }"
                                        x-effect="lines = editingIndex === index ? [] : chordLines(section); layout.fontScale; settings.twoColumns; editingIndex === index ? (aligned = false) : fit()"
                                        @resize.window.debounce.200ms="fit()"
                                        :class="{ 'is-aligned': aligned }"
                                        :style="`--chord-columns: ${Math.max(1, ...lines.map(line => line.length))}`">
                                        <template x-if="editingIndex === index">
                                            <textarea class="chord-edit" rows="2" spellcheck="false" autocapitalize="off" autocomplete="off"
                                                :value="(section.chords || []).join(' ')"
                                                x-init="$nextTick(() => { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length); })"
                                                @blur="finishEdit(index, $event.target.value)"
                                                @keydown.enter.prevent="$event.target.blur()"
                                                @keydown.escape.prevent.stop="cancelEdit()"
                                                :aria-label="`{{ __('Chords') }}: ${sectionName(section)}`"></textarea>
                                        </template>
                                        <template x-for="(line, lineIndex) in lines" :key="lineIndex">
                                            <div class="chord-line">
                                                <template x-for="(step, stepIndex) in line" :key="stepIndex">
                                                    <span class="chord-step"><template x-for="(chord, c) in step" :key="c"><span :class="{ 'is-passing': chord.passing }" x-text="(c ? ' ' : '') + chord.name"></span></template></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="section-lyrics" x-show="settings.showLyrics && lyricsText(section)" x-text="lyricsText(section)"></div>
                                    <div class="section-progress" x-show="index === currentSection && currentProgress !== null">
                                        <div :style="`width: ${(currentProgress ?? 0) * 100}%`"></div>
                                    </div>
                                </section>
                            </template>
                            <p class="hint" x-show="sections.length === 0">{{ __('No structure available for this song.') }}</p>
                        </div>
                        </template>
                    </article>
                </template>
            @endif
        </main>

        @if ($data)
            {{-- Setlist management panel --}}
            <div class="overlay" x-show="panelOpen" x-cloak @click="panelOpen = false"></div>
            <aside class="panel" x-show="panelOpen" x-cloak @keydown.escape="panelOpen = false">
                <div class="panel-header">
                    <h2>{{ __('Songs') }}</h2>
                    <button type="button" class="btn btn-icon" @click="panelOpen = false" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="panel-body">
                    <ol class="song-list" x-ref="list">
                        <template x-for="(song, index) in songs" :key="song.id">
                            <li class="song-item" :data-id="song.id" :class="{ 'is-current': song.id === currentId, 'is-dragging': song.id === dragId }">
                                <span class="handle" @pointerdown="startDrag($event, song.id)" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>
                                <button type="button" class="song-item-main" @click="go(index); panelOpen = false">
                                    <span class="song-item-title" x-text="`${index + 1}. ${song.title}`"></span>
                                    <span class="song-item-meta" x-text="[song.artist, song.setlist_key ?? song.original_key ?? '—', song.minister_name].filter(Boolean).join(' · ')"></span>
                                </button>
                                <button type="button" class="btn btn-icon" @click="askRemove(song)" aria-label="{{ __('Remove') }}"><i class="fa-solid fa-xmark"></i></button>
                            </li>
                        </template>
                    </ol>

                    <div class="search">
                        <button type="button" class="btn btn-accent" x-show="!searchOpen" @click="openSearch()">+ {{ __('Add song') }}</button>
                        <div x-show="searchOpen">
                            <input type="search" class="control" x-ref="search" x-model="term" @input.debounce.300ms="search()"
                                placeholder="{{ __('Search song...') }}" autocomplete="off">
                            <ul class="results">
                                <template x-for="song in results" :key="song.id">
                                    <li>
                                        <button type="button" class="result" @click="addSong(song)" :disabled="isInSetlist(song) || adding">
                                            <span class="song-item-title" x-text="song.title"></span>
                                            <span class="song-item-meta" x-text="isInSetlist(song) ? song.artist + ' · ' + @js(__('Already in the setlist')) : song.artist"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <p class="hint" x-show="searched && results.length === 0">{{ __('No songs found.') }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Remove confirmation --}}
            <div class="modal" x-show="removing" x-cloak>
                <div class="overlay" @click="removing = null"></div>
                <div class="modal-box" role="dialog" aria-modal="true">
                    <p x-text="removing ? messages.confirmRemove.replace(':title', removing.title) : ''"></p>
                    <div class="modal-actions">
                        <button type="button" class="btn" @click="removing = null">{{ __('Cancel') }}</button>
                        <button type="button" class="btn btn-danger" @click="confirmRemove()">{{ __('Remove') }}</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($data)
            {{-- Blocks bar: quick way back to a block (e.g. the chorus): goes to the last one with that name --}}
            <nav class="jump-bar" x-show="current && jumpTargets.length > 1" x-cloak aria-label="{{ __('Blocks') }}">
                <template x-for="target in jumpTargets" :key="target.key">
                    <button type="button" :style="typeStyle(target.name)" :class="{ 'is-current': isCurrentTarget(target) }" @click="jumpTo(target)" x-text="target.name"></button>
                </template>
            </nav>
        @endif

        {{-- Song sound: YouTube player kept small and visible, synced with the section timer --}}
        <div class="yt-mini" x-show="settings.youtubeSound && videoId" x-cloak>
            <button type="button" class="yt-mini-close" @click="settings.youtubeSound = false" aria-label="{{ __('Turn off YouTube sound') }}" title="{{ __('Turn off YouTube sound') }}"><i class="fa-solid fa-xmark"></i></button>
            <div id="yt-sound"></div>
        </div>

        <div class="toast" x-show="toast.text" x-cloak x-transition.opacity :class="{ 'is-error': toast.error }" x-text="toast.text" role="status"></div>
    </div>

    <script>
        // Per-viewer display preferences; the page works the same when storage is unavailable.
        // Toggles are shared by every song; size settings are stored per song_id.
        const SETTINGS_KEY = 'setlist-view-settings';
        const DEFAULT_SETTINGS = { showDetails: true, showAnchors: true, showLyrics: false, twoColumns: true, viewMode: 'map', youtubeSound: false };

        // YouTube player for the song sound, outside Alpine's reactive state.
        let soundPlayer = null;
        const youtubeIdFrom = (url) => {
            const match = /(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([\w-]{11})/.exec(url || '');
            return match ? match[1] : null;
        };

        // Chords of a chord sheet line ("{c: ...}" comments have none). Line splitting and repeat colors: chord-lines.js.
        const sheetChords = (line) => /^\s*\{/.test(String(line)) ? [] : [...String(line).matchAll(/\[([^\]]+)\]/g)].map(match => match[1]);
        const LAYOUT_KEY_PREFIX = 'song-layout:';
        const DEFAULT_LAYOUT = { fontScale: 1, labelScale: 1, lineGap: 1 };

        function readStorage(key, defaults) {
            try {
                const stored = JSON.parse(localStorage.getItem(key) || '{}');
                return Object.fromEntries(Object.entries(defaults).map(([name, value]) => [name, stored[name] ?? value]));
            } catch (e) {
                return { ...defaults };
            }
        }

        function writeStorage(key, value) {
            try {
                value === null ? localStorage.removeItem(key) : localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {}
        }

        const loadSettings = () => readStorage(SETTINGS_KEY, DEFAULT_SETTINGS);
        const saveSettings = (settings) => writeStorage(SETTINGS_KEY, settings);
        const loadLayout = (songId) => readStorage(LAYOUT_KEY_PREFIX + songId, DEFAULT_LAYOUT);
        const saveLayout = (songId, layout) => writeStorage(LAYOUT_KEY_PREFIX + songId, layout);

        // Chord edits per song_id: { [sectionIndex]: { original: [...], chords: [...] } }.
        // `original` makes an edit apply only while the stored structure of that section is unchanged.
        const CHORDS_KEY_PREFIX = 'song-chords:';

        function loadChordEdits(songId) {
            try {
                const edits = JSON.parse(localStorage.getItem(CHORDS_KEY_PREFIX + songId) || '{}');
                return edits && typeof edits === 'object' ? edits : {};
            } catch (e) {
                return {};
            }
        }

        function saveChordEdits(songId, edits) {
            if (songId) writeStorage(CHORDS_KEY_PREFIX + songId, Object.keys(edits).length ? edits : null);
        }

        // Section start time from the structure: seconds (16) or "m:ss" / "h:mm:ss" ("0:16"); null when absent.
        function parseStart(value) {
            if (typeof value === 'number' && Number.isFinite(value) && value >= 0) return value;
            if (typeof value !== 'string' || !/^\d+(:\d{1,2}){0,2}(\.\d+)?$/.test(value.trim())) return null;
            return value.trim().split(':').reduce((total, part) => total * 60 + Number(part), 0);
        }

        const originalChords = (section) => Array.isArray(section?.chords) ? section.chords.map(String) : [];
        const sameChords = (a, b) => Array.isArray(a) && a.length === b.length && a.every((chord, i) => chord === b[i]);
        // Chords separated by spaces or new lines; arrows used as separators are ignored, "|" keeps the line breaks.
        const parseChords = (text) => String(text).split(/\s+/).filter(token => token && !['->', '→', '-'].includes(token));

        function setlistPage(config) {
            const setlist = config.setlist;

            return {
                songs: setlist?.songs ?? [],
                keyOptions: config.keyOptions,
                messages: config.messages,
                currentId: setlist?.songs?.[0]?.id ?? null,
                optionsOpen: false,
                settings: loadSettings(),
                layout: { ...DEFAULT_LAYOUT },
                editingIndex: null,
                keyPickerOpen: false,
                playing: false,
                elapsed: 0,
                startedAt: 0,
                ticker: null,
                wakeLock: null,
                currentSection: null,
                // Last timed section reached by the clock; auto-advance only happens when it changes,
                // so a block picked by hand is kept until the clock reaches the next start.
                clockSection: null,
                editCancelled: false,
                lastTap: null,
                chordEdits: {},
                savingKey: false,
                panelOpen: false,
                searchOpen: false,
                term: '',
                results: [],
                searched: false,
                adding: false,
                removing: null,
                dragId: null,
                toast: { text: '', error: false },

                get currentIndex() { return this.songs.findIndex(song => song.id === this.currentId); },
                get current() { return this.songs[this.currentIndex] ?? null; },

                // Key used in this setlist, falling back to the song's original key.
                get selectedKey() { return this.current?.setlist_key ?? this.current?.original_key ?? ''; },
                // Only keys of the song's mode: major songs list major keys, minor songs list minor keys.
                get songIsMinor() {
                    const key = ChordTransposer.parseKey(this.current?.original_key || this.selectedKey);
                    return key ? key.minor : null;
                },
                get availableKeyOptions() {
                    if (this.songIsMinor === null) return this.keyOptions;
                    return Object.fromEntries(Object.entries(this.keyOptions)
                        .map(([group, keys]) => [group, keys.filter(key => ChordTransposer.parseKey(key)?.minor === this.songIsMinor)])
                        .filter(([, keys]) => keys.length));
                },
                get extraKeys() {
                    const listed = Object.values(this.availableKeyOptions).flat();
                    return [...new Set([this.selectedKey, this.current?.original_key])].filter(key => key && !listed.includes(key));
                },

                // Sections to render: the stored structure with this device's chord edits applied,
                // transposed from the song's original key to the key selected for this setlist.
                get sections() {
                    const structure = Array.isArray(this.current?.structure) ? this.current.structure : [];
                    const from = this.current?.original_key;
                    const to = this.selectedKey;
                    return structure.map((section, index) => {
                        if (!section || typeof section !== 'object') return section;
                        const edit = this.chordEdits[index];
                        const edited = !!edit && sameChords(edit.original, originalChords(section));
                        const chords = edited ? edit.chords : originalChords(section);
                        return { ...section, chords: ChordTransposer.transposeChords(chords, from, to), edited };
                    });
                },
                get hasChordEdits() { return this.sections.some(section => section?.edited); },
                // Saves the chords typed for a section; typing the original chords back removes the edit.
                updateChords(index, text) {
                    const original = originalChords(this.current.structure[index]);
                    // Chords are typed in the selected key and stored in the song's original key.
                    const chords = ChordTransposer.transposeChords(parseChords(text), this.selectedKey, this.current.original_key);
                    const edits = { ...this.chordEdits };
                    if (sameChords(chords, original)) {
                        delete edits[index];
                    } else {
                        edits[index] = { original, chords };
                    }
                    this.chordEdits = edits;
                    saveChordEdits(this.currentId, edits);
                },
                // Double click (desktop) or double tap (touch) opens a block for editing; leaving the field applies it.
                tap(event, index) {
                    if (event.pointerType === 'mouse' || this.editingIndex === index) return;
                    const now = Date.now();
                    if (this.lastTap?.index === index && now - this.lastTap.time < 350) {
                        this.lastTap = null;
                        this.startEdit(index);
                    } else {
                        this.lastTap = { index, time: now };
                    }
                },
                startEdit(index) {
                    this.editCancelled = false;
                    this.editingIndex = index;
                },
                finishEdit(index, text) {
                    if (this.editingIndex !== index) return;
                    if (!this.editCancelled) this.updateChords(index, text);
                    this.editingIndex = null;
                },
                cancelEdit() {
                    this.editCancelled = true;
                    this.editingIndex = null;
                },
                resetChords() {
                    this.chordEdits = {};
                    saveChordEdits(this.currentId, {});
                },
                // Lyrics of a section as text (string or list of lines in the structure).
                lyricsText(section) {
                    const lyrics = Array.isArray(section?.lyrics) ? section.lyrics.join('\n') : section?.lyrics;
                    return typeof lyrics === 'string' ? lyrics.trim() : '';
                },
                sectionName(section) { return String(section?.section ?? '').replaceAll('_', ' '); },
                // Display only: the section's lines (marked with "|" or automatic, see chord-lines.js), each a list
                // of steps, each step a list of chords; an identical chord repeated in a line is shown once (G G -> G).
                chordLines(section) {
                    const chords = Array.isArray(section?.chords) ? section.chords.map(String) : [];
                    const passing = this.sectionPassing(section);
                    const lines = chordSegments(chords, this.mapSongCycles, passing)
                        .map(line => line.map(step => step.chords.map((name, i) => ({ name, passing: passing.includes(step.positions[i]) }))));

                    // Shows a chord repeated in a row once per line (G G -> G), dropping cells left empty.
                    return lines.map(line => {
                        const cells = [];
                        line.forEach(step => {
                            const previous = cells[cells.length - 1];
                            const chords = step.filter((chord, i) => chord.name !== (i === 0 ? previous?.[previous.length - 1]?.name : step[i - 1].name));
                            if (chords.length) cells.push(chords);
                        });
                        return cells;
                    }).filter(line => line.length);
                },
                // Passing chords of a map section (positions); none when this device changed its chords.
                sectionPassing(section) {
                    return !section?.edited && Array.isArray(section?.passing) ? section.passing : [];
                },

                init() {
                    this.$watch('settings', value => saveSettings(value), { deep: true });
                    // Each song opens with its own saved layout.
                    this.layout = this.currentId ? loadLayout(this.currentId) : { ...DEFAULT_LAYOUT };
                    this.chordEdits = this.currentId ? loadChordEdits(this.currentId) : {};
                    document.addEventListener('visibilitychange', () => {
                        if (this.playing && document.visibilityState === 'visible') this.requestWakeLock();
                    });
                    this.$watch('currentId', id => {
                        this.layout = id ? loadLayout(id) : { ...DEFAULT_LAYOUT };
                        this.chordEdits = id ? loadChordEdits(id) : {};
                        this.editingIndex = null;
                        this.keyPickerOpen = false;
                        this.resetPlayback();
                        this.$nextTick(() => this.setupSound());
                    });
                    this.$watch('settings.youtubeSound', () => {
                        if (this.playing) this.pause();
                        this.setupSound();
                    });
                    this.$nextTick(() => this.setupSound());
                },
                scaleFont(step) {
                    this.step('fontScale', step * 0.1, 0.7, 1.6);
                },
                // Adjusts a size setting of the current song within [min, max], avoiding float drift.
                step(name, delta, min, max) {
                    const value = Math.round((this.layout[name] + delta) * 100) / 100;
                    this.layout[name] = Math.min(max, Math.max(min, value));
                    if (this.currentId) saveLayout(this.currentId, this.layout);
                },
                get isDefaultLayout() {
                    return Object.entries(DEFAULT_LAYOUT).every(([name, value]) => this.layout[name] === value);
                },
                resetLayout() {
                    this.layout = { ...DEFAULT_LAYOUT };
                    if (this.currentId) saveLayout(this.currentId, null);
                },
                // ---- Section timer ----
                // Start times come from the structure ("start" of each section); a section ends where the
                // next timed section starts, and the last one has no end.
                // ---- View mode: chord map (structure) or chord sheet; each has its own blocks and times ----
                get hasSheet() { return Array.isArray(this.current?.chord_sheet) && this.current.chord_sheet.length > 0; },
                get sheetMode() { return this.hasSheet && this.settings.viewMode === 'sheet'; },
                get timedList() {
                    if (this.sheetMode) return this.current.chord_sheet;
                    return Array.isArray(this.current?.structure) ? this.current.structure : [];
                },
                timedName(index) {
                    const block = this.timedList[index];
                    return this.sheetMode ? (block?.label || this.sectionName(block)) : this.sectionName(this.sections[index]);
                },
                setViewMode(mode) {
                    this.settings.viewMode = mode;
                    // Keep the clock and move the highlight to the matching block of the other view.
                    this.clockSection = this.hasTimeline ? this.sectionAt(this.elapsed) : null;
                    if (this.currentSection !== null) this.showSection(this.clockSection ?? 0);
                },
                chordFor(chord) { return ChordTransposer.transposeChord(chord, this.current?.original_key, this.selectedKey); },
                // Cycles found in any section of the map, so a cycle played once in a section is still shown as a line.
                songCyclesCache: { key: null, value: [] },
                get mapSongCycles() {
                    const lists = this.sections.map(section => Array.isArray(section?.chords) ? section.chords.map(String) : []);
                    const passing = this.sections.map(section => this.sectionPassing(section));
                    const key = JSON.stringify([lists, passing]);
                    if (this.songCyclesCache.key !== key) this.songCyclesCache = { key, value: songCyclesOf(lists, passing) };
                    return this.songCyclesCache.value;
                },
                // Passing chords of each chord sheet section (positions in the section), taken from the map when
                // both have the same chords (chord n of the sheet is chord n of the map).
                get sheetPassing() {
                    const structure = Array.isArray(this.current?.structure) ? this.current.structure : [];
                    const sheet = Array.isArray(this.current?.chord_sheet) ? this.current.chord_sheet : [];
                    const mapChords = structure.flatMap(section => chordsOnly(Array.isArray(section?.chords) ? section.chords.map(String) : []));
                    const sheetLists = sheet.map(section => (section.lines || []).flatMap(line => sheetChords(line)));
                    if (JSON.stringify(mapChords) !== JSON.stringify(sheetLists.flat())) return sheetLists.map(() => []);
                    const global = new Set();
                    let offset = 0;
                    structure.forEach(section => {
                        (Array.isArray(section?.passing) ? section.passing : []).forEach(p => global.add(offset + p));
                        offset += chordsOnly(Array.isArray(section?.chords) ? section.chords.map(String) : []).length;
                    });
                    offset = 0;
                    return sheetLists.map(list => {
                        const positions = list.map((chord, i) => i).filter(i => global.has(offset + i));
                        offset += list.length;
                        return positions;
                    });
                },
                sheetChordPassing(sectionIndex, lineIndex, ordinal) {
                    const lines = this.current?.chord_sheet?.[sectionIndex]?.lines || [];
                    const offset = lines.slice(0, lineIndex).reduce((total, line) => total + sheetChords(line).length, 0);
                    return (this.sheetPassing[sectionIndex] || []).includes(offset + ordinal);
                },
                // Color of a block by its kind (see blockType in chord-lines.js).
                typeStyle(name) { return `--type-color: var(--type-${blockType(name)})`; },
                // Blocks bar: one button per block name of the current view, going to the last time that block is
                // played: the first of its last run (a chorus split into consecutive parts starts at its first part).
                get jumpTargets() {
                    const names = this.sheetMode
                        ? (this.current?.chord_sheet || []).map((section, index) => this.timedName(index))
                        : this.sections.map(section => this.sectionName(section));
                    const targets = [];
                    names.forEach((name, index) => {
                        const key = String(name).trim().toUpperCase();
                        if (!key) return;
                        const target = targets.find(item => item.key === key);
                        if (target) target.index = index;
                        else targets.push({ key, name, index });
                    });
                    const keyAt = (index) => String(names[index] ?? '').trim().toUpperCase();
                    targets.forEach(target => {
                        while (target.index > 0 && keyAt(target.index - 1) === target.key) target.index--;
                    });
                    return targets;
                },
                jumpTo(target) { this.selectSection(target.index); },
                isCurrentTarget(target) {
                    const names = this.sheetMode ? null : this.sections;
                    if (this.currentSection === null) return false;
                    const name = this.sheetMode ? this.timedName(this.currentSection) : this.sectionName(names[this.currentSection]);
                    return String(name).trim().toUpperCase() === target.key;
                },
                // ChordPro line -> comment ("{c: Riff 2}") or units of [chord over text], split between words
                // so long lines wrap on small screens without losing the chord position.
                sheetLine(line) {
                    const text = String(line ?? '');
                    const comment = /^\{\s*(?:c|comment)\s*:\s*(.*?)\s*\}$/i.exec(text.trim());
                    if (comment) return { type: 'comment', text: comment[1] };

                    const pieces = [];
                    const pattern = /\[([^\]]+)\]/g;
                    let chord = null, last = 0, match;
                    while ((match = pattern.exec(text))) {
                        if (chord !== null || match.index > last) pieces.push({ chord, text: text.slice(last, match.index) });
                        chord = match[1];
                        last = pattern.lastIndex;
                    }
                    pieces.push({ chord, text: text.slice(last) });

                    const units = [];
                    pieces.forEach(piece => {
                        const words = piece.text.match(/\S+\s*|\s+/g) ?? [''];
                        words.forEach((word, i) => units.push({ chord: i === 0 ? piece.chord : null, text: word }));
                    });
                    const cleaned = units.filter(unit => unit.chord !== null || unit.text !== '');
                    let ordinal = 0;
                    cleaned.forEach(unit => { if (unit.chord !== null) unit.ordinal = ordinal++; });
                    // A chord needs its own width only when the text up to the next chord is too short for its name
                    // (bold chord letters are wider than lyric letters, hence the margin).
                    cleaned.forEach((unit, index) => {
                        if (unit.chord === null) return;
                        let distance = 0;
                        for (let next = index; next < cleaned.length; next++) {
                            if (next > index && cleaned[next].chord !== null) {
                                unit.wide = distance < Math.ceil(this.chordFor(unit.chord).length * 1.5) + 2;
                                return;
                            }
                            distance += cleaned[next].text.length;
                        }
                        unit.wide = false;
                    });

                    return {
                        type: 'line',
                        units: cleaned,
                        hasChords: cleaned.some(unit => unit.chord !== null),
                        hasText: cleaned.some(unit => unit.text.trim() !== '' && !/^[()|\s]+$/.test(unit.text)),
                    };
                },

                sectionStart(index) { return parseStart(this.timedList?.[index]?.start); },
                get hasTimeline() { return this.timedList.some((section, index) => this.sectionStart(index) !== null); },
                get nextSection() {
                    return this.currentSection !== null && this.currentSection + 1 < this.timedList.length ? this.currentSection + 1 : null;
                },
                sectionEnd(index) {
                    for (let next = index + 1; next < this.timedList.length; next++) {
                        const start = this.sectionStart(next);
                        if (start !== null) return start;
                    }
                    return null;
                },
                sectionRange(index) {
                    const start = this.sectionStart(index);
                    if (start === null) return '';
                    const end = this.sectionEnd(index);
                    return end === null ? this.formatTime(start) : `${this.formatTime(start)}–${this.formatTime(end)}`;
                },
                sectionState(index) {
                    return {
                        'is-current': index === this.currentSection,
                        'is-next': index === this.nextSection,
                        'is-past': this.currentSection !== null && index < this.currentSection,
                    };
                },
                get currentProgress() {
                    if (this.currentSection === null) return null;
                    const start = this.sectionStart(this.currentSection);
                    const end = this.sectionEnd(this.currentSection);
                    if (start === null || end === null || end <= start) return null;
                    return Math.min(1, Math.max(0, (this.elapsed - start) / (end - start)));
                },
                // Timed section with the latest start not after `time`.
                sectionAt(time) {
                    let found = null;
                    this.timedList.forEach((section, index) => {
                        const start = this.sectionStart(index);
                        if (start !== null && start <= time && (found === null || start >= this.sectionStart(found))) found = index;
                    });
                    return found ?? 0;
                },
                formatTime(seconds) {
                    const total = Math.max(0, Math.floor(seconds));
                    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
                },

                togglePlay() { this.playing ? this.pause() : this.play(); },
                // `fromVideo`: started/paused from the YouTube player itself, so the video is not commanded again.
                play(fromVideo = false) {
                    if (this.playing) return;
                    if (this.soundActive && !fromVideo) {
                        soundPlayer.seekTo(this.elapsed, true);
                        soundPlayer.playVideo();
                    }
                    this.startedAt = performance.now() - this.elapsed * 1000;
                    this.playing = true;
                    this.clockSection = this.hasTimeline ? this.sectionAt(this.elapsed) : null;
                    if (this.currentSection === null) this.showSection(this.clockSection ?? 0);
                    this.ticker = setInterval(() => this.tick(), 250);
                    this.requestWakeLock();
                },
                pause(fromVideo = false) {
                    if (!this.playing) return;
                    if (this.soundActive && !fromVideo) soundPlayer.pauseVideo();
                    this.elapsed = this.soundActive ? soundPlayer.getCurrentTime() : (performance.now() - this.startedAt) / 1000;
                    this.playing = false;
                    clearInterval(this.ticker);
                    this.releaseWakeLock();
                },
                tick() {
                    // With the song sound on, the clock follows the video time.
                    if (this.soundActive) {
                        this.elapsed = soundPlayer.getCurrentTime();
                        this.startedAt = performance.now() - this.elapsed * 1000;
                    } else {
                        this.elapsed = (performance.now() - this.startedAt) / 1000;
                    }
                    if (this.hasTimeline) {
                        const index = this.sectionAt(this.elapsed);
                        if (index !== this.clockSection) {
                            this.clockSection = index;
                            this.showSection(index);
                        }
                    }
                },
                // Moves the timer to `time` without changing play/pause.
                seek(time) {
                    this.elapsed = time;
                    if (this.soundActive) soundPlayer.seekTo(time, true);
                    if (this.playing) this.startedAt = performance.now() - time * 1000;
                    this.clockSection = this.hasTimeline ? this.sectionAt(time) : null;
                },
                resetPlayback() {
                    this.pause();
                    this.elapsed = 0;
                    this.currentSection = null;
                    this.clockSection = null;
                },
                restart() {
                    const wasPlaying = this.playing;
                    this.resetPlayback();
                    if (wasPlaying) this.play();
                },

                // Tapping a block marks it as the current section and re-syncs the timer to its start time.
                selectSection(index) {
                    if (this.editingIndex !== null) return;
                    const start = this.sectionStart(index);
                    if (start !== null) this.seek(start);
                    this.showSection(index);
                },
                showSection(index) {
                    this.currentSection = index;
                    this.$nextTick(() => {
                        const section = document.querySelectorAll('.sections section')[index];
                        if (!section) return;
                        section.style.scrollMarginTop = `${(document.querySelector('.chrome')?.offsetHeight ?? 0) + 8}px`;
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                },

                // ---- Song sound (YouTube) ----
                soundReady: false,
                get videoId() { return youtubeIdFrom(this.current?.youtube_url); },
                get soundActive() { return this.settings.youtubeSound && !!this.videoId && this.soundReady && !!soundPlayer; },
                // Creates the player, or loads the current song's video in it (paused).
                setupSound() {
                    if (!this.settings.youtubeSound || !this.videoId) {
                        soundPlayer?.pauseVideo?.();
                        return;
                    }
                    if (soundPlayer) {
                        if (this.soundReady) soundPlayer.cueVideoById(this.videoId, this.elapsed);
                        return;
                    }
                    const create = () => {
                        soundPlayer = new YT.Player('yt-sound', {
                            videoId: this.videoId,
                            playerVars: { playsinline: 1, rel: 0, controls: 1 },
                            events: {
                                onReady: () => this.soundReady = true,
                                // Playing or pausing in the YouTube player also drives the section timer.
                                onStateChange: (event) => {
                                    if (event.data === YT.PlayerState.PLAYING && !this.playing) this.play(true);
                                    if ((event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) && this.playing) this.pause(true);
                                },
                            },
                        });
                    };
                    if (window.YT?.Player) return create();
                    window.onYouTubeIframeAPIReady = create;
                    if (!document.getElementById('yt-iframe-api')) {
                        const script = document.createElement('script');
                        script.id = 'yt-iframe-api';
                        script.src = 'https://www.youtube.com/iframe_api';
                        document.head.appendChild(script);
                    }
                },

                // Keeps the screen on while the timer runs (mobile); silently ignored where unsupported.
                async requestWakeLock() {
                    try {
                        this.wakeLock = await navigator.wakeLock?.request('screen');
                    } catch (e) {}
                },
                releaseWakeLock() {
                    this.wakeLock?.release?.().catch(() => {});
                    this.wakeLock = null;
                },

                pickKey(key) {
                    this.keyPickerOpen = false;
                    if (key !== this.selectedKey) this.changeKey(key);
                },

                go(index) {
                    if (index >= 0 && index < this.songs.length) {
                        this.currentId = this.songs[index].id;
                        window.scrollTo({ top: 0 });
                    }
                },
                onKeydown(event) {
                    if (event.key === 'Escape') this.optionsOpen = false;
                    if (this.panelOpen || this.removing || ['INPUT', 'SELECT', 'TEXTAREA'].includes(event.target.tagName)) return;
                    if (event.key === ' ' && this.current) { event.preventDefault(); this.togglePlay(); }
                    if (['ArrowRight', 'PageDown'].includes(event.key)) { event.preventDefault(); this.go(this.currentIndex + 1); }
                    if (['ArrowLeft', 'PageUp'].includes(event.key)) { event.preventDefault(); this.go(this.currentIndex - 1); }
                },
                openSetlist(id) {
                    if (id) window.location.href = `${config.urls.setlist}/${id}`;
                },

                async request(method, url, body) {
                    const response = await fetch(url, {
                        method,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: body === undefined ? undefined : JSON.stringify(body),
                    });

                    if (!response.ok) {
                        let message = response.status === 419 ? this.messages.sessionExpired : this.messages.saveError;
                        try {
                            const json = await response.json();
                            message = json.errors ? Object.values(json.errors).flat()[0] : (response.status === 419 ? message : json.message || message);
                        } catch (e) {}
                        throw new Error(message);
                    }

                    return response.status === 204 ? null : response.json();
                },
                notify(text, error = false) {
                    this.toast = { text, error };
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => this.toast = { text: '', error: false }, error ? 4000 : 1500);
                },
                songUrl(song, suffix = '') { return `${config.urls.setlist}/${setlist.id}/songs/${song.id}${suffix}`; },

                async changeKey(key) {
                    const song = this.current;
                    const previous = song.setlist_key;
                    song.setlist_key = key || null;
                    this.savingKey = true;
                    try {
                        await this.request('PATCH', this.songUrl(song, '/key'), { musical_key: song.setlist_key });
                        this.notify(this.messages.saved);
                    } catch (error) {
                        song.setlist_key = previous;
                        this.notify(error.message, true);
                    } finally {
                        this.savingKey = false;
                    }
                },

                openPanel(withSearch = false) {
                    this.optionsOpen = false;
                    this.panelOpen = true;
                    if (withSearch) this.openSearch();
                },
                openSearch() {
                    this.searchOpen = true;
                    this.search();
                    this.$nextTick(() => this.$refs.search?.focus());
                },
                async search() {
                    try {
                        const response = await fetch(`${config.urls.search}?q=${encodeURIComponent(this.term)}`, { headers: { Accept: 'application/json' } });
                        this.results = await response.json();
                    } catch (error) {
                        this.results = [];
                    }
                    this.searched = true;
                },
                isInSetlist(song) { return this.songs.some(item => item.id === song.id); },
                async addSong(song) {
                    if (this.isInSetlist(song)) return;
                    this.adding = true;
                    try {
                        const added = await this.request('POST', `${config.urls.setlist}/${setlist.id}/songs`, { song_id: song.id });
                        this.songs.push(added);
                        if (!this.currentId) this.currentId = added.id;
                        this.notify(this.messages.added);
                    } catch (error) {
                        this.notify(error.message, true);
                    } finally {
                        this.adding = false;
                    }
                },

                askRemove(song) { this.removing = song; },
                async confirmRemove() {
                    const song = this.removing;
                    this.removing = null;
                    try {
                        await this.request('DELETE', this.songUrl(song));
                        const index = this.songs.findIndex(item => item.id === song.id);
                        this.songs.splice(index, 1);
                        this.renumber();
                        if (this.currentId === song.id) {
                            this.currentId = (this.songs[index] ?? this.songs[index - 1])?.id ?? null;
                        }
                        this.notify(this.messages.removed);
                    } catch (error) {
                        this.notify(error.message, true);
                    }
                },

                renumber() { this.songs.forEach((song, index) => song.position = index + 1); },

                // Pointer-based drag and drop, so it works with mouse and touch.
                startDrag(event, songId) {
                    event.preventDefault();
                    this.dragId = songId;
                    const before = this.songs.map(song => song.id);

                    const move = (moveEvent) => {
                        const items = [...this.$refs.list.querySelectorAll('.song-item')];
                        const target = items.find(item => {
                            const rect = item.getBoundingClientRect();
                            return moveEvent.clientY >= rect.top && moveEvent.clientY <= rect.bottom;
                        });
                        if (!target) return;

                        const from = this.songs.findIndex(song => song.id === this.dragId);
                        const to = this.songs.findIndex(song => song.id === Number(target.dataset.id));
                        if (from !== to && to !== -1) {
                            this.songs.splice(to, 0, this.songs.splice(from, 1)[0]);
                        }
                    };
                    const end = () => {
                        window.removeEventListener('pointermove', move);
                        window.removeEventListener('pointerup', end);
                        window.removeEventListener('pointercancel', end);
                        this.dragId = null;

                        const after = this.songs.map(song => song.id);
                        if (after.join() !== before.join()) this.saveOrder(before);
                    };

                    window.addEventListener('pointermove', move);
                    window.addEventListener('pointerup', end);
                    window.addEventListener('pointercancel', end);
                },
                async saveOrder(previousOrder) {
                    this.renumber();
                    try {
                        await this.request('PUT', `${config.urls.setlist}/${setlist.id}/order`,
                            this.songs.map(song => ({ song_id: song.id, position: song.position })));
                        this.notify(this.messages.saved);
                    } catch (error) {
                        this.songs.sort((a, b) => previousOrder.indexOf(a.id) - previousOrder.indexOf(b.id));
                        this.renumber();
                        this.notify(error.message, true);
                    }
                },
            };
        }
    </script>
</body>

</html>
