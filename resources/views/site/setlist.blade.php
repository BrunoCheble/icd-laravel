<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $data['title'] ?? __('Setlist') }} · {{ config('app.name', 'Laravel') }}</title>

    {{-- Installable app (PWA): manifest, icons and the service worker registered below --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#111318">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="{{ __('Setlist') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <script>
        // The install offer (Android / desktop Chrome) can come before the page is ready: kept for the install button.
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            window.installPrompt = event;
            window.dispatchEvent(new CustomEvent('install-available'));
        });
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register(@js(asset('sw.js')), { scope: @js(url('repertoire')) }).catch(() => {}));
        }
    </script>

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
        /* Song dragged sideways (see dragSong): no horizontal page scroll while it is off its place */
        .stage.is-swiping { overflow-x: clip; }
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
        /* Suggesting chord corrections: chords can be clicked; a changed one in orange, a removed one struck out */
        .is-suggesting .seg-chord, .is-suggesting .chord-step > span, .is-suggesting .live-beat > span { cursor: pointer; }
        .is-suggesting .seg-chord:hover > span, .is-suggesting .chord-step > span:hover, .is-suggesting .live-beat > span:hover { outline: 1px dashed var(--accent); border-radius: .2rem; }
        .seg-chord.is-suggested > span, .chord-step .is-suggested, .live-beat .is-suggested { color: #ea580c; text-decoration: underline wavy #ea580c; text-underline-offset: .2em; }
        .seg-chord.is-removed > span, .chord-step .is-removed, .live-beat .is-removed { color: #9ca3af; text-decoration: line-through; }
        .suggest-pop { position: fixed; z-index: 70; width: min(17rem, calc(100vw - 1.5rem)); padding: .6rem; border: 1px solid var(--accent); border-radius: .6rem; background: var(--surface); color: var(--text); box-shadow: 0 12px 30px rgba(0, 0, 0, .3); }
        .suggest-pop input { width: 100%; padding: .35rem .5rem; border: 1px solid var(--border); border-radius: .4rem; background: var(--surface-2); color: var(--text); font-weight: 700; font-size: 1.05rem; }
        .suggest-pop .row { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
        .suggest-pop .row .btn { flex: 1; }
        .suggest-pop .apply-all { display: flex; gap: .4rem; align-items: flex-start; margin-top: .45rem; font-size: .85rem; cursor: pointer; }
        .suggest-pop .apply-all input { width: auto; margin-top: .15rem; }
        .instrument-badge { padding: .1rem .55rem; border-radius: 999px; background: var(--accent); color: var(--accent-text); font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .song-meta strong { color: var(--text); font-weight: 600; }
        .hint { font-size: .85rem; color: var(--muted); }

        .sections { margin-top: .6rem; display: grid; grid-template-columns: minmax(0, 1fr); gap: calc(var(--section-gap, 1) * .6rem); font-size: calc(clamp(1.1rem, 3.6vw, 1.7rem) * var(--chord-scale, 1)); }
        /* A block selected: a vertical swipe on the blocks goes to the block above / below (see onTouchMove), never a page scroll */
        .sections.block-swipe { touch-action: pan-x pinch-zoom; }
        /* Each block: a side line and its chords in the color of its kind (intro, chorus...) */
        .sections section {
            break-inside: avoid; position: relative; border-radius: .5rem;
            padding: .2rem .5rem .2rem .6rem; margin: 0 -.5rem; transition: background-color .2s, opacity .2s, box-shadow .2s;
            border-left: 3px solid var(--type-color, transparent);
        }
        /* "Back to the start" marker of the map: only its label */
        .sections section.is-return > :not(.return-label) { display: none; }
        .sections section.is-return { border-left: 3px dashed var(--muted, #9ca3af); cursor: pointer; }
        .return-label { grid-column: 1 / -1; padding: .15rem 0; font-size: calc(.8rem * var(--label-scale, 1)); font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--muted, #6b7280); }
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
        /* Live mode: bars counted by the BPM, the current beat lit */
        /* Room below the last bars, so any block can be brought to the top of the screen. */
        .live-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .35rem; padding-bottom: 70vh; align-content: start; }
        @media (max-width: 700px) { .live-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .live-bar { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: .5rem; padding: .3rem .4rem .45rem; cursor: pointer; }
        .live-bar.is-current { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent) inset; }
        .live-bar.has-block { border-top: 3px solid var(--type-color, var(--accent)); }
        .live-bar .number { position: absolute; top: .15rem; right: .35rem; font-size: .62rem; color: var(--muted); }
        .live-block { font-size: calc(.7rem * var(--label-scale, 1)); font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--type-color, var(--accent)); min-height: .95rem; margin-right: 1.3rem; }
        .live-beats { display: grid; gap: .15rem; margin-top: .2rem; }
        .live-beat { min-width: 0; padding: .1rem .2rem; border-radius: .3rem; font-weight: 800; font-size: calc(1.1rem * var(--chord-scale, 1)); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .live-beat.is-repeat { color: var(--muted); font-weight: 400; }
        .live-beat.is-on { background: var(--accent); color: var(--accent-text); }
        .live-beat .and { font-size: .8em; border-left: 1px dashed var(--muted); padding-left: .2rem; margin-left: .2rem; }
        .live-beat .and.is-empty { color: var(--muted); font-weight: 400; opacity: .6; }
        /* Live mode, current and next bar only: big chords, the next one dimmed (tapping it goes there). */
        /* The two bars in the middle of the visible part of the page (below the top bars, above the blocks bar). */
        .live-focus { display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; align-content: center;
            max-width: 64rem; margin-inline: auto; min-height: calc(100vh - 16rem); min-height: calc(100dvh - 16rem); }
        .focus-bar { background: var(--surface); border: 1px solid var(--border); border-top: 4px solid var(--type-color, var(--accent)); border-radius: .75rem; padding: .6rem .8rem 1rem; }
        .focus-bar.is-now { box-shadow: 0 0 0 2px var(--accent) inset; }
        /* The bars around the current one: smaller and dimmed (tapping them goes there). */
        .focus-bar.is-next, .focus-bar.is-previous { --focus-size: 1.6rem; opacity: .55; cursor: pointer; padding-bottom: .6rem; }
        .focus-bar.is-empty { visibility: hidden; }
        .focus-head { display: flex; align-items: baseline; gap: .6rem; flex-wrap: wrap; }
        .focus-block { font-size: calc(.95rem * var(--label-scale, 1)); font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--type-color, var(--accent)); }
        .focus-starts { font-size: calc(.8rem * var(--label-scale, 1)); font-weight: 800; text-transform: uppercase; color: var(--muted); }
        .focus-number { margin-left: auto; font-size: .75rem; color: var(--muted); font-variant-numeric: tabular-nums; }
        .focus-beats { display: grid; gap: .3rem; margin-top: .6rem; }
        /* --focus-fit: how much the chords of a bar shrink so the longest one fits its beat (see fitFocusText). */
        .focus-beat { min-width: 0; padding: .35rem .3rem; border-radius: .5rem; font-weight: 800; font-size: calc(var(--focus-size, 2.8rem) * var(--chord-scale, 1) * var(--focus-fit, 1)); line-height: 1.1; text-align: center; white-space: nowrap; overflow: hidden; }
        .focus-beat.is-repeat { color: var(--muted); font-weight: 400; }
        .focus-beat.is-on { background: var(--accent); color: var(--accent-text); }
        .focus-beat .and { font-size: .55em; vertical-align: middle; border-left: 1px dashed var(--muted); padding-left: .25rem; margin-left: .25rem; }
        .live-tempo { display: inline-flex; align-items: center; gap: .25rem; }
        .live-tempo .bpm { min-width: 4.2rem; text-align: center; font-weight: 700; font-variant-numeric: tabular-nums; font-size: .9rem; }
        .mode-toggle { display: inline-flex; border: 1px solid var(--border); border-radius: 999px; overflow: hidden; }
        .mode-toggle button { padding: .15rem .7rem; font-size: .85rem; font-weight: 600; background: none; color: var(--muted); border: 0; cursor: pointer; min-height: 32px; }
        .mode-toggle button.is-active { background: var(--accent); color: var(--accent-text); }
        .section-lyrics { margin-top: .2rem; font-size: calc(.95rem * var(--label-scale, 1)); color: var(--muted); line-height: 1.35; white-space: pre-line; }
        /* Room between chord lines: set in the options ("line spacing"), per song */
        /* Map as a bar chart: one cell per bar, bars per row as written, each block on new rows. */
        .bar-chart-head { margin: 0 0 .5rem; font-size: .8rem; font-weight: 700; color: var(--muted); letter-spacing: .03em; }
        .bar-chart { font-weight: 700; color: var(--type-color, var(--chord)); }
        .bar-row { display: grid; grid-template-columns: repeat(var(--bars-per-row, 4), minmax(0, 1fr)); border-left: 2px solid var(--border); }
        .bar-row + .bar-row { margin-top: calc(.3rem * var(--line-gap, 1)); }
        .map-bar { min-width: 0; padding: .1rem .45rem; border-right: 2px solid var(--border); border-radius: .2rem; font-size: calc(1.05rem * var(--chord-scale, 1)); line-height: 1.4; }
        .map-bar.is-playing { background: color-mix(in srgb, var(--accent) 14%, transparent); }
        .bar-chords { display: flex; flex-wrap: wrap; column-gap: .45em; user-select: none; -webkit-user-select: none; }
        .bar-chords .bar-slash, .bar-repeat { color: var(--muted); font-weight: 400; }
        .bar-chords .is-off { font-size: .78em; align-self: flex-end; }
        .bar-chords .is-playing { margin: 0 -.2em; padding: 0 .2em; border-radius: .25rem; background: var(--accent); color: var(--accent-text); }
        .bar-chords .is-passing { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .22em; }
        .is-suggesting .bar-chords > span:not(.bar-slash) { cursor: pointer; }
        .section-chords { font-weight: 700; color: var(--type-color, var(--chord)); line-height: calc(1.3 * var(--line-gap, 1)); }
        .chord-line { display: flex; flex-wrap: wrap; column-gap: 1em; user-select: none; -webkit-user-select: none; }
        .chord-step { white-space: pre; }
        /* Passing chords: dotted underline */
        .chord-step .is-passing, .seg-chord.is-passing { text-decoration: underline dotted; text-decoration-thickness: 2px; text-underline-offset: .22em; }
        /* The chord being played (clock, audio or video) */
        .seg-chord.is-playing > span, .chord-step .is-playing { margin: 0 -.2em; padding: 0 .2em; border-radius: .25rem; background: var(--accent); color: var(--accent-text); }
        /* Repeated cycles in columns: each column is as wide as its widest chord across the lines. */
        .section-chords.is-aligned { display: grid; grid-template-columns: repeat(var(--chord-columns, 1), max-content); column-gap: 1em; justify-content: start; }
        .section-chords.is-aligned .chord-line { display: grid; grid-column: 1 / -1; grid-template-columns: subgrid; }
        .section-chords { touch-action: manipulation; }
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
        .install { margin: .75rem 0; }
        .offline-badge { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; flex-shrink: 0; color: var(--danger); }
        .offline-badge::after { content: ''; position: absolute; width: 1.45rem; height: 2px; background: currentColor; border-radius: 1px; transform: rotate(-45deg); box-shadow: 0 0 0 2px var(--surface); }
        .install .btn { width: 100%; justify-content: center; }
        .install-steps { margin: .5rem 0 1rem; padding-left: 1.25rem; line-height: 1.6; list-style: decimal; }
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
        /* Handle to move the video (the YouTube frame itself takes the pointer) */
        .yt-mini-drag {
            position: absolute; top: .25rem; left: .25rem; z-index: 1; width: 32px; height: 28px; border-radius: 999px;
            border: 0; background: rgba(0, 0, 0, .65); color: #fff; font-size: .85rem; cursor: grab; touch-action: none;
        }
        .yt-mini.is-dragging { opacity: .85; }
        .yt-mini.is-dragging iframe { pointer-events: none; }
        .yt-mini.is-dragging .yt-mini-drag { cursor: grabbing; }
        /* Above the blocks bar when it is shown */
        .has-jump-bar .yt-mini { bottom: calc(4.2rem + max(.75rem, env(safe-area-inset-bottom))); }

        /* Blocks bar: one button per block of the song; a tap goes to the last block with that name */
        .jump-bar {
            position: fixed; z-index: 34; left: 0; right: 0; bottom: 0;
            display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none;
            padding: .4rem max(.5rem, env(safe-area-inset-left)) max(.4rem, env(safe-area-inset-bottom));
            background: var(--surface); border-top: 1px solid var(--border);
        }
        .jump-bar::-webkit-scrollbar { display: none; }
        .jump-bar button {
            flex-shrink: 0; min-height: 48px; min-width: 4.5rem; padding: 0 1.1rem; border-radius: 999px; cursor: pointer;
            border: 1px solid var(--type-color, var(--border)); background: none; color: var(--type-color, var(--text));
            font-size: .9rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap;
        }
        .jump-bar button.is-current { background: var(--type-color, var(--accent)); color: var(--surface); }
        .has-jump-bar .stage { padding-bottom: 4.8rem; }
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
            'catalog' => route('site.setlist.songs.catalog'),
        ],
        'messages' => [
            'confirmRemove' => __('Remove ":title" from this setlist?'),
            'saved' => __('Saved'),
            'invalidChord' => __('Use a chord name, e.g. G, Am7, C/E, D4.'),
            'discardSuggestion' => __('Discard the chord changes of this song?'),
            'suggestionSent' => __('Suggestion sent: an admin will review it. Thank you!'),
            'suggestionOffline' => __('No connection: the changes stay on this device; send them when you are online.'),
            'saveError' => __('Could not save the change.'),
            'added' => __('Song added to the setlist.'),
            'removed' => __('Song removed from the setlist.'),
            'sessionExpired' => __('Your session expired. Please reload the page.'),
            'offlineOnly' => __('No connection: changed only on this device, not saved.'),
            'offlineNoCatalog' => __('No connection: download the setlist for offline to add songs.'),
        ],
    ]))" @keydown.window="onKeydown($event)" :class="{ 'has-jump-bar': current && jumpTargets.length > 1, 'is-suggesting': settings.suggest && !current?.instrumentVersion }">

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
            {{-- No connection: the page is the copy saved on this device --}}
            <span class="offline-badge" x-show="!online" x-cloak title="{{ __('No connection: showing the copy saved on this device') }}" aria-label="{{ __('No connection') }}"><i class="fa-solid fa-wifi"></i></span>
            <button type="button" class="btn btn-icon" :class="{ 'is-active': optionsOpen }" @click="optionsOpen = !optionsOpen" :aria-expanded="optionsOpen" aria-label="{{ __('Options') }}" title="{{ __('Options') }}"><i class="fa-solid fa-gear"></i></button>
        </header>

        @if ($data)
            {{-- Section timer: follows the "start" times of the structure (the recording); tapping a block re-syncs it --}}
            <div class="player" x-show="current && !bpmMode">
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
            </div>

            {{-- Counting the bars by the BPM (each musician on his device), in every view of a song with bars: tapping a
                 block or a bar goes on from it --}}
            <div class="player" x-show="current && bpmMode" x-cloak>
                <button type="button" class="btn btn-icon" @click="toggleLive()" :aria-label="live.running ? @js(__('Stop')) : @js(__('Start'))"><i class="fa-solid" :class="live.running ? 'fa-stop' : 'fa-play'"></i></button>
                <span class="player-time" x-text="`${liveBar + 1}/${liveGrid?.bars.length ?? 0}`"></span>
                <div class="player-section">
                    <div class="names">
                        <strong x-text="liveChordNow ? chordFor(liveChordNow) : '—'"></strong>
                        <template x-if="liveChordNext">
                            <span> <i class="fa-solid fa-arrow-right" style="font-size: .8em;"></i> <span x-text="chordFor(liveChordNext)"></span></span>
                        </template>
                    </div>
                </div>
                <span class="live-tempo">
                    <button type="button" class="btn bpm" @click="tapTempo()" :title="@js(__('Tap the beat to set the tempo'))"><span x-text="`${Math.round(liveBpm)} BPM`"></span> <i class="fa-solid fa-hand-pointer" style="font-size: .8em; opacity: .7;"></i></button>
                </span>
                <button type="button" class="btn btn-icon" :class="{ 'is-active': live.click }" @click="live.click = !live.click" :title="@js(__('Metronome'))" aria-label="{{ __('Metronome') }}"><i class="fa-solid fa-drum"></i></button>
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
                        <template x-for="choice in keyChoices" :key="choice.value">
                            <option :value="choice.value" x-text="choice.label" :selected="choice.value === selectedKey"></option>
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
                    <label class="switch" :title="audioUrl || videoId ? '' : @js(__('This song has no audio or YouTube video.'))"><input type="checkbox" x-model="settings.youtubeSound"> {{ __('Song sound') }}</label>
                </div>

                <div class="option-row" x-show="instrumentOptions.length">
                    <span class="option-label">{{ __('My instrument') }}</span>
                    <select class="control" x-model="settings.instrument" style="width: auto;">
                        <option value="">{{ __('Base (everyone)') }}</option>
                        <template x-for="name in instrumentOptions" :key="name">
                            <option :value="name" x-text="name" :selected="name === settings.instrument"></option>
                        </template>
                    </select>
                    <span class="hint">{{ __('Songs with a version for your instrument show its chords; the others show the base.') }}</span>
                </div>

                <div class="option-row">
                    <span class="option-label">{{ __('Suggest chords') }}</span>
                    <label class="switch"><input type="checkbox" x-model="settings.suggest"> {{ __('Correct chords of the songs (sent for review)') }}</label>
                    <template x-if="settings.suggest">
                        <div style="display: grid; gap: .45rem; width: 100%;">
                            <span class="hint" x-show="current?.instrumentVersion">{{ __('Choose "Base" in My instrument to suggest corrections.') }}</span>
                            <span class="hint" x-show="!current?.instrumentVersion">{{ __('Tap a chord to change or remove it. Nothing changes for the others until an admin approves.') }}</span>
                            <input type="text" class="control" x-model="suggestAuthor" @change="saveSuggestAuthor()" placeholder="{{ __('Your name (optional)') }}" maxlength="60">
                            <textarea class="control" rows="2" x-model="suggestNote" placeholder="{{ __('Comment (optional)') }}" maxlength="500"></textarea>
                            <div style="display: flex; flex-wrap: wrap; gap: .4rem; align-items: center;">
                                <button type="button" class="btn btn-accent" @click="sendSuggestion()" :disabled="!suggestCount || sendingSuggestion"
                                    x-text="@js(__('Send suggestion (:count)')).replace(':count', suggestCount)"></button>
                                <button type="button" class="btn" x-show="suggestCount" @click="discardSuggestion()">{{ __('Discard') }}</button>
                                <span class="hint" x-show="otherSuggestions" x-text="@js(__('Other songs with changes not sent: :count')).replace(':count', otherSuggestions)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Chords edited by block on this device before editing moved to each chord (suggestions) --}}
                <div class="option-row" x-show="current && hasChordEdits">
                    <span class="option-label">{{ __('Chords') }}</span>
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
                            <span class="instrument-badge" x-show="current.instrumentVersion" x-text="current.instrumentVersion" :title="@js(__('Version of this song for your instrument'))"></span>
                            <span class="key-picker-anchor" @click.outside="keyPickerOpen = false" @keydown.escape.window="keyPickerOpen = false">
                                <button type="button" class="key-badge" @click.stop="keyPickerOpen = !keyPickerOpen" :aria-expanded="keyPickerOpen" :title="@js(__('Change key'))">
                                    <span x-text="selectedKey || '—'"></span>
                                    <small x-show="current.original_key && current.original_key !== selectedKey" x-text="`(${current.original_key})`"></small>
                                </button>
                                <div class="key-picker" x-show="keyPickerOpen" x-cloak @click.stop role="listbox" :aria-label="@js(__('Key'))">
                                    <template x-for="choice in [...extraKeys.map(key => ({ value: key, label: key })), ...keyChoices]" :key="choice.value">
                                        <button type="button" class="key-option" role="option"
                                            :class="{ 'is-selected': choice.value === selectedKey, 'is-original': choice.value === current.original_key }"
                                            :aria-selected="choice.value === selectedKey" :disabled="savingKey"
                                            :title="choice.value === current.original_key ? @js(__('Original key')) : ''"
                                            @click="pickKey(choice.value)" x-text="choice.label"></button>
                                    </template>
                                </div>
                            </span>
                            <span class="mode-toggle" x-show="hasSheet || hasLive" role="group" :aria-label="@js(__('View'))">
                                <button type="button" :class="{ 'is-active': !sheetMode && !liveMode }" @click.stop="setViewMode('map')">{{ __('Map') }}</button>
                                <button type="button" x-show="hasSheet" :class="{ 'is-active': sheetMode }" @click.stop="setViewMode('sheet')">{{ __('Chord sheet') }}</button>
                                <button type="button" x-show="hasLive" :class="{ 'is-active': liveMode }" @click.stop="setViewMode('live')"><i class="fa-solid fa-circle" style="font-size: .55em; color: #ef4444;"></i> {{ __('Live') }}</button>
                            </span>
                        </div>
                        <p class="song-meta" x-show="settings.showDetails">
                            <span x-text="current.artist"></span>
                            <template x-if="current.minister_name">
                                <span> · {{ __('Minister') }}: <strong x-text="current.minister_name"></strong></span>
                            </template>
                        </p>

                        {{-- Live mode: the bars of the map (from the chord durations), counted by the BPM. Only the current bar
                             and the next one, big; while correcting chords, every bar (their chords can be corrected there). --}}
                        <template x-if="liveMode">
                        <div :style="`--chord-scale: ${layout.fontScale}; --label-scale: ${layout.labelScale}`">
                        <template x-if="!liveEdit">
                            <div class="live-focus" x-init="fitFocus()" @resize.window.debounce.150ms="fitFocus()">
                                <template x-for="item in focusBars" :key="`${current.id}-focus-${item.index}-${item.role}`">
                                    <div class="focus-bar" :class="{ 'is-now': item.now, 'is-next': item.role === 'next', 'is-previous': item.role === 'previous', 'is-empty': item.empty }" :style="typeStyle(item.block)"
                                        @click="!item.now && !item.empty && goToLiveBar(item.index)">
                                        <div class="focus-head">
                                            <span class="focus-block" x-text="item.restart ? '↺ ' + @js(__('Back to the start')) + ' · ' + item.block : item.block"></span>
                                            <span class="focus-starts" x-show="item.starts" x-text="item.starts ? '→ ' + item.starts : ''"></span>
                                            <span class="focus-number" x-text="`${item.now ? @js(__('Now')) : (item.role === 'next' ? @js(__('Next')) : @js(__('Before')))} · ${item.index + 1}/${liveGrid.bars.length}`"></span>
                                        </div>
                                        <div class="focus-beats" :style="`grid-template-columns: repeat(${item.bar.beats.length}, minmax(0, 1fr))`">
                                            <template x-for="(beat, i) in item.bar.beats" :key="i">
                                                <span class="focus-beat" :class="{ 'is-repeat': beat.repeat, 'is-on': item.now && i === liveBeat }">
                                                    <span x-text="beat.repeat ? '/' : (beat.chord ? chordFor(beat.chord) : '–')"></span><template x-if="beat.and !== null"><span class="and" x-text="beat.and ? chordFor(beat.and) : '–'"></span></template>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <p class="hint" x-show="!focusBars.some(item => item.role === 'next')">{{ __('Last bar of the song.') }}</p>
                            </div>
                        </template>
                        <template x-if="liveEdit">
                        <div class="live-grid">
                            <template x-for="(bar, b) in liveGrid.bars" :key="`${current.id}-live-${b}`">
                                <div class="live-bar" :class="{ 'is-current': b === liveBar, 'has-block': bar.blocks.some(block => !block.marker) }"
                                    :style="bar.blocks.length ? typeStyle(bar.blocks[0].name) : ''" @click="goToLiveBar(b)" :data-live-bar="b">
                                    <span class="number" x-text="b + 1"></span>
                                    <div class="live-block"><template x-for="block in bar.blocks" :key="block.tick"><span x-text="(block.marker ? '↺ ' + @js(__('Back to the start')) : block.name) + ' '"></span></template></div>
                                    <div class="live-beats" :style="`grid-template-columns: repeat(${bar.beats.length}, minmax(0, 1fr))`">
                                        <template x-for="(beat, i) in bar.beats" :key="i">
                                            <span class="live-beat" :class="{ 'is-repeat': beat.repeat && !insertAt(beat.tick), 'is-on': b === liveBar && i === liveBeat }">
                                                <span :class="halfClasses(beat, 0)" @click="tapHalf(beat, 0, $event)" x-text="halfText(beat, 0)"></span><template x-if="beat.and !== null || liveEdit"><span class="and" :class="halfClasses(beat, 1)" @click="tapHalf(beat, 1, $event)" x-text="halfText(beat, 1)"></span></template>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                        </template>
                        </div>
                        </template>

                        <template x-if="sheetMode">
                        <div class="sections" :class="{ 'two-columns': settings.twoColumns, 'block-swipe': blockScrollActive }" :style="`--chord-scale: ${layout.fontScale}; --label-scale: ${layout.labelScale}; --line-gap: ${layout.lineGap}; --section-gap: 3`">
                            <template x-for="(section, index) in current.chord_sheet" :key="`${current.id}-${index}`">
                                <section :class="sectionState(index)" :style="typeStyle(timedName(index))" @click="selectSection(index)">
                                    <div class="return-label" x-show="section.jump === 'start'"><i class="fa-solid fa-rotate-left"></i> {{ __('Back to the start') }}</div>
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
                                                    <span class="seg" :class="{ 'is-wide': unit.wide }"><span class="seg-chord" :class="{ 'is-passing': unit.chord && sheetChordPassing(index, lineIndex, unit.ordinal), 'is-playing': unit.chord && playingChord >= 0 && sheetChordNumber(index, lineIndex, unit.ordinal) === playingChord, ...suggestClasses(unit.chord ? [sheetChordNumber(index, lineIndex, unit.ordinal)] : []) }" :data-chord="unit.chord ? sheetChordNumber(index, lineIndex, unit.ordinal) : null"
                                                        @click="unit.chord && suggestClick([sheetChordNumber(index, lineIndex, unit.ordinal)], $event)"><span x-text="unit.chord ? suggestedName([sheetChordNumber(index, lineIndex, unit.ordinal)], chordFor(unit.chord)) : ''"></span></span><span class="seg-text" x-text="unit.text"></span></span>
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

                        <template x-if="!sheetMode && !liveMode && barChart">
                        <p class="bar-chart-head" x-text="[selectedKey, current.bpm ? `${current.bpm} BPM` : null, meterLabel(barChart.perBar)].filter(Boolean).join(' · ')"></p>
                        </template>
                        <template x-if="!sheetMode && !liveMode">
                        <div class="sections" :class="{ 'two-columns': settings.twoColumns, 'block-swipe': blockScrollActive }" :style="`--chord-scale: ${layout.fontScale}; --label-scale: ${layout.labelScale}; --line-gap: ${layout.lineGap}; --section-gap: 3`">
                            <template x-for="(section, index) in sections" :key="`${current.id}-${index}`">
                                <section :class="sectionState(index)" :style="typeStyle(sectionName(section))" @click="selectSection(index)">
                                    <div class="return-label" x-show="section?.jump === 'start'"><i class="fa-solid fa-rotate-left"></i> {{ __('Back to the start') }}</div>
                                    <div class="section-label">
                                        <div class="section-name">
                                            <span x-text="sectionName(section)"></span><span class="section-time" x-show="sectionRange(index)" x-text="sectionRange(index)"></span><span class="next-badge" x-show="index === nextSection">{{ __('next') }}</span><span class="edited-badge" x-show="section.edited">{{ __('edited') }}</span>
                                        </div>
                                        <div class="section-anchor" x-show="settings.showAnchors && section.anchor && !(settings.showLyrics && lyricsText(section))" x-text="section.anchor"></div>
                                    </div>
                                    <template x-if="barChart && barChart.sections[index]">
                                        <div class="bar-chart" :style="`--bars-per-row: ${barChart.perRow}`">
                                            <template x-for="(row, r) in barChart.sections[index].rows" :key="r">
                                                <div class="bar-row">
                                                    <template x-for="bar in row" :key="bar.index">
                                                        <div class="map-bar" :class="{ 'is-playing': bar.index === playingBar }" :data-map-bar="bar.index">
                                                            <template x-if="bar.repeat"><span class="bar-repeat">%</span></template>
                                                            <template x-if="!bar.repeat">
                                                                <span class="bar-chords"><template x-for="(token, t) in bar.tokens" :key="t"><span
                                                                    :class="{ 'bar-slash': token.slash, 'is-off': token.off, 'is-passing': token.passing, 'is-playing': bar.index === playingBar && token.numbers.includes(playingChord), ...suggestClasses(token.numbers) }"
                                                                    :data-chord="token.numbers[0]" @click="token.numbers.length && suggestClick(token.numbers, $event)"
                                                                    x-text="token.slash ? '/' : (token.push ? '^' : '') + suggestedName(token.numbers, token.name)"></span></template></span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!barChart">
                                        <div class="section-chords"
                                            x-data="{
                                                lines: [],
                                                aligned: true,
                                                // Columns sized by the widest chord of each cycle position; plain wrapping when it does not fit.
                                                fit() {
                                                    this.aligned = true;
                                                    this.$nextTick(() => this.aligned = this.$el.scrollWidth <= this.$el.clientWidth + 1);
                                                },
                                            }"
                                            x-effect="lines = chordLines(section, index); layout.fontScale; settings.twoColumns; fit()"
                                            @resize.window.debounce.200ms="fit()"
                                            :class="{ 'is-aligned': aligned }"
                                            :style="`--chord-columns: ${Math.max(1, ...lines.map(line => line.length))}`">
                                            <template x-for="(line, lineIndex) in lines" :key="lineIndex">
                                                <div class="chord-line">
                                                    <template x-for="(step, stepIndex) in line" :key="stepIndex">
                                                        <span class="chord-step"><template x-for="(chord, c) in step" :key="c"><span :class="{ 'is-passing': chord.passing, 'is-playing': playingChord >= 0 && chord.numbers.includes(playingChord), ...suggestClasses(chord.numbers) }" :data-chord="chord.numbers[0]"
                                                            @click="suggestClick(chord.numbers, $event)" x-text="(c ? ' ' : '') + suggestedName(chord.numbers, chord.name)"></span></template></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
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

                    {{-- Offline copy of this setlist: the page (chords, maps, keys, times) and the songs' audio files --}}
                    <div class="install" x-show="offlineSupported" x-cloak>
                        <button type="button" class="btn" @click="downloadOffline()" :disabled="offline.busy">
                            <i class="fa-solid" :class="offline.busy ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-down'"></i>
                            <span x-text="offline.busy ? @js(__('Downloading :done of :total…')).replace(':done', offline.done).replace(':total', offline.total)
                                : (offline.ready ? @js(__('Update offline copy')) : @js(__('Download for offline')))"></span>
                        </button>
                        <p class="hint" x-show="offline.ready && !offline.busy" style="margin-top: .35rem;"><i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> {{ __('Available offline') }}</p>
                        <p class="hint" x-show="offline.error" style="margin-top: .35rem; color: var(--danger);">{{ __('Could not download everything. Check the connection and try again.') }}</p>
                    </div>

                    {{-- Install as an app (hidden when already opened as the app) --}}
                    <div class="install" x-show="canInstall" x-cloak>
                        <button type="button" class="btn" @click="install()"><i class="fa-solid fa-mobile-screen-button"></i> {{ __('Install app') }}</button>
                    </div>

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

            {{-- Installing on iPhone / iPad: only by hand, from Safari's share menu --}}
            <div class="modal" x-show="installHelp" x-cloak @keydown.escape.window="installHelp = false">
                <div class="overlay" @click="installHelp = false"></div>
                <div class="modal-box" role="dialog" aria-modal="true" aria-label="{{ __('Install app') }}">
                    <p><strong>{{ __('Install app') }}</strong></p>
                    <ol class="install-steps">
                        <li>{{ __('Open this page in Safari.') }}</li>
                        <li>{!! __('Tap :icon Share.', ['icon' => '<i class="fa-solid fa-arrow-up-from-bracket"></i>']) !!}</li>
                        <li>{{ __('Choose "Add to Home Screen" and then "Add".') }}</li>
                    </ol>
                    <div class="modal-actions">
                        <button type="button" class="btn" @click="installHelp = false">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>

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
        {{-- Suggesting a correction of the chord tapped: another chord, or removing it --}}
        <div class="suggest-pop" x-show="suggestEdit" x-cloak :style="suggestEdit ? `left: ${suggestEdit?.left}px; top: ${suggestEdit?.top}px` : ''"
            @click.outside="suggestEdit = null" @keydown.escape.window="suggestEdit = null">
            <template x-if="suggestEdit">
                <div>
                    <div class="hint" style="margin-bottom: .35rem;" x-text="suggestEdit?.insert ? suggestEdit?.place : @js(__('Chord on the chord sheet: :chord')).replace(':chord', suggestEdit?.original)"></div>
                    <input type="text" x-ref="suggestInput" :value="suggestEdit?.name" autocomplete="off" autocapitalize="off" spellcheck="false"
                        @input="typeChord($event.target)" @keydown.enter.prevent="applySuggest(suggestEdit?.name)">
                    <label class="apply-all" x-show="suggestEdit?.others.length">
                        <input type="checkbox" :checked="suggestEdit?.applyAll" @change="if (suggestEdit) suggestEdit.applyAll = $event.target.checked">
                        <span x-text="@js(__('Also in the other “:name” blocks (:count)')).replace(':name', suggestEdit?.blockName).replace(':count', suggestEdit?.otherBlocks)"></span>
                    </label>
                    <div class="row">
                        <button type="button" class="btn btn-accent" @click="applySuggest(suggestEdit?.name)" x-text="suggestEdit?.insert ? @js(__('Insert')) : @js(__('Change'))"></button>
                        <button type="button" class="btn" x-show="!suggestEdit?.insert" @click="applySuggest(null)">{{ __('Remove') }}</button>
                    </div>
                    <div class="row">
                        <button type="button" class="btn" x-show="suggestEdit?.changed" @click="undoSuggest()">{{ __('Keep as it was') }}</button>
                        <button type="button" class="btn" @click="suggestEdit = null">{{ __('Cancel') }}</button>
                    </div>
                    <p class="hint" style="color: var(--danger); margin-top: .35rem;" x-show="suggestEdit?.error" x-text="suggestEdit?.error"></p>
                </div>
            </template>
        </div>

        <div class="yt-mini" x-ref="video" x-show="settings.youtubeSound && videoId && !audioUrl" x-cloak :class="{ 'is-dragging': draggingVideo }" :style="videoStyle"
            @resize.window.debounce.200ms="keepVideoInView()">
            <button type="button" class="yt-mini-drag" @pointerdown="dragVideo($event)" @dblclick="resetVideoPosition()"
                title="{{ __('Drag to move; double-click to put it back') }}" aria-label="{{ __('Move video') }}"><i class="fa-solid fa-grip"></i></button>
            <button type="button" class="yt-mini-close" @click="settings.youtubeSound = false" aria-label="{{ __('Turn off YouTube sound') }}" title="{{ __('Turn off YouTube sound') }}"><i class="fa-solid fa-xmark"></i></button>
            <div id="yt-sound"></div>
        </div>

        <div class="toast" x-show="toast.text" x-cloak x-transition.opacity :class="{ 'is-error': toast.error }" x-text="toast.text" role="status"></div>
    </div>

    <script>
        // Per-viewer display preferences; the page works the same when storage is unavailable.
        // Toggles are shared by every song; size settings are stored per song_id.
        const SETTINGS_KEY = 'setlist-view-settings';
        const DEFAULT_SETTINGS = { showDetails: true, showAnchors: true, showLyrics: false, twoColumns: true, viewMode: 'map', youtubeSound: false, instrument: '', suggest: false };

        // Values worked out once per song data, outside Alpine's reactive state: the songs' structure and chord sheet
        // are replaced (never changed in place), so the same objects give the same result. Read by every bar, beat and
        // chord on screen, they would otherwise be worked out again thousands of times when the song changes.
        const memoStore = new Map();
        let lastSongIndex = -1;
        function memo(name, deps, compute) {
            const entry = memoStore.get(name);
            if (entry && entry.deps.length === deps.length && entry.deps.every((dep, i) => dep === deps[i])) return entry.value;
            const value = compute();
            memoStore.set(name, { deps, value });
            return value;
        }

        // YouTube player for the song sound, outside Alpine's reactive state.
        let soundPlayer = null;
        // Audio file of the song, played instead of the YouTube video when the song has one (works offline).
        const audioPlayer = new Audio();
        audioPlayer.preload = 'auto';
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
        // Where the video was dragged to (this browser); null keeps it in its corner.
        const VIDEO_POSITION_KEY = 'setlist-video-position';
        const loadVideoPosition = () => {
            try {
                const position = JSON.parse(localStorage.getItem(VIDEO_POSITION_KEY) || 'null');
                return position && Number.isFinite(position.x) && Number.isFinite(position.y) ? position : null;
            } catch (e) {
                return null;
            }
        };
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

        // Note letters of a chord in capitals: the root and the bass after "/" (am -> Am, bb -> Bb, c#m7/g -> C#m7/G).
        const chordCase = (text) => String(text).replace(/^(\s*)([a-g])/, (match, space, note) => space + note.toUpperCase()).replace(/\/([a-g])/g, (match, note) => '/' + note.toUpperCase());
        const originalChords = (section) => Array.isArray(section?.chords) ? section.chords.map(String) : [];
        const sameChords = (a, b) => Array.isArray(a) && a.length === b.length && a.every((chord, i) => chord === b[i]);

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
                chordEdits: {},
                // ---- Chord corrections suggested here (see the "Suggest chords" option): { [songId]: { [n]: { from, to } } },
                // n: chord n of the song's map; from / to in the song's own key; to null: removed. Kept on this device
                // until sent for review. ----
                suggestions: (() => { try { return JSON.parse(localStorage.getItem('chord-suggestions') || '{}') || {}; } catch (e) { return {}; } })(),
                suggestEdit: null,
                suggestAuthor: (() => { try { return localStorage.getItem('suggest-author') || ''; } catch (e) { return ''; } })(),
                suggestNote: '',
                sendingSuggestion: false,
                savingKey: false,
                panelOpen: false,
                // ---- Offline copy (see public/sw.js, which serves it): this setlist's page, the scripts and styles it
                // loaded and its songs' audio files. Same cache names as the service worker. ----
                offline: { busy: false, ready: false, error: false, done: 0, total: 0 },
                // Connection state, shown as a crossed-out wifi icon in the top bar when there is none.
                online: navigator.onLine,

                // ---- Changes made without a connection: only on this device, never sent (so musicians do not
                // overwrite each other). They survive closing the app while there is no connection; opening it with a
                // connection shows the setlist as saved. Only the final result is kept: keys by song, songs added
                // (with their data) and removed, and the order. ----
                pending: { keys: {}, added: {}, removed: [], order: null },
                pendingKey() { return `setlist-pending-${setlist.id}`; },
                get hasPending() {
                    const p = this.pending;
                    return Object.keys(p.keys).length > 0 || Object.keys(p.added).length > 0 || p.removed.length > 0 || !!p.order;
                },
                savePending() { writeStorage(this.pendingKey(), this.hasPending ? this.pending : null); },
                // Shows the setlist (as saved, or as copied for offline use) with the changes still to be sent.
                loadPending() {
                    if (!setlist) return;
                    if (navigator.onLine) return this.forgetPending();
                    let saved = null;
                    try { saved = JSON.parse(localStorage.getItem(this.pendingKey()) || 'null'); } catch (e) {}
                    if (!saved) return;
                    this.pending = { keys: saved.keys || {}, added: saved.added || {}, removed: saved.removed || [], order: saved.order || null };
                    this.songs = this.songs.filter(song => !this.pending.removed.includes(song.id));
                    Object.values(this.pending.added).forEach(song => { if (!this.isInSetlist(song)) this.songs.push(song); });
                    this.songs.forEach(song => { if (song.id in this.pending.keys) song.setlist_key = this.pending.keys[song.id]; });
                    if (this.pending.order) {
                        const order = this.pending.order;
                        const at = (song) => (order.includes(song.id) ? order.indexOf(song.id) : order.length);
                        this.songs.sort((a, b) => at(a) - at(b));
                    }
                    this.renumber();
                    if (!this.songs.some(song => song.id === this.currentId)) this.currentId = this.songs[0]?.id ?? null;
                },
                forgetPending() {
                    this.pending = { keys: {}, added: {}, removed: [], order: null };
                    this.savePending();
                },
                rememberOffline(change) {
                    const p = this.pending;
                    if (change.key !== undefined) p.keys[change.song.id] = change.key;
                    if (change.add) {
                        p.removed = p.removed.filter(id => id !== change.add.id);
                        p.added[change.add.id] = change.add;
                    }
                    if (change.remove) {
                        if (change.remove.id in p.added) delete p.added[change.remove.id];
                        else if (!p.removed.includes(change.remove.id)) p.removed.push(change.remove.id);
                        delete p.keys[change.remove.id];
                    }
                    if (change.order || change.add || change.remove) p.order = this.songs.map(song => song.id);
                    this.pending = { ...p };
                    this.savePending();
                },
                get offlineSupported() { return 'caches' in window && 'serviceWorker' in navigator && !!setlist; },
                offlinePages() { return [...new Set([location.pathname, new URL(`${config.urls.setlist}/${setlist.id}`).pathname])]; },
                offlineAudio() { return [...new Set(this.songs.map(song => song.audio_url).filter(Boolean))]; },
                async checkOffline() {
                    if (!this.offlineSupported) return;
                    try {
                        const pages = await caches.open('repertoire-pages-v1');
                        const audio = await caches.open('repertoire-audio-v1');
                        const found = await Promise.all([
                            ...this.offlinePages().map(url => pages.match(url, { ignoreSearch: true })),
                            ...this.offlineAudio().map(url => audio.match(url)),
                        ]);
                        this.offline.ready = found.every(Boolean);
                    } catch (e) {}
                },
                // Pages, scripts, styles, fonts and icons of this setlist ([cache, url] pairs) and, with `withAudio`, its
                // songs' audio files.
                offlineJobs(withAudio) {
                    const assets = [...new Set(performance.getEntriesByType('resource').map(entry => entry.name))]
                        .filter(url => /\/(build|js|icons|img)\//.test(new URL(url).pathname) || /cdnjs\.cloudflare\.com|fonts\.bunny\.net/.test(url));
                    return [
                        ...this.offlinePages().map(url => ['repertoire-pages-v1', url]),
                        ...assets.map(url => ['repertoire-assets-v1', url]),
                        // Songs that can be added to the setlist without a connection.
                        ...(withAudio ? [['repertoire-pages-v1', config.urls.catalog]] : []),
                        ...(withAudio ? this.offlineAudio().map(url => ['repertoire-audio-v1', url]) : []),
                    ];
                },
                // Saves one address. Scripts, styles and audio files never change under the same address, so the ones
                // already saved are skipped. The server limits how many requests come in a row (429): it waits and
                // tries again a few times.
                async saveOffline([name, url]) {
                    const cache = await caches.open(name);
                    if (name !== 'repertoire-pages-v1' && await cache.match(url)) return;
                    for (let attempt = 0; ; attempt++) {
                        const response = await fetch(url, { cache: 'reload' });
                        if (response.ok) return cache.put(url, response);
                        if (![429, 503].includes(response.status) || attempt === 4) throw new Error(response.status);
                        const wait = Number(response.headers.get('Retry-After')) || 2 ** (attempt + 1);
                        await new Promise(resolve => setTimeout(resolve, Math.min(wait, 20) * 1000));
                    }
                },
                // Every visit with a connection saves the page and what it loaded (not the audio, which only the
                // button downloads), so the setlist opens again offline even before "Download for offline".
                async saveOfflineShell() {
                    if (!this.offlineSupported || !navigator.onLine) return;
                    for (const job of this.offlineJobs(false)) {
                        try { await this.saveOffline(job); } catch (e) {}
                    }
                    this.checkOffline();
                },
                async downloadOffline() {
                    if (this.offline.busy) return;
                    // Asks the browser to keep the copy even when the device is short of space (usually granted
                    // without asking on Android; ignored on iPhone).
                    try { await navigator.storage?.persist?.(); } catch (e) {}
                    const jobs = this.offlineJobs(true);
                    this.offline = { busy: true, ready: false, error: false, done: 0, total: jobs.length };
                    for (const job of jobs) {
                        try {
                            await this.saveOffline(job);
                        } catch (e) {
                            this.offline.error = true;
                        }
                        this.offline.done++;
                        // A short pause between requests, for the server's request limit.
                        await new Promise(resolve => setTimeout(resolve, 250));
                    }
                    this.offline.busy = false;
                    this.forgetOldAudio();
                    await this.checkOffline();
                },
                // Audio files saved for setlists of this device that are no longer used (replaced or removed) are
                // deleted, so the copies do not keep growing.
                async forgetOldAudio() {
                    try {
                        const saved = JSON.parse(localStorage.getItem('offline-audio') || '{}');
                        saved[setlist.id] = this.offlineAudio();
                        localStorage.setItem('offline-audio', JSON.stringify(saved));
                        const used = new Set(Object.values(saved).flat());
                        const audio = await caches.open('repertoire-audio-v1');
                        for (const request of await audio.keys()) {
                            if (!used.has(request.url)) await audio.delete(request);
                        }
                    } catch (e) {}
                },

                // Installing as an app: the browser's offer (Android / desktop) or the steps for iPhone.
                installPrompt: window.installPrompt ?? null,
                installHelp: false,
                get isApp() { return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true; },
                get isIos() { return /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1); },
                get canInstall() { return !this.isApp && (!!this.installPrompt || this.isIos); },
                async install() {
                    if (!this.installPrompt) {
                        this.installHelp = true;
                        return;
                    }
                    this.installPrompt.prompt();
                    const { outcome } = await this.installPrompt.userChoice;
                    if (outcome === 'accepted') this.installPrompt = null;
                },
                searchOpen: false,
                term: '',
                results: [],
                searched: false,
                adding: false,
                removing: null,
                dragId: null,
                toast: { text: '', error: false },
                videoPosition: loadVideoPosition(),
                draggingVideo: false,

                // ---- Moving the video: bottom right by default, or where it was dragged to ----
                get videoStyle() {
                    const position = this.videoPosition;
                    return position ? `left: ${position.x}px; top: ${position.y}px; right: auto; bottom: auto;` : '';
                },
                clampVideo(x, y) {
                    const box = this.$refs.video.getBoundingClientRect();
                    return {
                        x: Math.floor(Math.min(Math.max(0, x), window.innerWidth - box.width)),
                        y: Math.floor(Math.min(Math.max(0, y), window.innerHeight - box.height)),
                    };
                },
                dragVideo(event) {
                    event.preventDefault();
                    const box = this.$refs.video.getBoundingClientRect();
                    const offset = { x: event.clientX - box.left, y: event.clientY - box.top };
                    this.draggingVideo = true;
                    const move = (moveEvent) => {
                        this.videoPosition = this.clampVideo(moveEvent.clientX - offset.x, moveEvent.clientY - offset.y);
                    };
                    const end = () => {
                        window.removeEventListener('pointermove', move);
                        window.removeEventListener('pointerup', end);
                        window.removeEventListener('pointercancel', end);
                        this.draggingVideo = false;
                        writeStorage(VIDEO_POSITION_KEY, this.videoPosition);
                    };
                    window.addEventListener('pointermove', move);
                    window.addEventListener('pointerup', end);
                    window.addEventListener('pointercancel', end);
                },
                keepVideoInView() {
                    if (!this.videoPosition || !this.$refs.video.offsetWidth) return;
                    this.videoPosition = this.clampVideo(this.videoPosition.x, this.videoPosition.y);
                },
                resetVideoPosition() {
                    this.videoPosition = null;
                    writeStorage(VIDEO_POSITION_KEY, null);
                },

                // The position found last is checked first: read by every chord on screen, a search each time adds up.
                get currentIndex() {
                    if (this.songs[lastSongIndex]?.id === this.currentId) return lastSongIndex;
                    return lastSongIndex = this.songs.findIndex(song => song.id === this.currentId);
                },
                get current() { return this.songs[this.currentIndex] ?? null; },

                // Key used in this setlist, falling back to the song's original key.
                get selectedKey() { return this.current?.setlist_key ?? this.current?.original_key ?? ''; },
                // One option per pair of relative keys ("C / Am"): major songs pick the major key of the pair,
                // minor songs the minor one.
                get songIsMinor() {
                    const key = ChordTransposer.parseKey(this.current?.original_key || this.selectedKey);
                    return key ? key.minor : null;
                },
                get keyChoices() {
                    return this.keyOptions.map(pair => ({ value: this.songIsMinor ? pair.minor : pair.major, label: pair.label }));
                },
                get extraKeys() {
                    const listed = this.keyChoices.map(choice => choice.value);
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
                // `index`: the section's position in the map.
                chordLines(section, index) {
                    const chords = Array.isArray(section?.chords) ? section.chords.map(String) : [];
                    const passing = this.sectionPassing(section);
                    // Numbers of each chord in the whole map (to light the chord being played); none when edited here.
                    const offset = section?.edited ? null : this.mapChordOffsets[index];
                    const lines = chordSegments(chords, this.mapSongCycles, passing)
                        .map(line => line.map(step => step.chords.map((name, i) => ({
                            name,
                            passing: passing.includes(step.positions[i]),
                            numbers: offset === undefined || offset === null ? [] : [offset + step.positions[i]],
                        }))));

                    // Shows a chord repeated in a row once per line (G G -> G), dropping cells left empty; the chord
                    // shown also stands for the ones dropped after it.
                    return lines.map(line => {
                        const cells = [];
                        let last = null;
                        line.forEach(step => {
                            const kept = [];
                            step.forEach(chord => {
                                if (last && chord.name === last.name) last.numbers.push(...chord.numbers);
                                else { kept.push(chord); last = chord; }
                            });
                            if (kept.length) cells.push(kept);
                        });
                        return cells;
                    }).filter(line => line.length);
                },
                // ---- The map as a bar chart (the usual band chart): each block on new rows of 4 bars, a chord that
                // fills a bar written once ("| G |"), two halves "| G  D |", other splits by beats "| G / / D |", a bar
                // like the one before "%", a chord coming half a beat early "^C" in the next bar. Built from the chords'
                // beats; null when a block has no beats (the map then shows its chord lines). ----
                get barChart() {
                    const sections = this.sections;
                    const key = [this.currentId, this.current?.structure, this.selectedKey, JSON.stringify(this.chordEdits)];
                    return memo('barChart', key, () => this.buildBarChart(sections));
                },
                buildBarChart(sections) {
                    const ticks = [];
                    const starts = [];
                    const chordTicks = [];
                    let perBar = 0;
                    for (const [index, section] of sections.entries()) {
                        if (!section || typeof section !== 'object' || section.jump === 'start') continue;
                        const chords = chordsOnly(Array.isArray(section.chords) ? section.chords.map(String) : []);
                        const durations = Array.isArray(section.durations) ? section.durations.map(Number) : [];
                        if (!chords.length) continue;
                        if (durations.length !== chords.length || durations.some(value => !(value > 0))) return null;
                        perBar ||= Number(section.beats_per_bar) || 4;
                        const passing = this.sectionPassing(section);
                        const offset = section.edited ? null : this.mapChordOffsets[index];
                        starts.push({ index, tick: ticks.length });
                        chords.forEach((name, k) => {
                            const number = offset === null || offset === undefined ? null : offset + k;
                            if (number !== null) chordTicks[number] = [ticks.length, ticks.length + Math.max(1, Math.round(durations[k] * 2))];
                            for (let t = 0; t < Math.max(1, Math.round(durations[k] * 2)); t++) ticks.push({ name, number, passing: passing.includes(k) });
                        });
                    }
                    if (!ticks.length) return null;
                    const size = perBar * 2;

                    // Chords of each bar, a chord held over the bar line written again at its start.
                    const bars = [];
                    for (let start = 0; start < ticks.length; start += size) {
                        const segments = [];
                        ticks.slice(start, start + size).forEach((tick, at) => {
                            const last = segments[segments.length - 1];
                            if (last && last.name === tick.name) {
                                if (tick.number !== null && !last.numbers.includes(tick.number)) last.numbers.push(tick.number);
                                return;
                            }
                            segments.push({ name: tick.name, at, numbers: tick.number === null ? [] : [tick.number], passing: tick.passing, push: false });
                        });
                        bars.push({ index: bars.length, start, segments });
                    }
                    // A chord starting on the last half beat of a bar and going on in the next one is written there, pushed.
                    bars.forEach((bar, b) => {
                        const last = bar.segments[bar.segments.length - 1];
                        const next = bars[b + 1]?.segments[0];
                        if (bar.segments.length > 1 && last.at === size - 1 && next && next.name === last.name) {
                            bar.segments.pop();
                            next.push = true;
                        }
                    });
                    bars.forEach(bar => {
                        const segments = bar.segments;
                        let tokens;
                        if (segments.length === 1) {
                            tokens = [segments[0]];
                        } else if (segments.length === 2 && segments[1].at === size / 2 && perBar % 2 === 0) {
                            tokens = segments;
                        } else {
                            tokens = [];
                            for (let beat = 0; beat < perBar; beat++) {
                                const on = segments.find(segment => segment.at === beat * 2);
                                const off = segments.find(segment => segment.at === beat * 2 + 1);
                                tokens.push(on || { slash: true, numbers: [] });
                                if (off) tokens.push({ ...off, off: true });
                            }
                        }
                        bar.tokens = tokens;
                    });

                    // Rows: the bars of each block (a bar belongs to the block playing on its first beat).
                    const owner = (tick) => starts.filter(item => item.tick <= tick).at(-1)?.index;
                    const result = sections.map(() => null);
                    let previous = null;
                    bars.forEach(bar => {
                        const index = owner(bar.start);
                        if (index === undefined) return;
                        result[index] ||= { bars: [] };
                        const signature = bar.tokens.map(token => token.slash ? '/' : (token.push ? '^' : '') + token.name + (token.off ? '&' : '')).join(' ');
                        // "%": the same bar again in the same block.
                        bar.repeat = previous && previous.index === index && previous.signature === signature && !bar.tokens.some(token => token.push);
                        previous = { index, signature };
                        result[index].bars.push(bar);
                    });
                    const perRow = 4;
                    result.forEach(item => {
                        if (!item) return;
                        item.rows = [];
                        for (let i = 0; i < item.bars.length; i += perRow) item.rows.push(item.bars.slice(i, i + perRow));
                    });
                    return { perBar, perRow, size, sections: result, chordTicks };
                },
                // Time signature shown for the beats of a bar: 6 (eighth notes) is 6/8.
                meterLabel(beats) { return { 6: '6/8', 9: '9/8', 12: '12/8' }[beats] ?? `${beats}/4`; },
                playingBar: -1,
                // Bar being played: from the chord being played and the time since it started.
                barAt(n, time) {
                    const chart = this.barChart;
                    const span = chart?.chordTicks[n];
                    const times = this.chordTimes;
                    if (!span || !times || !(Number(this.current?.bpm) > 0)) return -1;
                    const tick = Math.min(span[1] - 1, span[0] + Math.max(0, Math.floor((time - times[n]) / (30 / Number(this.current.bpm)))));
                    return Math.floor(tick / chart.size);
                },
                // Number of the first chord of each map section in the whole map.
                get mapChordOffsets() {
                    let offset = 0;
                    return (Array.isArray(this.current?.structure) ? this.current.structure : []).map(section => {
                        const start = offset;
                        offset += chordsOnly(Array.isArray(section?.chords) ? section.chords.map(String) : []).length;
                        return start;
                    });
                },
                // Passing chords of a map section (positions); none when this device changed its chords.
                sectionPassing(section) {
                    return !section?.edited && Array.isArray(section?.passing) ? section.passing : [];
                },

                saveSuggestions() { writeStorage('chord-suggestions', Object.keys(this.suggestions).length ? this.suggestions : null); },
                saveSuggestAuthor() { try { localStorage.setItem('suggest-author', this.suggestAuthor); } catch (e) {} },
                get songSuggestions() { return this.suggestions[this.currentId] || {}; },
                get suggestCount() { return Object.keys(this.songSuggestions).length; },
                get otherSuggestions() { return Object.entries(this.suggestions).filter(([id, changes]) => Number(id) !== this.currentId && Object.keys(changes).length).length; },
                // Chord n of the song's base map (in its own key).
                baseChord(n) {
                    const list = (this.current?._base?.structure || this.current?.structure || []).flatMap(section => chordsOnly(Array.isArray(section?.chords) ? section.chords.map(String) : []));
                    return list[n];
                },
                canSuggest(numbers) {
                    return this.settings.suggest && !this.current?.instrumentVersion && numbers.length > 0 && numbers.every(n => Number.isInteger(n) && this.baseChord(n) !== undefined);
                },
                suggestClasses(numbers) {
                    const change = numbers.length ? this.songSuggestions[numbers[0]] : null;
                    return { 'is-suggested': !!change && change.to !== null, 'is-removed': !!change && change.to === null };
                },
                // The name shown: the suggested one (in the key on screen) when changed.
                suggestedName(numbers, name) {
                    const change = numbers.length ? this.songSuggestions[numbers[0]] : null;
                    return change && change.to !== null ? this.chordFor(change.to) : name;
                },
                // ---- Inserting a chord where none starts (Live view, while correcting): key "i<n>:<offset>" ----
                // The insertion suggested at `tick`: inside chord n, `offset` half beats after its start.
                insertKey(tick) {
                    const grid = this.liveGrid;
                    const n = grid?.numbers[tick];
                    if (n === undefined) return null;
                    const offset = tick - grid.chordStart[n];
                    return offset > 0 ? { key: `i${n}:${offset}`, n, offset } : null;
                },
                insertAt(tick) {
                    const place = this.insertKey(tick);
                    return place ? this.songSuggestions[place.key] ?? null : null;
                },
                // A half of a beat in the grid (0: the beat, 1: its "and"): the chord starting there, a chord suggested to
                // be inserted there, or what sounds there.
                halfText(beat, half) {
                    const n = half ? beat.andN : beat.n;
                    const name = half ? beat.and : beat.chord;
                    if (n !== null) return this.suggestedName([n], this.chordFor(name));
                    const insert = this.insertAt(beat.tick + half);
                    if (insert) return this.chordFor(insert.to);
                    if (half) return name ? this.chordFor(name) : '+';
                    return beat.repeat ? '/' : (name ? this.chordFor(name) : '–');
                },
                halfClasses(beat, half) {
                    const n = half ? beat.andN : beat.n;
                    if (n !== null) return this.suggestClasses([n]);
                    const insert = this.insertAt(beat.tick + half);
                    return { 'is-suggested': !!insert, 'is-empty': half === 1 && !insert && beat.and === null };
                },
                tapHalf(beat, half, event) {
                    if (!this.liveEdit) return;
                    const n = half ? beat.andN : beat.n;
                    if (n !== null) return this.suggestClick([n], event);
                    this.insertClick(beat.tick + half, event);
                },
                insertClick(tick, event) {
                    const place = this.insertKey(tick);
                    if (!place || !this.canSuggest([place.n])) return;
                    event.stopPropagation();
                    const box = event.currentTarget.getBoundingClientRect();
                    const size = this.liveGrid.perBar * 2;
                    const existing = this.songSuggestions[place.key];
                    // The same place in the blocks with the same name, when the same chord is there and lasts that long.
                    const same = this.sameBlockChords([place.n]);
                    const lasts = (m) => (this.liveGrid.chordStart[m + 1] ?? this.liveGrid.ticks.length) - this.liveGrid.chordStart[m] > place.offset;
                    const others = same.numbers.filter(lasts);
                    this.suggestEdit = {
                        insert: place,
                        numbers: [place.n],
                        others,
                        blockName: same.name,
                        otherBlocks: others.length,
                        applyAll: false,
                        name: existing ? this.chordFor(existing.to) : '',
                        original: this.chordFor(this.baseChord(place.n)),
                        place: @js(__('New chord: bar :bar, beat :beat')).replace(':bar', Math.floor(tick / size) + 1).replace(':beat', Math.floor((tick % size) / 2) + 1) + (tick % 2 ? ` (${@js(__('and'))})` : ''),
                        changed: !!existing,
                        error: '',
                        left: Math.max(8, Math.min(box.left, window.innerWidth - 290)),
                        top: Math.min(box.bottom + 6, window.innerHeight - (others.length ? 205 : 170)),
                    };
                    this.$nextTick(() => this.$refs.suggestInput?.focus());
                },
                suggestClick(numbers, event) {
                    if (!this.canSuggest(numbers)) return;
                    event.stopPropagation();
                    const box = event.currentTarget.getBoundingClientRect();
                    const change = this.songSuggestions[numbers[0]];
                    const same = this.sameBlockChords(numbers);
                    this.suggestEdit = {
                        numbers,
                        others: same.numbers,
                        blockName: same.name,
                        otherBlocks: same.blocks,
                        applyAll: false,
                        name: change && change.to !== null ? this.chordFor(change.to) : this.chordFor(this.baseChord(numbers[0])),
                        original: this.chordFor(this.baseChord(numbers[0])),
                        changed: !!change,
                        error: '',
                        left: Math.max(8, Math.min(box.left, window.innerWidth - 290)),
                        top: Math.min(box.bottom + 6, window.innerHeight - (same.numbers.length ? 205 : 170)),
                    };
                    this.$nextTick(() => this.$refs.suggestInput?.select());
                },
                // to: a chord name typed in the key on screen, or null to remove the chord.
                // Chord typed: note letters in capitals (am -> Am, c#m7/g -> C#m7/G), keeping the cursor in place.
                typeChord(input) {
                    const position = input.selectionStart;
                    input.value = chordCase(input.value);
                    input.setSelectionRange(position, position);
                    this.suggestEdit.name = input.value;
                },
                // The same chord in the other blocks with the same name (e.g. every "Refrão"): the chord at the same
                // position of each of them, when it is the same chord there.
                sameBlockChords(numbers) {
                    const structure = this.current?._base?.structure || this.current?.structure || [];
                    let offset = 0;
                    const blocks = structure.map(section => {
                        const count = chordsOnly(originalChords(section)).length;
                        const block = { name: this.sectionName(section).trim(), start: offset, count };
                        offset += count;
                        return block;
                    });
                    const block = blocks.find(item => numbers[0] >= item.start && numbers[0] < item.start + item.count);
                    const found = new Set();
                    const matched = new Set();
                    if (block?.name) {
                        blocks.forEach(other => {
                            if (other === block || other.name.toUpperCase() !== block.name.toUpperCase()) return;
                            numbers.forEach(n => {
                                const position = n - block.start;
                                const m = other.start + position;
                                if (position < other.count && !numbers.includes(m) && this.baseChord(m) === this.baseChord(n)) {
                                    found.add(m);
                                    matched.add(other);
                                }
                            });
                        });
                    }
                    return { name: block?.name || '', numbers: [...found], blocks: matched.size };
                },
                // Chords the change goes to: the one tapped, and the same chord in the blocks with the same name when chosen.
                suggestNumbers(edit) {
                    return edit.applyAll ? [...edit.numbers, ...edit.others] : edit.numbers;
                },
                applySuggest(to) {
                    const edit = this.suggestEdit;
                    if (!edit) return;
                    if (to !== null) {
                        to = chordCase(to.trim());
                        if (!/^[A-G][#b]?[^\s\[\]|]*$/.test(to)) { edit.error = this.messages.invalidChord; return; }
                        to = ChordTransposer.transposeChord(to, this.selectedKey, this.current.original_key);
                    }
                    const changes = { ...this.songSuggestions };
                    if (edit.insert) {
                        if (to === null) return;
                        this.suggestNumbers(edit).forEach(n => {
                            changes[`i${n}:${edit.insert.offset}`] = { type: 'insert', n, offset: edit.insert.offset, from: this.baseChord(n), to };
                        });
                        this.setSongSuggestions(changes);
                        this.suggestEdit = null;
                        return;
                    }
                    this.suggestNumbers(edit).forEach(n => {
                        const from = this.baseChord(n);
                        if (to === from) delete changes[n];
                        else changes[n] = { from, to };
                    });
                    this.setSongSuggestions(changes);
                    this.suggestEdit = null;
                },
                undoSuggest() {
                    const changes = { ...this.songSuggestions };
                    const edit = this.suggestEdit;
                    this.suggestNumbers(edit).forEach(n => delete changes[edit.insert ? `i${n}:${edit.insert.offset}` : n]);
                    this.setSongSuggestions(changes);
                    this.suggestEdit = null;
                },
                setSongSuggestions(changes) {
                    const all = { ...this.suggestions };
                    if (Object.keys(changes).length) all[this.currentId] = changes;
                    else delete all[this.currentId];
                    this.suggestions = all;
                    this.saveSuggestions();
                },
                discardSuggestion() {
                    if (!confirm(this.messages.discardSuggestion)) return;
                    this.setSongSuggestions({});
                },
                async sendSuggestion() {
                    if (!this.suggestCount || !this.current) return;
                    this.sendingSuggestion = true;
                    try {
                        const changes = Object.entries(this.songSuggestions).map(([key, change]) => (change.type === 'insert'
                            ? { type: 'insert', n: change.n, offset: change.offset, from: change.from, to: change.to }
                            : { n: Number(key), from: change.from, to: change.to }));
                        await this.request('POST', this.songUrl(this.current, '/suggestions'), { changes, author: this.suggestAuthor || null, note: this.suggestNote || null });
                        this.setSongSuggestions({});
                        this.suggestNote = '';
                        this.notify(this.messages.suggestionSent);
                    } catch (error) {
                        this.notify(error.offline ? this.messages.suggestionOffline : error.message, true);
                    } finally {
                        this.sendingSuggestion = false;
                    }
                },

                // ---- Versions for an instrument: whoever picks an instrument sees its version of each song that has
                // one (its map and the chord sheet with its chords); the base map stays kept in `_base` ----
                get instrumentOptions() {
                    const names = new Set(this.songs.flatMap(song => (song.versions || []).map(version => version.instrument)));
                    if (this.settings.instrument) names.add(this.settings.instrument);
                    return [...names].sort((a, b) => a.localeCompare(b));
                },
                applyInstrument(song) {
                    if (!song) return;
                    if (!song._base) song._base = { structure: song.structure, chord_sheet: song.chord_sheet };
                    const version = (song.versions || []).find(item => item.instrument === this.settings.instrument);
                    if (version) {
                        const structure = versionStructure(version, song.original_key);
                        song.structure = structure;
                        song.chord_sheet = Array.isArray(song._base.chord_sheet) ? sheetWithChords(song._base.chord_sheet, song._base.structure, structure) : song._base.chord_sheet;
                        song.instrumentVersion = version.instrument;
                    } else {
                        song.structure = song._base.structure;
                        song.chord_sheet = song._base.chord_sheet;
                        song.instrumentVersion = null;
                    }
                },
                applyInstruments() { this.songs.forEach(song => this.applyInstrument(song)); },

                init() {
                    window.addEventListener('install-available', () => this.installPrompt = window.installPrompt);
                    this.setupAudio();
                    this.liveBpm = this.loadLiveBpm();
                    this.checkOffline();
                    this.loadPending();
                    this.applyInstruments();
                    this.$watch('settings.instrument', () => {
                        this.stopLive();
                        this.resetPlayback();
                        this.applyInstruments();
                    });
                    // Back online: the changes stay on screen until the app is opened again, but are no longer kept.
                    window.addEventListener('online', () => { this.online = true; this.forgetPending(); this.saveOfflineShell(); });
                    window.addEventListener('offline', () => this.online = false);
                    // After the page finished loading (so every script and style is listed).
                    window.addEventListener('load', () => setTimeout(() => this.saveOfflineShell(), 1500), { once: true });
                    if (document.readyState === 'complete') setTimeout(() => this.saveOfflineShell(), 1500);
                    window.addEventListener('appinstalled', () => this.installPrompt = null);
                    // Not passive, so the page scroll can be replaced by going to the block above / below.
                    window.addEventListener('wheel', event => this.onWheel(event), { passive: false });
                    window.addEventListener('touchstart', event => this.onTouchStart(event), { passive: true });
                    window.addEventListener('touchmove', event => this.onTouchMove(event), { passive: false });
                    window.addEventListener('touchend', () => this.onTouchEnd(), { passive: true });
                    window.addEventListener('touchcancel', () => this.onTouchEnd(true), { passive: true });
                    this.$watch('settings', value => saveSettings(value), { deep: true });
                    // Each song opens with its own saved layout.
                    this.layout = this.currentId ? loadLayout(this.currentId) : { ...DEFAULT_LAYOUT };
                    this.chordEdits = this.currentId ? loadChordEdits(this.currentId) : {};
                    document.addEventListener('visibilitychange', () => {
                        if (this.playing && document.visibilityState === 'visible') this.requestWakeLock();
                    });
                    this.$watch('currentId', id => {
                        this.stopLive();
                        // The count of the new song starts from its first bar.
                        this.live.tick = 0;
                        this.live.usedMarkers = [];
                        this.liveBpm = this.loadLiveBpm();
                        this.layout = id ? loadLayout(id) : { ...DEFAULT_LAYOUT };
                        this.chordEdits = id ? loadChordEdits(id) : {};
                        this.keyPickerOpen = false;
                        this.resetPlayback();
                        this.$nextTick(() => this.setupSound());
                    });
                    this.$watch('liveEdit', () => { if (this.liveMode) this.scrollToLiveBar(true); });
                    this.$watch('layout.fontScale', () => { if (this.liveMode && !this.liveEdit) this.fitFocus(); });
                    // The area of the current and next bar follows the size of what is above it (title, buttons, fonts).
                    if (window.ResizeObserver) new ResizeObserver(() => { if (this.liveMode && !this.liveEdit) this.fitFocus(); }).observe(document.body);
                    this.$watch('bpmMode', on => {
                        if (!on) this.stopLive();
                        else if (this.playing) this.pause();
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
                // ---- Live mode: the map's bars, built from the duration of each chord (in beats, saved by the study
                // app), counted by the BPM on this device. A chord can change on a beat or halfway through it ("and"),
                // so the count goes by half beats ("ticks"). "Back to the start" goes back to the first block once. ----
                live: { running: false, tick: 0, startTick: 0, startedAt: 0, click: false, usedMarkers: [], frame: null, lastBeat: -1, shownBlock: null },
                liveBpm: 80,
                taps: [],
                get liveGrid() {
                    const structure = Array.isArray(this.current?.structure) ? this.current.structure : [];
                    return memo('liveGrid', [this.currentId, structure], () => this.buildLiveGrid(structure));
                },
                buildLiveGrid(structure) {
                    let value = null;
                    const ticks = [];
                    const blocks = [];
                    let perBar = 4;
                    let clickDouble = false;
                    let complete = structure.length > 0;
                    // Number of the chord of each tick (chord n of the map, as corrections count them).
                    const numbers = [];
                    let number = 0;
                    structure.forEach((section, index) => {
                        if (!section || typeof section !== 'object') return;
                        if (section.jump === 'start') return blocks.push({ tick: ticks.length, marker: true, name: '', index });
                        const chords = chordsOnly(Array.isArray(section.chords) ? section.chords.map(String) : []);
                        const durations = Array.isArray(section.durations) ? section.durations : null;
                        if (!durations || durations.length !== chords.length) { complete = complete && chords.length === 0; return; }
                        if (section.beats_per_bar) perBar = section.beats_per_bar;
                        if (section.click_double) clickDouble = true;
                        blocks.push({ tick: ticks.length, marker: false, name: this.sectionName(section), index });
                        chords.forEach((chord, i) => {
                            const n = number++;
                            for (let k = 0; k < Math.round(durations[i] * 2); k++) { ticks.push(chord); numbers.push(n); }
                        });
                    });
                    if (complete && ticks.length) {
                        const size = perBar * 2;
                        const bars = [];
                        for (let start = 0; start < ticks.length; start += size) {
                            const part = ticks.slice(start, start + size);
                            const beats = [];
                            for (let i = 0; i < part.length; i += 2) {
                                const onBeat = part[i];
                                const previous = start + i > 0 ? ticks[start + i - 1] : null;
                                const at = start + i;
                                beats.push({
                                    chord: onBeat,
                                    repeat: i > 0 && onBeat === previous,
                                    and: part[i + 1] !== undefined && part[i + 1] !== onBeat ? part[i + 1] : null,
                                    tick: at,
                                    // The chord starting on the beat / on its "and" (null: none starts there).
                                    n: at === 0 || numbers[at] !== numbers[at - 1] ? numbers[at] : null,
                                    andN: part[i + 1] !== undefined && numbers[at + 1] !== numbers[at] ? numbers[at + 1] : null,
                                });
                            }
                            bars.push({ start, beats, blocks: blocks.filter(block => block.tick >= start && block.tick < start + size) });
                        }
                        const chordStart = [];
                        numbers.forEach((n, t) => { if (chordStart[n] === undefined) chordStart[n] = t; });
                        value = { perBar, clickDouble, ticks, numbers, chordStart, bars, blocks: blocks.filter(block => !block.marker), markers: blocks.filter(block => block.marker).map(block => block.tick) };
                    }
                    return value;
                },
                get hasLive() { return !!this.liveGrid; },
                // Every bar is shown in the Live view while correcting chords (the corrections are made there).
                get liveEdit() { return !!this.settings.suggest && !this.current?.instrumentVersion; },
                // The bar before, the current one and the next one (the first bar again when a "back to the start" comes before it),
                // each with the block it is in and the block that starts inside it.
                get focusBars() {
                    const grid = this.liveGrid;
                    if (!grid) return [];
                    const size = grid.perBar * 2;
                    const current = this.liveBar;
                    const end = (current + 1) * size;
                    const restart = grid.markers.some(at => at > this.live.tick && at <= end && !this.live.usedMarkers.includes(at));
                    const next = restart ? 0 : current + 1;
                    const describe = (index, role, back = false) => {
                        const start = index * size;
                        const block = grid.blocks.filter(item => !item.marker && item.tick <= start).at(-1);
                        const starts = grid.blocks.find(item => !item.marker && item.tick > start && item.tick < start + size);
                        return { index, role, now: role === 'now', bar: grid.bars[index], block: block?.name ?? '', starts: starts?.name ?? '', restart: back, empty: false };
                    };
                    // The bar before (an empty place on the first bar, so the current one stays in the middle).
                    const items = [current > 0 ? describe(current - 1, 'previous') : { ...describe(current, 'previous'), empty: true }, describe(current, 'now')];
                    if (next < grid.bars.length) items.push(describe(next, 'next', restart));
                    return items;
                },
                get liveBar() { return this.liveGrid ? Math.floor(this.live.tick / (this.liveGrid.perBar * 2)) : 0; },
                get liveBeat() { return this.liveGrid ? Math.floor((this.live.tick % (this.liveGrid.perBar * 2)) / 2) : 0; },
                get liveChordNow() { return this.liveGrid?.ticks[this.live.tick] ?? ''; },
                get liveChordNext() {
                    const ticks = this.liveGrid?.ticks || [];
                    const now = ticks[this.live.tick];
                    for (let t = this.live.tick + 1; t < ticks.length; t++) if (ticks[t] && ticks[t] !== now) return ticks[t];
                    return '';
                },
                liveBpmKey() { return `live-bpm-${this.currentId}`; },
                loadLiveBpm() {
                    let saved = 0;
                    try { saved = Number(localStorage.getItem(this.liveBpmKey())); } catch (e) {}
                    return saved > 0 ? saved : (Number(this.current?.bpm) || 80);
                },
                setLiveBpm(value) {
                    const bpm = Math.min(240, Math.max(30, Math.round(value)));
                    if (this.live.running) {
                        // Keeps the position: the count goes on from the current tick at the new tempo.
                        this.live.startTick = this.live.tick;
                        this.live.startedAt = performance.now();
                    }
                    this.liveBpm = bpm;
                    writeStorage(this.liveBpmKey(), bpm);
                },
                // Tapping the tempo: the average of the last taps (a pause of 2 s starts over).
                tapTempo() {
                    const now = performance.now();
                    this.taps = [...this.taps.filter(tap => now - tap < 2000), now].slice(-6);
                    if (this.taps.length < 3) return;
                    const gaps = this.taps.slice(1).map((tap, i) => tap - this.taps[i]);
                    this.setLiveBpm(60000 / (gaps.reduce((a, b) => a + b, 0) / gaps.length));
                },
                toggleLive() { this.live.running ? this.stopLive() : this.startLive(); },
                // Starts right away from the current bar (no count-in) and goes on by itself.
                startLive() {
                    if (!this.liveGrid) return;
                    if (this.live.tick >= this.liveGrid.ticks.length - 1) this.live.tick = 0;
                    this.live.running = true;
                    this.live.startTick = this.live.tick;
                    this.live.startedAt = performance.now();
                    this.live.lastBeat = -1;
                    this.requestWakeLock();
                    // The block where the count starts goes to the top right away.
                    if (this.liveMode) {
                        this.live.shownBlock = null;
                        this.scrollToLiveBar();
                    }
                    if (this.clicksOn(this.live.tick)) this.clickBeat(this.live.tick % (this.liveGrid.perBar * 2) === 0);
                    const loop = () => {
                        if (!this.live.running) return;
                        const beatMs = 60000 / this.liveBpm;
                        let tick = this.live.startTick + Math.floor((performance.now() - this.live.startedAt) / (beatMs / 2));
                        const marker = this.liveGrid.markers.find(at => at <= tick && at > this.live.tick && !this.live.usedMarkers.includes(at));
                        if (marker !== undefined) {
                            this.live.usedMarkers.push(marker);
                            this.live.startTick = 0;
                            this.live.startedAt = performance.now();
                            tick = 0;
                        }
                        if (tick >= this.liveGrid.ticks.length) return this.stopLive();
                        if (tick !== this.live.tick) {
                            const before = this.liveBar;
                            this.live.tick = tick;
                            if (this.clicksOn(tick)) this.clickBeat(tick % (this.liveGrid.perBar * 2) === 0);
                            if (this.liveMode && this.liveBar !== before) this.scrollToLiveBar();
                            if (!this.liveMode) this.followLiveTick();
                        }
                        this.live.frame = requestAnimationFrame(loop);
                    };
                    this.live.frame = requestAnimationFrame(loop);
                },
                stopLive() {
                    if (this.live.frame) cancelAnimationFrame(this.live.frame);
                    this.live.running = false;
                    this.releaseWakeLock();
                },
                // Tapping a bar: the count goes on from it (right away when running).
                goToLiveBar(bar) {
                    this.goToLiveTick(bar * this.liveGrid.perBar * 2);
                },
                goToLiveTick(tick) {
                    if (!this.bpmMode) {
                        const time = this.timeOfLiveTick(tick);
                        if (time !== null) this.seek(Math.max(0, time));
                        this.live.tick = tick;
                        if (this.liveMode) this.scrollToLiveBar(true);
                        return;
                    }
                    this.live.tick = tick;
                    this.live.startTick = this.live.tick;
                    this.live.startedAt = performance.now();
                    if (!this.liveMode) this.followLiveTick(true);
                    else if (!this.liveEdit) this.fitFocus();
                },
                // ---- The BPM count shown in the map and the chord sheet: the block, bar and chord of the current beat ----
                // Start tick of each chord of the map (chord n of the map, as numbered for the highlights).
                get liveChordStarts() {
                    const structure = Array.isArray(this.current?.structure) ? this.current.structure : [];
                    return memo('liveChordStarts', [this.currentId, structure], () => {
                        const starts = [];
                        let tick = 0;
                        structure.forEach(section => {
                            if (!section || typeof section !== 'object' || section.jump === 'start') return;
                            const chords = chordsOnly(Array.isArray(section.chords) ? section.chords.map(String) : []);
                            const durations = Array.isArray(section.durations) ? section.durations.map(Number) : [];
                            chords.forEach((chord, i) => { starts.push(tick); tick += Math.round((durations[i] || 0) * 2); });
                        });
                        return starts;
                    });
                },
                // Index in the view shown (map: the structure; chord sheet: its sections) of block `index` of the structure.
                viewIndexOf(index) {
                    if (!this.sheetMode) return index;
                    const sheet = this.current?.chord_sheet || [];
                    const structure = Array.isArray(this.current?.structure) ? this.current.structure : [];
                    if (sheet.length === structure.length) return index;
                    const k = structure.slice(0, index).filter(block => block && block.jump !== 'start').length;
                    const found = sheet.map((section, i) => i).filter(i => sheet[i]?.jump !== 'start')[k];
                    return found ?? null;
                },
                // Block of the structure shown at `index` of the view (the other way).
                structureIndexOf(index) {
                    if (!this.sheetMode) return index;
                    const sheet = this.current?.chord_sheet || [];
                    const structure = Array.isArray(this.current?.structure) ? this.current.structure : [];
                    if (sheet.length === structure.length) return index;
                    const k = sheet.slice(0, index).filter(section => section?.jump !== 'start').length;
                    return structure.map((block, i) => i).filter(i => structure[i] && structure[i].jump !== 'start')[k] ?? null;
                },
                // Lights the chord and bar of the current beat and shows its block (`jump`: moved there by hand).
                followLiveTick(jump = false) {
                    const tick = this.live.tick;
                    const starts = this.liveChordStarts;
                    let n = -1;
                    while (n + 1 < starts.length && starts[n + 1] <= tick) n++;
                    const bar = this.barChart ? Math.floor(tick / this.barChart.size) : -1;
                    const changedBar = bar !== this.playingBar;
                    this.playingChord = n;
                    this.playingBar = bar;
                    const block = (this.liveGrid?.blocks || []).filter(item => !item.marker && item.tick <= tick).at(-1);
                    const index = block ? this.viewIndexOf(block.index) : null;
                    if (index !== null && (index !== this.currentSection || jump)) return this.showSection(index);
                    if (changedBar && bar >= 0 && this.live.running) this.keepInView(`.sections [data-map-bar="${bar}"]`);
                    else if (this.sheetMode && this.live.running) this.keepInView(`.sections [data-chord="${n}"]`);
                },
                // Scrolls when the element went below or above the visible part of the page.
                keepInView(selector) {
                    this.$nextTick(() => {
                        const element = document.querySelector(selector);
                        if (!element) return;
                        const box = element.getBoundingClientRect();
                        const top = (document.querySelector('.chrome')?.offsetHeight ?? 0) + 8;
                        if (box.bottom > window.innerHeight * .85 || box.top < top) {
                            window.scrollBy({ top: box.top - Math.max(top, window.innerHeight * .3), behavior: 'smooth' });
                        }
                    });
                },
                // Each bar shown big gets the size at which its longest chord fits its beat on one line (never bigger than
                // set in the layout, never under a third of it; a quarter for the bars around the current one).
                fitFocusText() {
                    this.$nextTick(() => document.querySelectorAll('.focus-bar').forEach(bar => {
                        const beats = [...bar.querySelectorAll('.focus-beat')];
                        bar.style.setProperty('--focus-fit', '1');
                        let fit = 1;
                        beats.forEach(beat => {
                            if (beat.scrollWidth > beat.clientWidth + 1) fit = Math.min(fit, (beat.clientWidth - 6) / (beat.scrollWidth - 6));
                        });
                        const smallest = bar.classList.contains('is-now') ? 0.34 : 0.25;
                        fit = Math.max(smallest, Math.floor(fit * 100) / 100);
                        bar.style.setProperty('--focus-fit', String(fit));
                        // Padding and rounding: a little smaller until nothing is cut off.
                        const cut = () => beats.some(beat => beat.scrollWidth > beat.clientWidth + 1);
                        while (cut() && fit > smallest) {
                            fit = Math.max(smallest, Math.round((fit - 0.03) * 100) / 100);
                            bar.style.setProperty('--focus-fit', String(fit));
                        }
                    }));
                },
                // The current and next bar: the page at its top, their area filling what is visible down to the blocks bar,
                // with the two bars in its middle.
                fitFocus() {
                    this.fitFocusText();
                    this.$nextTick(() => {
                        const element = document.querySelector('.live-focus');
                        if (!element) return;
                        if (window.scrollY > 0) window.scrollTo({ top: 0 });
                        const bar = document.querySelector('.jump-bar');
                        // The blocks bar is fixed at the bottom (when shown): the area ends above it.
                        const bottom = bar && bar.getClientRects().length ? bar.getBoundingClientRect().top : window.innerHeight;
                        const height = Math.max(160, bottom - element.getBoundingClientRect().top - 12);
                        // Only when it changes (setting it changes the page's size, which calls this again).
                        if (Math.abs(height - parseFloat(element.style.minHeight || 0)) > 1) element.style.minHeight = `${height}px`;
                    });
                },
                // A new block goes to the top of the screen (as in the map and the chord sheet); within a block, the page
                // only scrolls when the current bar would be out of sight.
                scrollToLiveBar(force = false) {
                    if (!this.liveEdit) return this.fitFocus();
                    if (force) this.live.shownBlock = null;
                    const size = (this.liveGrid?.perBar ?? 4) * 2;
                    const block = (this.liveGrid?.blocks || []).filter(item => !item.marker && item.tick <= this.live.tick).at(-1);
                    const newBlock = !!block && block.tick !== this.live.shownBlock;
                    this.live.shownBlock = block ? block.tick : null;
                    this.$nextTick(() => {
                        const bar = newBlock ? Math.floor(block.tick / size) : this.liveBar;
                        const element = document.querySelector(`[data-live-bar="${bar}"]`);
                        if (!element) return;
                        element.style.scrollMarginTop = `${(document.querySelector('.chrome')?.offsetHeight ?? 0) + 8}px`;
                        element.scrollIntoView({ behavior: 'smooth', block: newBlock ? 'start' : 'nearest' });
                    });
                },
                // A tick that clicks: each beat, and each "and" too when the song's click is doubled (the drummer's
                // tempo; the bars keep the BPM).
                clicksOn(tick) { return tick % 2 === 0 || !!this.liveGrid?.clickDouble; },
                // Metronome click (when on); the first beat of a bar is higher.
                clickBeat(accent) {
                    if (!this.live.click) return;
                    try {
                        this.clickContext = this.clickContext || new AudioContext();
                        const context = this.clickContext;
                        const oscillator = context.createOscillator();
                        const gain = context.createGain();
                        oscillator.frequency.value = accent ? 1600 : 1000;
                        gain.gain.setValueAtTime(0.4, context.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.05);
                        oscillator.connect(gain).connect(context.destination);
                        oscillator.start();
                        oscillator.stop(context.currentTime + 0.06);
                    } catch (e) {}
                },

                // ---- Section timer ----
                // Start times come from the structure ("start" of each section); a section ends where the
                // next timed section starts, and the last one has no end.
                // ---- View mode: chord map (structure) or chord sheet; each has its own blocks and times ----
                get hasSheet() { return Array.isArray(this.current?.chord_sheet) && this.current.chord_sheet.length > 0; },
                get sheetMode() { return this.hasSheet && this.settings.viewMode === 'sheet'; },
                get liveMode() { return this.hasLive && this.settings.viewMode === 'live'; },
                // The song is followed by its BPM (not by the recording) when it has bars and its sound (audio or video) is
                // not on, in every view; with the sound on, the recording plays and the bars follow it.
                get bpmMode() {
                    if (!this.hasLive) return false;
                    return !(this.settings.youtubeSound && (this.audioUrl || this.videoId));
                },
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
                    // The count goes on in the other view; with the recording, the clock does.
                    if (this.bpmMode) {
                        if (this.playing) this.pause();
                        this.followLiveTick(true);
                        if (this.liveMode) this.scrollToLiveBar();
                        return;
                    }
                    this.stopLive();
                    if (this.liveMode) this.followLiveFromTime(this.elapsed);
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
                    return memo('sheetPassing', [structure, sheet], () => this.buildSheetPassing(structure, sheet));
                },
                buildSheetPassing(structure, sheet) {
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
                // ---- Chord being played: start time of each chord of the map, from the blocks' times, the chords'
                // beats and the BPM (null when the map has no durations for every block or the song has no BPM) ----
                chordTimesCache: { key: null, value: null },
                get chordTimes() {
                    const song = this.current;
                    const key = song ? `${song.id}:${song.bpm}:${song.instrumentVersion}` : null;
                    if (this.chordTimesCache.key === key) return this.chordTimesCache.value;
                    let times = [];
                    const beat = 60 / Number(song?.bpm);
                    let end = null;
                    for (const section of (Array.isArray(song?.structure) ? song.structure : [])) {
                        if (!section || typeof section !== 'object' || section.jump === 'start') continue;
                        const chords = chordsOnly(Array.isArray(section.chords) ? section.chords.map(String) : []);
                        const durations = Array.isArray(section.durations) ? section.durations.map(Number) : [];
                        if (!(beat > 0) || durations.length !== chords.length || durations.some(value => !(value > 0))) { times = null; break; }
                        // The first block's start is the delay in the recording; from there only the BPM counts
                        // (other blocks' marked starts would leave gaps when the BPM was changed).
                        let time = end ?? parseStart(section.start) ?? 0;
                        durations.forEach(value => { times.push(time); time += value * beat; });
                        end = time;
                    }
                    this.chordTimesCache = { key, value: times?.length ? times : null };
                    return this.chordTimesCache.value;
                },
                playingChord: -1,
                // Number of the chord being played at `time` (-1 before the song starts or without chord times).
                chordAt(time) {
                    const times = this.chordTimes;
                    if (!times || (!this.playing && time === 0)) return -1;
                    let n = -1;
                    while (n + 1 < times.length && times[n + 1] <= time + 0.05) n++;
                    return n;
                },
                followChord() {
                    const n = this.chordAt(this.elapsed);
                    const bar = n < 0 ? -1 : this.barAt(n, this.elapsed);
                    if (bar !== this.playingBar) {
                        this.playingBar = bar;
                        // Keeps the bar in view in the map (the section scroll handles new blocks).
                        if (bar >= 0 && this.playing) this.$nextTick(() => {
                            const element = document.querySelector(`.sections [data-map-bar="${bar}"]`);
                            if (!element) return;
                            const box = element.getBoundingClientRect();
                            const top = (document.querySelector('.chrome')?.offsetHeight ?? 0) + 8;
                            if (box.bottom > window.innerHeight * .85 || box.top < top) {
                                window.scrollBy({ top: box.top - Math.max(top, window.innerHeight * .3), behavior: 'smooth' });
                            }
                        });
                    }
                    if (n === this.playingChord) return;
                    this.playingChord = n;
                    // Keeps the chord in view when it goes below the screen (the section scroll handles new blocks).
                    if (n < 0 || !this.playing) return;
                    this.$nextTick(() => {
                        const element = document.querySelector(`.sections [data-chord="${n}"]`);
                        if (!element) return;
                        const box = element.getBoundingClientRect();
                        const top = (document.querySelector('.chrome')?.offsetHeight ?? 0) + 8;
                        if (box.bottom > window.innerHeight * .85 || box.top < top) {
                            window.scrollBy({ top: box.top - Math.max(top, window.innerHeight * .3), behavior: 'smooth' });
                        }
                    });
                },
                // Number of a chord of the chord sheet in the whole map (chord n of the sheet is chord n of the map), or
                // null when the sheet does not have the map's chords.
                sheetNumbersCache: { key: null, value: null },
                get sheetNumbers() {
                    const song = this.current;
                    const key = song ? `${song.id}:${song.instrumentVersion}` : null;
                    if (this.sheetNumbersCache.key === key) return this.sheetNumbersCache.value;
                    const structure = Array.isArray(song?.structure) ? song.structure : [];
                    const sheet = Array.isArray(song?.chord_sheet) ? song.chord_sheet : [];
                    const mapChords = structure.flatMap(section => chordsOnly(Array.isArray(section?.chords) ? section.chords.map(String) : []));
                    let value = null;
                    if (JSON.stringify(mapChords) === JSON.stringify(sheet.flatMap(section => (section.lines || []).flatMap(line => sheetChords(line))))) {
                        let n = 0;
                        value = sheet.map(section => (section.lines || []).map(line => { const start = n; n += sheetChords(line).length; return start; }));
                    }
                    this.sheetNumbersCache = { key, value };
                    return value;
                },
                sheetChordNumber(sectionIndex, lineIndex, ordinal) {
                    const start = this.sheetNumbers?.[sectionIndex]?.[lineIndex];
                    return start === undefined ? null : start + ordinal;
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
                        ? (this.current?.chord_sheet || []).map((section, index) => this.isReturn(index) ? '' : this.timedName(index))
                        : this.sections.map((section, index) => this.isReturn(index) ? '' : this.sectionName(section));
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
                jumpTo(target) {
                    // Live view: the count goes on from the first bar of that block.
                    if (this.liveMode) {
                        const block = this.liveGrid.blocks.find(item => item.index === target.index);
                        if (!block) return;
                        this.goToLiveBar(Math.floor(block.tick / (this.liveGrid.perBar * 2)));
                        return this.scrollToLiveBar();
                    }
                    this.selectSection(target.index);
                },
                isCurrentTarget(target) {
                    if (this.liveMode) {
                        const block = this.liveGrid.blocks.filter(item => item.tick <= this.live.tick).at(-1);
                        return !!block && String(block.name).trim().toUpperCase() === target.key;
                    }
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
                        'is-return': this.isReturn(index),
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
                        this.sound.seek(this.elapsed);
                        this.sound.play();
                    }
                    this.startedAt = performance.now() - this.elapsed * 1000;
                    this.playing = true;
                    this.clockSection = this.hasTimeline ? this.sectionAt(this.elapsed) : null;
                    if (this.currentSection === null) this.showSection(this.clockSection ?? 0);
                    this.ticker = setInterval(() => this.tick(), 100);
                    this.requestWakeLock();
                },
                pause(fromVideo = false) {
                    if (!this.playing) return;
                    if (this.soundActive && !fromVideo) this.sound.pause();
                    this.elapsed = this.soundActive ? this.sound.time() : (performance.now() - this.startedAt) / 1000;
                    this.playing = false;
                    clearInterval(this.ticker);
                    this.releaseWakeLock();
                },
                tick() {
                    // With the song sound on, the clock follows the audio / video time.
                    if (this.soundActive) {
                        this.elapsed = this.sound.time();
                        this.startedAt = performance.now() - this.elapsed * 1000;
                    } else {
                        this.elapsed = (performance.now() - this.startedAt) / 1000;
                    }
                    let newSection = false;
                    if (this.hasTimeline) {
                        const index = this.sectionAt(this.elapsed);
                        if (index !== this.clockSection) {
                            this.clockSection = index;
                            this.showSection(index);
                            newSection = true;
                        }
                    }
                    if (newSection) {
                        this.playingChord = this.chordAt(this.elapsed);
                        this.playingBar = this.playingChord < 0 ? -1 : this.barAt(this.playingChord, this.elapsed);
                    } else this.followChord();
                    if (this.liveMode) this.followLiveFromTime(this.elapsed);
                },
                // ---- The Live view following the recording (song sound on): the bar of the time played ----
                // Half beat of the map sounding at `time` (from the chord being played, its start and the BPM).
                liveTickAt(time) {
                    const n = this.chordAt(time);
                    const times = this.chordTimes;
                    const starts = this.liveChordStarts;
                    if (n < 0 || !times || starts[n] === undefined) return 0;
                    const next = starts[n + 1] ?? this.liveGrid.ticks.length;
                    return Math.min(next - 1, starts[n] + Math.max(0, Math.floor((time - times[n]) / (30 / Number(this.current.bpm)))));
                },
                // Time in the recording of a half beat of the map (where its chord starts there, plus the beats since).
                timeOfLiveTick(tick) {
                    const times = this.chordTimes;
                    const starts = this.liveChordStarts;
                    if (!times) return null;
                    let n = 0;
                    while (n + 1 < starts.length && starts[n + 1] <= tick) n++;
                    return times[n] + (tick - starts[n]) * (30 / Number(this.current.bpm));
                },
                followLiveFromTime(time) {
                    if (!this.liveGrid) return;
                    const tick = this.liveTickAt(time);
                    if (tick === this.live.tick) return;
                    const before = this.liveBar;
                    this.live.tick = tick;
                    if (this.liveBar !== before) this.scrollToLiveBar();
                },
                // Moves the timer to `time` without changing play/pause.
                seek(time) {
                    this.elapsed = time;
                    if (this.soundActive) this.sound.seek(time);
                    if (this.playing) this.startedAt = performance.now() - time * 1000;
                    this.clockSection = this.hasTimeline ? this.sectionAt(time) : null;
                    this.playingChord = this.chordAt(time);
                    this.playingBar = this.playingChord < 0 ? -1 : this.barAt(this.playingChord, time);
                    if (this.liveMode && !this.bpmMode) this.followLiveFromTime(time);
                },
                resetPlayback() {
                    this.pause();
                    this.elapsed = 0;
                    this.currentSection = null;
                    this.clockSection = null;
                    this.playingChord = -1;
                    this.playingBar = -1;
                },

                // Tapping a block marks it as the current section and re-syncs the timer to its start time.
                // "Back to the start" marker of the map (no chords): tapping it goes to the first block.
                isReturn(index) { return this.timedList?.[index]?.jump === 'start'; },
                selectSection(index) {
                    if (this.isReturn(index)) {
                        const first = this.timedList.findIndex((section, i) => !this.isReturn(i));
                        if (first === -1) return;
                        index = first;
                    }
                    // Counting by the BPM: the count goes on from the start of that block.
                    if (this.bpmMode) {
                        const structureIndex = this.structureIndexOf(index);
                        const block = (this.liveGrid?.blocks || []).find(item => !item.marker && item.index === structureIndex);
                        if (block) return this.goToLiveTick(block.tick);
                    }
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

                // ---- Song sound: the song's audio file when it has one, otherwise its YouTube video ----
                soundReady: false,
                get videoId() { return youtubeIdFrom(this.current?.youtube_url); },
                get audioUrl() { return this.current?.audio_url || null; },
                get soundActive() {
                    if (!this.settings.youtubeSound) return false;
                    return this.audioUrl ? true : (!!this.videoId && this.soundReady && !!soundPlayer);
                },
                // The player in use, with the same commands for the audio file and the YouTube video.
                get sound() {
                    return this.audioUrl
                        ? { seek: (time) => { audioPlayer.currentTime = time; }, play: () => audioPlayer.play().catch(() => this.pause(true)), pause: () => audioPlayer.pause(), time: () => audioPlayer.currentTime }
                        : { seek: (time) => soundPlayer.seekTo(time, true), play: () => soundPlayer.playVideo(), pause: () => soundPlayer.pauseVideo(), time: () => soundPlayer.getCurrentTime() };
                },
                // Playing, pausing or reaching the end of the audio file also drives the section timer.
                setupAudio() {
                    audioPlayer.addEventListener('play', () => { if (this.audioUrl && !this.playing) this.play(true); });
                    audioPlayer.addEventListener('pause', () => { if (this.audioUrl && this.playing) this.pause(true); });
                },
                // Loads the current song's audio file or YouTube video (paused).
                setupSound() {
                    if (!this.settings.youtubeSound || !this.audioUrl) audioPlayer.pause();
                    if (this.settings.youtubeSound && this.audioUrl) {
                        soundPlayer?.pauseVideo?.();
                        if (audioPlayer.src !== this.audioUrl) {
                            audioPlayer.src = this.audioUrl;
                            audioPlayer.load();
                        }
                        return;
                    }
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
                    if (event.key === ' ' && this.current) { event.preventDefault(); this.bpmMode ? this.toggleLive() : this.togglePlay(); }
                    if (['ArrowRight', 'PageDown'].includes(event.key)) { event.preventDefault(); this.go(this.currentIndex + 1); }
                    if (['ArrowLeft', 'PageUp'].includes(event.key)) { event.preventDefault(); this.go(this.currentIndex - 1); }
                    if (['ArrowDown', 'ArrowUp'].includes(event.key) && this.current) { event.preventDefault(); this.moveBlock(event.key === 'ArrowDown' ? 1 : -1); }
                },
                // ↑ ↓: the block before / after the current one ("back to the start" markers skipped); in live mode
                // the count goes on from the first bar of that block.
                moveBlock(step) {
                    if (this.liveMode) {
                        const blocks = (this.liveGrid?.blocks || []).filter(block => !block.marker);
                        if (!blocks.length) return;
                        const current = blocks.filter(block => block.tick <= this.live.tick).length - 1;
                        const target = blocks[Math.max(0, Math.min(blocks.length - 1, current + step))];
                        this.goToLiveBar(Math.floor(target.tick / (this.liveGrid.perBar * 2)));
                        return this.scrollToLiveBar();
                    }
                    const index = this.blockStepTarget(step);
                    if (index !== null) this.selectSection(index);
                },
                // Block before / after the current one in the map or sheet (null when there is none).
                blockStepTarget(step) {
                    const count = this.timedList?.length || 0;
                    if (!count || !step) return null;
                    let index = this.currentSection === null ? (step > 0 ? -1 : count) : this.currentSection;
                    do { index += step; } while (index >= 0 && index < count && this.isReturn(index));
                    return index >= 0 && index < count ? index : null;
                },
                // With a block selected, scrolling the page (wheel or swipe) goes to the block above / below, like ↑ ↓.
                // Past the first / last block the page scrolls as usual.
                blockScroll: { wheel: 0, wheelAt: 0, lockedUntil: 0, touchX: null, touchY: null, lastY: 0, dragX: 0, touch: null },
                get blockScrollActive() {
                    return !!this.current && !this.liveMode && this.currentSection !== null && this.gesturesFree;
                },
                // No panel / popup open.
                get gesturesFree() {
                    return !(this.panelOpen || this.optionsOpen || this.removing || this.installHelp || this.suggestEdit || this.keyPickerOpen);
                },
                gestureTarget(event) {
                    return !event.ctrlKey && !event.target.closest?.('.panel, .options, .modal, .suggest-pop, .jump-bar, textarea, select, input');
                },
                scrollSelectsBlock(event) {
                    return this.blockScrollActive && this.gestureTarget(event);
                },
                // Horizontal drag: next / previous song, like ← →.
                swipeChangesSong(event) {
                    return !!this.current && this.gesturesFree && this.gestureTarget(event);
                },
                onWheel(event) {
                    if (!this.scrollSelectsBlock(event) || !event.deltaY) return;
                    const state = this.blockScroll;
                    const now = performance.now();
                    // One block per gesture: a trackpad keeps sending events while it slows down.
                    if (now < state.lockedUntil) {
                        state.lockedUntil = Math.max(state.lockedUntil, now + 200);
                        return event.preventDefault();
                    }
                    if (now - state.wheelAt > 200) state.wheel = 0;
                    state.wheelAt = now;
                    state.wheel += event.deltaMode ? event.deltaY * 40 : event.deltaY;
                    const step = Math.sign(state.wheel);
                    if (this.blockStepTarget(step) === null) return;
                    event.preventDefault();
                    if (Math.abs(state.wheel) < 30) return;
                    state.wheel = 0;
                    state.lockedUntil = now + 400;
                    this.moveBlock(step);
                },
                onTouchStart(event) {
                    const touch = event.touches.length === 1 ? event.touches[0] : null;
                    Object.assign(this.blockScroll, { touchX: touch?.clientX ?? null, touchY: touch?.clientY ?? null, touch: null });
                },
                // The blocks have no native vertical scroll while one is selected (touch-action, see .block-swipe):
                // a fast swipe would otherwise start the page scroll before it can be stopped, and fling past the block.
                onTouchMove(event) {
                    const state = this.blockScroll;
                    if (state.touchY === null || event.touches.length !== 1 || state.touch === 'native') return;
                    const x = event.touches[0].clientX;
                    const y = event.touches[0].clientY;
                    const delta = state.touchY - y;
                    const sideways = state.touchX - x;
                    if (state.touch === null) {
                        if (Math.abs(delta) < 8 && Math.abs(sideways) < 8) return this.scrollSelectsBlock(event) && event.cancelable && event.preventDefault();
                        // Clearly sideways: the song is dragged (next / previous one). Otherwise a block in that direction is
                        // selected; above the first / below the last one the page follows the finger.
                        if (Math.abs(sideways) > Math.abs(delta) * 1.5 && this.swipeChangesSong(event)) state.touch = 'song';
                        else if (Math.abs(sideways) > Math.abs(delta) || !this.scrollSelectsBlock(event)) return state.touch = 'native';
                        else state.touch = this.blockStepTarget(Math.sign(delta)) === null ? 'page' : 'block';
                        state.lastY = state.touchY;
                    }
                    if (event.cancelable) event.preventDefault();
                    if (state.touch === 'song') {
                        this.dragSong(-sideways);
                    } else if (state.touch === 'page') {
                        window.scrollBy(0, state.lastY - y);
                        state.lastY = y;
                    } else if (state.touch === 'block' && Math.abs(delta) >= 40) {
                        state.touch = 'done';
                        this.moveBlock(Math.sign(delta));
                    }
                },
                // The song follows the finger; it only changes when let go past a third of the screen, otherwise it
                // goes back to its place. Past the first / last song it barely moves.
                songView() { return document.querySelector('.stage article'); },
                dragSong(offset) {
                    const view = this.songView();
                    if (!view) return;
                    const shown = this.songs[this.currentIndex + (offset < 0 ? 1 : -1)] ? offset : offset * .2;
                    this.blockScroll.dragX = offset;
                    view.parentElement.classList.add('is-swiping');
                    view.style.transition = 'none';
                    view.style.transform = `translateX(${shown}px)`;
                    view.style.opacity = String(1 - Math.min(Math.abs(shown) / window.innerWidth, 1) * .6);
                },
                onTouchEnd(cancelled = false) {
                    const state = this.blockScroll;
                    if (state.touch !== 'song') return;
                    state.touch = 'done';
                    const view = this.songView();
                    if (!view) return;
                    const offset = state.dragX;
                    const step = offset < 0 ? 1 : -1;
                    const change = !cancelled && Math.abs(offset) >= window.innerWidth / 3 && !!this.songs[this.currentIndex + step];
                    view.style.transition = 'transform .18s ease-out, opacity .18s ease-out';
                    view.style.transform = change ? `translateX(${offset < 0 ? '-' : ''}100vw)` : '';
                    view.style.opacity = change ? '0' : '';
                    setTimeout(() => {
                        if (change) this.go(this.currentIndex + step);
                        view.style.transition = view.style.transform = view.style.opacity = '';
                        view.parentElement?.classList.remove('is-swiping');
                    }, 180);
                },
                openSetlist(id) {
                    if (id) window.location.href = `${config.urls.setlist}/${id}`;
                },

                // Without a connection it throws an error with `offline`: the change is then made only on this
                // device (nothing is saved).
                async request(method, url, body) {
                    let response;
                    try {
                        if (!navigator.onLine) throw new Error();
                        response = await fetch(url, {
                            method,
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: body === undefined ? undefined : JSON.stringify(body),
                        });
                    } catch (e) {
                        const error = new Error(this.messages.offlineOnly);
                        error.offline = true;
                        throw error;
                    }

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
                        if (!error.offline) song.setlist_key = previous;
                        else this.rememberOffline({ song, key: song.setlist_key });
                        this.notify(error.message, !error.offline);
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
                        if (!navigator.onLine) throw new Error();
                        const response = await fetch(`${config.urls.search}?q=${encodeURIComponent(this.term)}`, { headers: { Accept: 'application/json' } });
                        this.results = await response.json();
                    } catch (error) {
                        // Without a connection: the songs of the offline copy (see "Download for offline").
                        const plain = (text) => String(text ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                        const term = plain(this.term.trim());
                        this.results = (await this.offlineCatalog())
                            .filter(song => !term || plain(song.title).includes(term) || plain(song.artist).includes(term))
                            .slice(0, 20);
                    }
                    this.searched = true;
                },
                // Every song with its chords, from the offline copy (empty when it was never downloaded).
                async offlineCatalog() {
                    try {
                        const response = await caches.match(config.urls.catalog);
                        return response ? await response.json() : [];
                    } catch (e) {
                        return [];
                    }
                },
                isInSetlist(song) { return this.songs.some(item => item.id === song.id); },
                async addSong(song) {
                    if (this.isInSetlist(song)) return;
                    this.adding = true;
                    try {
                        const added = await this.request('POST', `${config.urls.setlist}/${setlist.id}/songs`, { song_id: song.id });
                        this.applyInstrument(added);
                        this.songs.push(added);
                        if (!this.currentId) this.currentId = added.id;
                        this.notify(this.messages.added);
                    } catch (error) {
                        const saved = error.offline ? (await this.offlineCatalog()).find(item => item.id === song.id) : null;
                        if (saved) {
                            const added = { ...saved, position: this.songs.length + 1 };
                            this.applyInstrument(added);
                            this.songs.push(added);
                            if (!this.currentId) this.currentId = saved.id;
                            this.rememberOffline({ add: added });
                        }
                        this.notify(error.offline && !saved ? this.messages.offlineNoCatalog : error.message, !saved);
                    } finally {
                        this.adding = false;
                    }
                },

                askRemove(song) { this.removing = song; },
                async confirmRemove() {
                    const song = this.removing;
                    this.removing = null;
                    try {
                        let offline = false;
                        try {
                            await this.request('DELETE', this.songUrl(song));
                        } catch (error) {
                            if (!error.offline) throw error;
                            offline = true;
                            this.notify(error.message);
                        }
                        const index = this.songs.findIndex(item => item.id === song.id);
                        this.songs.splice(index, 1);
                        this.renumber();
                        if (this.currentId === song.id) {
                            this.currentId = (this.songs[index] ?? this.songs[index - 1])?.id ?? null;
                        }
                        if (offline) this.rememberOffline({ remove: song });
                        else this.notify(this.messages.removed);
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
                        if (!error.offline) {
                            this.songs.sort((a, b) => previousOrder.indexOf(a.id) - previousOrder.indexOf(b.id));
                            this.renumber();
                        } else {
                            this.rememberOffline({ order: true });
                        }
                        this.notify(error.message, !error.offline);
                    }
                },
            };
        }
    </script>
</body>

</html>
