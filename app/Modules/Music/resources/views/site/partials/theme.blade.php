{{-- Color tokens of the public pages (dark by default, light when the device prefers it) --}}
<style>
    :root {
        --bg: #111318;
        --surface: #1b1e25;
        --surface-2: #252a33;
        --border: #313743;
        --text: #f3f4f6;
        --muted: #9aa3b2;
        --accent: #f5a524;
        --accent-text: #1b1300;
        --danger: #ef4444;
        --chord: #ffffff;
        --accent-soft: rgba(245, 165, 36, .14);
        /* Colors by kind of block (intro, verse, pre-chorus, chorus, bridge, instrumental, ending) */
        --type-intro: #7dd3fc;
        --type-verse: #6ee7b7;
        --type-prechorus: #fcd34d;
        --type-chorus: #fda4af;
        --type-bridge: #c4b5fd;
        --type-instrumental: #a5b4fc;
        --type-ending: #cbd5e1;
        --type-other: var(--chord);
    }

    @media (prefers-color-scheme: light) {
        :root {
            --bg: #f7f7f8;
            --surface: #ffffff;
            --surface-2: #f0f1f3;
            --border: #d9dce1;
            --text: #111318;
            --muted: #5f6673;
            --accent: #d9860b;
            --accent-text: #ffffff;
            --danger: #dc2626;
            --chord: #111318;
            --accent-soft: rgba(217, 134, 11, .12);
            --type-intro: #0369a1;
            --type-verse: #047857;
            --type-prechorus: #b45309;
            --type-chorus: #be123c;
            --type-bridge: #6d28d9;
            --type-instrumental: #4338ca;
            --type-ending: #475569;
            --type-other: var(--chord);
        }
    }
</style>
