{{-- Shared look of the modules' lists and forms (songs, setlists, members…): page, cards, buttons, search, the filter /
     actions sheet (a bottom sheet on phones), form sections and fields, status tags. Included once. --}}
@once
    <style>
        .m-page { max-width: 76rem; margin: 0 auto; padding: 1.5rem 1.5rem 3rem; }
        .m-page.is-narrow { max-width: 52rem; }
        @media (max-width: 639px) { .m-page { padding: .75rem .75rem 2rem; } }

        .m-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .9rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .m-card-body { padding: 1.25rem; }
        @media (max-width: 639px) { .m-card-body { padding: .9rem; } }

        /* Page head: title and a line under it, actions on the right (under on phones) */
        .m-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .75rem 1rem; margin-bottom: 1rem; }
        .m-head h1 { margin: 0; font-size: 1.35rem; font-weight: 800; color: #111827; letter-spacing: -.01em; }
        .m-head p { margin: .2rem 0 0; font-size: .875rem; color: #6b7280; }
        .m-head-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        /* Phones: the top bar already names the page */
        @media (max-width: 639px) { .m-head { align-items: center; } .m-head h1 { display: none; } .m-head p { margin: 0; } }
        .m-back { display: inline-flex; align-items: center; gap: .4rem; margin-bottom: .5rem; font-size: .85rem; font-weight: 600; color: #6b7280; text-decoration: none; }
        .m-back:hover { color: #111827; }

        .m-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 40px; padding: 0 .95rem; border: 1px solid #d1d5db; border-radius: .6rem; background: #fff; color: #374151; font-size: .875rem; font-weight: 600; white-space: nowrap; text-decoration: none; cursor: pointer; transition: background .15s, border-color .15s, color .15s; }
        .m-btn:hover { background: #f9fafb; border-color: #c4c8cf; }
        .m-btn:disabled { opacity: .45; cursor: default; }
        .m-btn.is-primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .m-btn.is-primary:hover { background: var(--brand-hover); border-color: var(--brand-hover); }
        .m-btn.is-danger { background: #dc2626; border-color: #dc2626; color: #fff; }
        .m-btn.is-danger:hover { background: #b91c1c; }
        .m-btn.is-ghost { border-color: transparent; background: none; color: #6b7280; }
        .m-btn.is-ghost:hover { background: #f3f4f6; color: #111827; }
        .m-btn.is-icon { width: 40px; padding: 0; }
        .m-btn .m-count { min-width: 1.25rem; padding: 0 .35rem; border-radius: 999px; background: var(--brand); color: #fff; font-size: .7rem; line-height: 1.25rem; }
        /* Phones: buttons with an icon show only the icon when marked so */
        @media (max-width: 639px) { .m-btn .m-hide-sm { display: none; } }

        /* Search box with its icon, and the controls next to it */
        .m-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .m-search { position: relative; flex: 1 1 16rem; min-width: 0; }
        .m-search i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: .85rem; pointer-events: none; }
        .m-search input { width: 100%; min-height: 40px; padding: 0 .85rem 0 2.3rem; border: 1px solid #d1d5db; border-radius: .6rem; background-color: #fff; font-size: .9rem; }
        .m-search input:focus, .m-input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-100); }

        .m-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .5rem; margin-top: .75rem; }
        .m-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .65rem; border-radius: 999px; background: var(--brand-soft); color: var(--brand-strong); font-size: .78rem; font-weight: 700; text-decoration: none; }
        .m-chip:hover { background: var(--brand-100); }
        .m-muted { font-size: .85rem; color: #6b7280; }
        .m-link { font-size: .85rem; font-weight: 700; color: var(--brand-text); text-decoration: none; }
        .m-link:hover { text-decoration: underline; }

        /* Status tags (reviewed, times, media) */
        .m-tag { display: inline-flex; align-items: center; gap: .3rem; padding: .12rem .5rem; border-radius: 999px; font-size: .75rem; font-weight: 700; white-space: nowrap; text-decoration: none; }
        .m-tag.is-ok { background: #dcfce7; color: #166534; }
        .m-tag.is-part { background: #fef3c7; color: #92400e; }
        .m-tag.is-off { background: #f3f4f6; color: #9ca3af; }
        .m-tag.is-key { min-width: 2.3rem; justify-content: center; border: 1px solid var(--brand); background: #fff; color: var(--brand-text); font-weight: 800; }
        .m-tag.is-media { background: #f3f4f6; color: #4b5563; }
        .m-tag.is-media .fa-youtube { color: #dc2626; }

        /* Sheet: filters, actions; centered on larger screens, from the bottom on phones */
        .m-sheet { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .m-sheet-overlay { position: absolute; inset: 0; background: rgba(17, 24, 39, .45); }
        .m-sheet-box { position: relative; width: min(30rem, 100%); max-height: calc(100vh - 2rem); overflow-y: auto; padding: 1.25rem; border-radius: 1rem; background: #fff; box-shadow: 0 25px 50px rgba(0, 0, 0, .25); }
        .m-sheet-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .m-sheet-head h2 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #111827; }
        .m-sheet-head p { margin: .15rem 0 0; font-size: .85rem; color: #6b7280; }
        .m-sheet-foot { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; margin-top: 1.25rem; }
        @media (max-width: 639px) {
            .m-sheet { align-items: flex-end; padding: 0; }
            .m-sheet-box { width: 100%; max-height: 88vh; border-radius: 1.1rem 1.1rem 0 0; padding-bottom: max(1.25rem, env(safe-area-inset-bottom)); }
            .m-sheet-box::before { content: ''; display: block; width: 2.5rem; height: 4px; margin: -.4rem auto .9rem; border-radius: 999px; background: #e5e7eb; }
            .m-sheet-foot { position: sticky; bottom: calc(-1 * max(1.25rem, env(safe-area-inset-bottom))); margin: 1rem -1.25rem calc(-1 * max(1.25rem, env(safe-area-inset-bottom))); padding: .75rem 1.25rem max(1rem, env(safe-area-inset-bottom)); border-top: 1px solid #f1f2f4; background: #fff; }
            .m-sheet-foot .m-btn { flex: 1; }
        }
        .m-menu { display: grid; gap: .35rem; }
        .m-menu a, .m-menu button { display: flex; align-items: center; gap: .75rem; width: 100%; min-height: 46px; padding: 0 .9rem; border: 1px solid #f1f2f4; border-radius: .65rem; background: #fff; color: #374151; font-size: .92rem; font-weight: 600; text-align: left; text-decoration: none; cursor: pointer; }
        .m-menu a:hover, .m-menu button:hover { background: #f9fafb; border-color: #e5e7eb; }
        .m-menu i { width: 1.1rem; text-align: center; color: #9ca3af; }
        .m-menu .is-danger, .m-menu .is-danger i { color: #dc2626; }

        /* Segmented choice (All / Yes / No) */
        .m-seg { display: inline-flex; width: 100%; padding: 3px; border-radius: .6rem; background: #f3f4f6; }
        .m-seg label { flex: 1; }
        .m-seg input { position: absolute; opacity: 0; pointer-events: none; }
        .m-seg span { display: block; padding: .4rem .5rem; border-radius: .45rem; font-size: .82rem; font-weight: 600; color: #6b7280; text-align: center; cursor: pointer; }
        .m-seg input:checked + span { background: #fff; color: #111827; box-shadow: 0 1px 2px rgba(0, 0, 0, .1); }
        .m-seg input:focus-visible + span { outline: 2px solid var(--brand); }

        /* Forms: sections (a card each), fields in columns on larger screens */
        .m-form { display: grid; gap: 1rem; }
        .m-section { padding: 1.25rem; }
        .m-section-title { display: flex; align-items: center; gap: .5rem; margin: 0 0 1rem; font-size: .95rem; font-weight: 800; color: #111827; }
        .m-section-title i { width: 1.9rem; height: 1.9rem; display: grid; place-items: center; border-radius: .55rem; background: var(--brand-soft); color: var(--brand-hover); font-size: .85rem; }
        .m-section-title small { margin-left: auto; font-size: .75rem; font-weight: 600; color: #9ca3af; }
        @media (max-width: 639px) { .m-section { padding: .9rem; } }
        .m-grid { display: grid; gap: 1rem; }
        @media (min-width: 640px) { .m-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .m-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .m-span-2 { grid-column: span 2; } }
        .m-field { display: grid; gap: .3rem; min-width: 0; }
        .m-label { font-size: .82rem; font-weight: 700; color: #374151; }
        .m-label .m-optional { font-weight: 500; color: #9ca3af; }
        .m-input { width: 100%; min-height: 42px; padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: .6rem; background-color: #fff; font-size: .92rem; color: #111827; }
        select.m-input { padding-right: 2rem; }
        textarea.m-input { min-height: 0; line-height: 1.45; }
        .m-input.is-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; }
        .m-help { margin: 0; font-size: .8rem; color: #6b7280; line-height: 1.4; }
        .m-error { margin: 0; font-size: .8rem; font-weight: 600; color: #dc2626; }
        .m-form-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .5rem; }
        @media (max-width: 639px) { .m-form-actions .m-btn { flex: 1; } }
    </style>
@endonce
