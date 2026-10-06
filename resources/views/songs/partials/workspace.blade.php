{{-- Shared look of the song work pages (section times, layout, practice): one sticky toolbar with the page's
     actions, the player button and an options panel (bottom sheet on phones). Included once per page. --}}
@once
    <style>
        /* Less space around the page on phones */
        .ws-page { padding: .75rem 0; }
        @media (min-width: 640px) { .ws-page { padding: 3rem 0; } }
        .ws-card { padding: .75rem; }
        @media (min-width: 640px) { .ws-card { padding: 2rem; } }

        /* Only on phones / only on larger screens */
        @media (max-width: 639px) { .ws-desktop { display: none !important; } }
        @media (min-width: 640px) { .ws-mobile { display: none !important; } }

        .ws-toolbar { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; gap: .35rem; padding: .45rem 0; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .ws-toolbar .ws-spacer { flex: 1; min-width: 0; }
        .ws-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; flex-shrink: 0; min-width: 38px; min-height: 38px; padding: 0 .7rem; border: 1px solid #d1d5db; border-radius: .5rem; background: #fff; color: #374151; font-size: .85rem; font-weight: 600; white-space: nowrap; cursor: pointer; text-decoration: none; }
        .ws-btn:hover:not(:disabled) { background: #f9fafb; }
        .ws-btn:disabled, .ws-btn.is-disabled { opacity: .4; cursor: default; pointer-events: none; }
        .ws-btn.is-active { border-color: #4f46e5; color: #4f46e5; }
        .ws-btn-primary { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .ws-btn-primary:hover:not(:disabled) { background: #4338ca; }
        .ws-seg { display: inline-flex; flex-shrink: 0; border: 1px solid #d1d5db; border-radius: .5rem; overflow: hidden; }
        .ws-seg button { min-height: 36px; padding: 0 .65rem; border: 0; background: #fff; color: #374151; font-size: .85rem; font-weight: 600; white-space: nowrap; cursor: pointer; }
        .ws-seg button + button { border-left: 1px solid #d1d5db; }
        .ws-seg button.is-active { background: #4f46e5; color: #fff; }
        .ws-play { font-variant-numeric: tabular-nums; }
        .ws-play .ws-time { min-width: 2.4rem; text-align: left; }
        .ws-note { font-size: .8rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        /* Phones: icons only and tighter buttons */
        @media (max-width: 639px) {
            .ws-toolbar { gap: .25rem; }
            .ws-label { display: none; }
            .ws-btn { min-width: 34px; min-height: 36px; padding: 0 .5rem; }
            .ws-seg button { padding: 0 .5rem; }
        }

        /* Options panel: bottom sheet on phones, a box under the toolbar on larger screens */
        .ws-panel-backdrop { position: fixed; inset: 0; z-index: 60; background: rgba(17, 24, 39, .35); }
        .ws-panel { position: fixed; z-index: 61; left: 0; right: 0; bottom: 0; max-height: 80vh; overflow-y: auto; padding: 1rem 1rem max(1rem, env(safe-area-inset-bottom)); background: #fff; border-radius: 1rem 1rem 0 0; box-shadow: 0 -10px 30px rgba(0, 0, 0, .2); }
        @media (min-width: 640px) {
            .ws-panel-backdrop { background: transparent; }
            .ws-panel { left: auto; right: 1.5rem; top: 5rem; bottom: auto; width: 24rem; border-radius: .75rem; border: 1px solid #e5e7eb; box-shadow: 0 15px 35px rgba(0, 0, 0, .15); }
        }
        .ws-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .5rem; font-weight: 700; color: #111827; }
        .ws-panel-section { padding: .75rem 0; border-top: 1px solid #f3f4f6; }
        .ws-panel-section:first-of-type { border-top: 0; }
        .ws-panel-label { margin-bottom: .45rem; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }
        .ws-panel-row { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .ws-panel-help { font-size: .85rem; line-height: 1.45; color: #4b5563; }
    </style>
@endonce
