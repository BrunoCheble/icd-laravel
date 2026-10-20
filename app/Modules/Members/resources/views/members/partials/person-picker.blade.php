{{-- Picks another member (spouse, father, mother) by typing part of the name or NIF. $name (the field), $want ("M",
     "F" or "spouse": of the other gender than the form's), $freeName (optional: the field for a name typed when the
     person is not a member), with window.memberPeople set by the form --}}
@php
    $selected = (string) old($name, $member->{$name});
    $free = isset($freeName) ? (string) old($freeName, $member->{$freeName}) : null;
@endphp
<div class="person-picker" x-data="personPicker(@js(['selected' => $selected, 'want' => $want, 'free' => $free]))" @click.outside="open = false">
    <input type="hidden" name="{{ $name }}" :value="selected">
    @isset($freeName)<input type="hidden" name="{{ $freeName }}" :value="selected ? '' : free">@endisset
    <template x-if="current">
        <div class="person-picker-current">
            <i class="fa-solid fa-user"></i>
            <span><strong x-text="current.name"></strong><small x-text="current.member ? (current.document ? 'NIF ' + current.document : '') : @js(__('not a member'))"></small></span>
            <button type="button" class="m-btn is-ghost is-icon" @click="clear()" aria-label="{{ __('Remove') }}"><i class="fa-solid fa-xmark"></i></button>
        </div>
    </template>
    <template x-if="!current">
        <div class="m-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" x-model="term" x-ref="term" @focus="open = true" @input="open = true; active = 0"
                @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="pick(matches[active])" @keydown.escape="open = false"
                placeholder="{{ __('Name or NIF') }}" autocomplete="off" aria-label="{{ __('Search') }}">
        </div>
    </template>
    <div class="person-picker-list" x-show="open && !current" x-cloak>
        <template x-for="(person, index) in matches" :key="person.id">
            <button type="button" @mousedown.prevent="pick(person)" :class="{ 'is-active': index === active }">
                <strong x-text="person.name"></strong><small x-text="person.document ? 'NIF ' + person.document : ''"></small>
            </button>
        </template>
        <p x-show="!matches.length && !canTypeName">{{ __('No members found.') }}</p>
        {{-- Not a member: the name typed is kept --}}
        <button type="button" class="is-free" x-show="canTypeName" @mousedown.prevent="useName()" :class="{ 'is-active': active === matches.length }">
            <i class="fa-solid fa-user-plus"></i> <strong x-text="@js(__('Use')) + ' «' + term.trim() + '»'"></strong><small>{{ __('not a member') }}</small>
        </button>
    </div>
</div>

@once
    <style>
        .person-picker { position: relative; }
        .person-picker .m-search { display: block; }
        .person-picker-current { display: flex; align-items: center; gap: .65rem; min-height: 42px; padding: 0 .25rem 0 .75rem; border: 1px solid var(--bd-d1d5db); border-radius: .6rem; background: var(--brand-soft); }
        .person-picker-current > i { color: var(--brand-text); }
        .person-picker-current span { flex: 1; min-width: 0; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 .5rem; }
        .person-picker-current strong { font-size: .92rem; font-weight: 700; color: var(--tx-111827); }
        .person-picker-current small, .person-picker-list small { font-size: .78rem; color: var(--tx-6b7280); }
        .person-picker-list { position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + 4px); max-height: 15rem; overflow-y: auto; padding: .3rem; border: 1px solid var(--bd-e5e7eb); border-radius: .7rem; background: var(--bg-fff); box-shadow: 0 12px 30px rgba(16, 24, 40, .15); }
        .person-picker-list button { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 .5rem; width: 100%; padding: .55rem .65rem; border: 0; border-radius: .5rem; background: none; text-align: left; cursor: pointer; }
        .person-picker-list button strong { font-size: .9rem; font-weight: 600; color: var(--tx-111827); }
        .person-picker-list button:hover, .person-picker-list button.is-active { background: var(--bg-f3f4f6); }
        .person-picker-list button.is-free { border-top: 1px solid var(--bd-f1f2f4); border-radius: 0 0 .5rem .5rem; color: var(--brand-text); }
        .person-picker-list button.is-free i { align-self: center; }
        .person-picker-list p { margin: 0; padding: .55rem .65rem; font-size: .85rem; color: var(--tx-6b7280); }
    </style>
    <script>
        // Search a member by name or NIF (accents and case ignored), of the wanted gender; the chosen one is kept.
        // With "free" (a name field), a name that is no member's can be typed too.
        function personPicker({ selected, want, free }) {
            const plain = text => (text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
            const people = window.memberPeople || [];
            return {
                selected,
                free: free || '',
                canFree: free !== null,
                term: '',
                open: false,
                active: 0,
                get current() {
                    const person = people.find(person => person.id === this.selected);
                    if (person) return { ...person, member: true };
                    return this.free ? { name: this.free, member: false } : null;
                },
                get canTypeName() { return this.canFree && this.term.trim().length > 1; },
                // The gender wanted; for the spouse, the other one than the form's ("gender" of memberForm).
                get wanted() { return want === 'spouse' ? ({ M: 'F', F: 'M' }[this.gender] || null) : want; },
                get matches() {
                    const words = plain(this.term).split(/\s+/).filter(Boolean);
                    const gender = this.wanted;
                    return people
                        .filter(person => !gender || !person.gender || person.gender === gender)
                        .filter(person => words.every(word => plain(person.name).includes(word) || (person.document || '').includes(word)))
                        .slice(0, 30);
                },
                move(step) { this.active = Math.max(0, Math.min(this.matches.length - (this.canTypeName ? 0 : 1), this.active + step)); },
                useName() {
                    this.selected = '';
                    this.free = this.term.trim();
                    this.term = '';
                    this.open = false;
                },
                pick(person) {
                    if (!person) return this.canTypeName ? this.useName() : null;
                    this.selected = person.id;
                    this.free = '';
                    this.term = '';
                    this.open = false;
                },
                clear() {
                    this.selected = '';
                    this.free = '';
                    this.$nextTick(() => this.$refs.term?.focus());
                },
            };
        }
    </script>
@endonce
