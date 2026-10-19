<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        /* Greeting: kept on phones too (other pages hide their title there) */
        .today-head h1 { display: block !important; }
        @media (max-width: 639px) { .today-head h1 { font-size: 1.15rem; } .today-head p { font-size: .82rem; } }

        /* Shortcuts to each section, with what is left to send */
        .today-chips { display: flex; gap: .4rem; margin: -.25rem 0 1rem; overflow-x: auto; scrollbar-width: none; }
        .today-chips::-webkit-scrollbar { display: none; }
        .today-chips a { flex: none; display: inline-flex; align-items: center; gap: .45rem; min-height: 38px; padding: 0 .85rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #fff; color: #374151; font-size: .85rem; font-weight: 700; text-decoration: none; }
        .today-chips a:hover { border-color: #d1d5db; }
        .today-chips i { color: var(--brand-hover); }
        .today-chips b { min-width: 1.3rem; padding: 0 .35rem; border-radius: 999px; background: var(--brand); color: #fff; font-size: .72rem; line-height: 1.3rem; text-align: center; }
        .today-chips b.is-done { background: #dcfce7; color: #166534; }

        /* Cards flow in columns: one on phones and tablets, two on larger screens */
        .today-grid { columns: 2 24rem; column-gap: 1rem; }
        .today-grid > * { break-inside: avoid; margin-bottom: 1rem; scroll-margin-top: 5rem; }
        .today-title { display: flex; align-items: center; gap: .5rem; margin: 0; padding: .9rem 1rem .55rem; font-size: .95rem; font-weight: 800; color: #111827; }
        .today-title i { flex: none; width: 1.9rem; height: 1.9rem; display: grid; place-items: center; border-radius: .55rem; background: var(--brand-soft); color: var(--brand-hover); font-size: .85rem; }
        .today-title small { margin-left: auto; font-size: .78rem; font-weight: 700; color: #9ca3af; white-space: nowrap; }
        .today-title small.is-done { color: #16a34a; }
        /* Sent messages go to the end of their section */
        .today-section { display: flex; flex-direction: column; }
        .today-section .today-item.is-sent { order: 1; }

        .today-item { display: flex; gap: .75rem; padding: .8rem 1rem; border-top: 1px solid #f1f2f4; }
        .today-item.is-sent { background: #fafafa; }
        .today-item.is-sent .today-faces, .today-item.is-sent .today-text { opacity: .6; }
        .today-faces { flex: none; display: flex; align-self: flex-start; }
        .today-faces > * { box-shadow: 0 0 0 2px #fff; }
        .today-faces > * + * { margin-left: -.9rem; }
        .today-face { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; background: #f3f4f6; }
        .today-body { flex: 1; min-width: 0; }
        .today-line { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .5rem; }
        .today-text { flex: 1 1 0; min-width: 0; }
        .today-text strong { display: block; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .today-text strong a { color: inherit; text-decoration: none; }
        .today-text strong a:hover { color: var(--brand-text); }
        .today-text small { display: block; margin-top: .05rem; font-size: .8rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .today-actions { flex: none; display: flex; align-items: center; justify-content: flex-end; gap: .35rem; margin-left: auto; }
        /* A couple: a button for each, under the names */
        .today-actions.is-many { flex: 1 0 100%; }
        .today-actions .m-btn { min-height: 38px; }
        .m-btn.is-whatsapp { background: #25d366; border-color: #25d366; color: #fff; }
        .m-btn.is-whatsapp:hover { background: #1ebe5b; border-color: #1ebe5b; }
        .today-sent { display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 700; color: #16a34a; white-space: nowrap; }
        .today-sent small { font-weight: 600; color: #6b7280; }
        /* The message: two lines, tap to edit it */
        .today-preview { margin: .45rem 0 0; padding: .45rem .65rem; border-radius: .55rem; background: #f0fdf4; color: #374151; font-size: .84rem; line-height: 1.4; cursor: text; }
        .today-preview span { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: pre-line; }
        .today-edit { margin-top: .45rem; }
        .today-edit textarea { font-size: .88rem; }
        /* Phones and tablets: a sent message's state goes under the name */
        @media (max-width: 1023px) { .today-item.is-sent .today-actions { flex: 1 0 100%; flex-wrap: wrap; justify-content: flex-start; } }
        /* Phones: a couple's buttons go under the names, side by side */
        @media (max-width: 639px) {
            .today-item { padding: .75rem .85rem; gap: .65rem; }
            .today-face, .today-faces .m-initials { width: 2.3rem; height: 2.3rem; }
            .today-actions.is-many .m-btn.is-whatsapp { flex: 1; }
        }

        .today-pager { order: 2; display: flex; align-items: center; justify-content: center; gap: .75rem; padding: .6rem 1rem; border-top: 1px solid #f1f2f4; font-size: .85rem; font-weight: 600; color: #6b7280; }
        .today-pager .m-btn { min-height: 36px; width: 36px; }

        .today-search { display: block; margin: 0 0 1rem; max-width: 28rem; }
        .today-nothing { margin: -.5rem 0 1rem; }

        .today-done { padding: 2.5rem 1rem; text-align: center; }
        .today-done i { display: block; margin-bottom: .6rem; font-size: 2rem; color: #16a34a; }
        .today-done strong { display: block; font-size: 1.05rem; color: #111827; }
        .today-done small { color: #6b7280; }

        .today-next { display: flex; align-items: center; gap: .75rem; padding: .6rem 1rem; border-top: 1px solid #f1f2f4; }
        .today-next-day { flex: none; width: 2.75rem; padding: .2rem 0; border: 1px solid #e5e7eb; border-radius: .6rem; text-align: center; line-height: 1.1; }
        .today-next-day strong { display: block; font-size: 1rem; font-weight: 800; color: #111827; }
        .today-next-day small { display: block; font-size: .62rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; }
        .today-next > i { flex: none; width: 1rem; color: #9ca3af; text-align: center; }
    </style>

    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good morning') : ($hour < 19 ? __('Good afternoon') : __('Good evening'));
        // Name => icon, title, short name (the shortcuts), items.
        $sections = collect([
            'birthdays' => ['fa-cake-candles', __('Birthdays today'), __('Birthdays'), $birthdays],
            'weddings' => ['fa-heart', __('Wedding anniversaries today'), __('Weddings'), $weddings],
            'welcomes' => ['fa-hand', __('Visitors to welcome'), __('Visitors'), $welcomes],
            'reminders' => ['fa-circle-exclamation', __('Missing details to ask for'), __('Missing details'), $reminders],
        ])->filter(fn ($section) => $section[3]->isNotEmpty());
        $all = $sections->flatMap(fn ($section) => $section[3]);
        $toSend = $all->whereNull('sent')->count();
        $perPage = 5;
        $weekday = fn ($date) => rtrim($date->locale(app()->getLocale())->isoFormat('ddd'), '.');
    @endphp

    <div class="m-page" x-data="todayMessages(@js(['toSend' => $toSend, 'left' => $sections->map(fn ($section) => $section[3]->whereNull('sent')->count()), 'store' => route('dashboard.sent.store'), 'destroy' => route('dashboard.sent.destroy'), 'names' => $all->pluck('title')->concat($upcoming->pluck('title'))->values()]))">
        @include('layouts.alert')

        <div class="m-head today-head">
            <div>
                <h1>{{ $greeting }}, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h1>
                <p>
                    {{ \Illuminate\Support\Str::ucfirst($today->locale(app()->getLocale())->isoFormat('dddd, D [de] MMMM')) }}
                    · <span x-text="toSend === 0 ? @js(__('Everything sent')) : (toSend === 1 ? @js(__('1 message to send')) : @js(__(':count messages to send', ['count' => '#'])).replace('#', toSend))"></span>
                </p>
            </div>
        </div>

        {{-- Shortcuts: each section with what is left to send --}}
        @if ($sections->isNotEmpty())
            <nav class="today-chips" aria-label="{{ __('Sections') }}">
                @foreach ($sections as $name => [$icon, $title, $short, $items])
                    <a href="#today-{{ $name }}"><i class="fa-solid {{ $icon }}"></i> {{ $short }} <b :class="{ 'is-done': left['{{ $name }}'] === 0 }" x-text="left['{{ $name }}'] === 0 ? '✓' : left['{{ $name }}']"></b></a>
                @endforeach
                <a href="#today-next"><i class="fa-solid fa-calendar-days"></i> {{ __('Next days') }}</a>
            </nav>

            {{-- Find someone by name, in every section --}}
            <label class="m-search today-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" x-model="search" placeholder="{{ __('Search by name') }}" aria-label="{{ __('Search by name') }}" @keydown.escape="search = ''">
            </label>
            <p class="m-muted today-nothing" x-show="search.trim() && !found()" x-cloak>{{ __('Nobody with this name today.') }}</p>
        @endif

        <div class="today-grid">
            @if ($all->isEmpty())
                <div class="m-card today-done">
                    <i class="fa-solid fa-circle-check"></i>
                    <strong>{{ __('No messages for today.') }}</strong>
                    <small>{{ __('No birthdays, wedding anniversaries, new visitors or missing details today.') }}</small>
                </div>
            @endif

            @foreach ($sections as $name => [$icon, $title, $short, $items])
                <section id="today-{{ $name }}" class="m-card m-list today-section" x-data="{ section: @js($name), total: {{ $items->count() }}, page: 0, perPage: {{ $perPage }}, pages: {{ (int) ceil($items->count() / $perPage) }}, names: @js($items->pluck('title')) }" x-show="!search.trim() || names.some(name => matches(name))">
                    <h3 class="today-title">
                        <i class="fa-solid {{ $icon }}"></i> {{ $title }}
                        <small :class="{ 'is-done': left[section] === 0 }" x-text="left[section] === 0 ? '✓ ' + @js(__('All sent')) : (total - left[section]) + '/' + total + ' ' + @js(__('sent'))"></small>
                    </h3>
                    @foreach ($items as $item)
                        @php($withPhone = collect($item['people'])->whereNotNull('whatsapp'))
                        <div class="today-item" x-data="todayMessage(@js($item))" :class="{ 'is-sent': sent }" x-show="search.trim() ? matches(item.title) : Math.floor({{ $loop->index }} / perPage) === page">
                            <span class="today-faces">
                                @foreach ($item['people'] as $person)
                                    @if ($person['photo'])
                                        <img class="today-face" src="{{ $person['photo'] }}" alt="{{ $person['name'] }}" loading="lazy">
                                    @else
                                        <span class="m-initials is-lg">{{ $person['initials'] }}</span>
                                    @endif
                                @endforeach
                            </span>
                            <div class="today-body">
                                <div class="today-line">
                                    <span class="today-text">
                                        <strong>
                                            @if (count($item['people']) === 1)
                                                <a href="{{ $item['people'][0]['url'] }}">{{ $item['title'] }}</a>
                                            @else
                                                {{ $item['title'] }}
                                            @endif
                                        </strong>
                                        <small>{{ $item['subtitle'] }}</small>
                                    </span>
                                    <div @class(['today-actions', 'is-many' => $withPhone->count() > 1])>
                                        <template x-if="sent">
                                            <span class="today-sent"><i class="fa-solid fa-check"></i> {{ __('Sent') }} <small x-text="sentLabel()"></small></span>
                                        </template>
                                        {{-- After a reminder: did the member answer? --}}
                                        <span x-show="sent && item.progress === 'completed'" x-cloak class="m-tag is-ok"><i class="fa-solid fa-circle-check"></i> {{ __('Completed') }}</span>
                                        <span x-show="sent && item.kind === 'pending' && item.progress !== 'completed'" x-cloak class="m-tag is-part"><i class="fa-solid fa-hourglass-half"></i> {{ __('Waiting') }}</span>
                                        <template x-if="sent && sent.today !== false">
                                            <button type="button" class="m-btn is-ghost" @click="mark(false)">{{ __('Undo') }}</button>
                                        </template>
                                        @if ($withPhone->isNotEmpty())
                                            <button type="button" x-show="!sent" class="m-btn is-ghost is-icon" @click="editing ? editing = false : edit()" :aria-pressed="editing" title="{{ __('Edit message') }}" aria-label="{{ __('Edit message') }}"><i class="fa-solid fa-pen"></i></button>
                                        @endif
                                        @foreach ($item['people'] as $i => $person)
                                            @if ($person['whatsapp'])
                                                <a x-show="!sent" href="{{ $person['whatsapp'] }}?text={{ rawurlencode($item['text']) }}" :href="whatsappUrl({{ $i }})" target="_blank" rel="noopener" class="m-btn is-whatsapp" @click="mark(true)">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                    {{ $withPhone->count() > 1 ? $person['name'] : __('Send') }}
                                                </a>
                                            @endif
                                        @endforeach
                                        @if ($withPhone->isEmpty())
                                            <button type="button" x-show="!sent" class="m-btn" @click="mark(true)" title="{{ __('No phone number: mark as done after greeting in person.') }}"><i class="fa-solid fa-check"></i> {{ __('Mark as done') }}</button>
                                        @endif
                                    </div>
                                </div>
                                @if ($withPhone->isNotEmpty())
                                    <p class="today-preview" x-show="!sent && !editing" @click="edit()" title="{{ __('Edit message') }}"><span x-text="text">{{ $item['text'] }}</span></p>
                                    <div class="today-edit" x-show="!sent && editing" x-cloak>
                                        <textarea class="m-input" rows="4" x-model="text" x-ref="text"></textarea>
                                    </div>
                                @else
                                    <p class="m-muted" x-show="!sent" style="margin: .3rem 0 0; font-size: .8rem;"><i class="fa-solid fa-phone-slash"></i> {{ __('No phone number: mark as done after greeting in person.') }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    {{-- Pages of the section --}}
                    @if ($items->count() > $perPage)
                        <div class="today-pager" x-show="!search.trim()">
                            <button type="button" class="m-btn is-icon" @click="page--" :disabled="page === 0" aria-label="{{ __('Previous') }}"><i class="fa-solid fa-chevron-left"></i></button>
                            <span x-text="(page * perPage + 1) + '–' + Math.min((page + 1) * perPage, total) + ' ' + @js(__('of')) + ' ' + total"></span>
                            <button type="button" class="m-btn is-icon" @click="page++" :disabled="page === pages - 1" aria-label="{{ __('Next') }}"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    @endif
                </section>
            @endforeach

            {{-- What comes next --}}
            <section id="today-next" class="m-card m-list" x-data="{ names: @js($upcoming->pluck('title')) }" x-show="!search.trim() || names.some(name => matches(name))">
                <h3 class="today-title"><i class="fa-solid fa-calendar-days"></i> {{ __('Next :count days', ['count' => \App\Modules\Dashboard\Services\GetTodayMessagesService::UPCOMING_DAYS]) }} <small>{{ $upcoming->count() }}</small></h3>
                @forelse ($upcoming as $entry)
                    <div class="today-next" x-show="matches(@js($entry['title']))">
                        <span class="today-next-day"><strong>{{ $entry['date']->day }}</strong><small>{{ $weekday($entry['date']) }}</small></span>
                        <i class="fa-solid {{ $entry['kind'] === 'wedding' ? 'fa-heart' : 'fa-cake-candles' }}"></i>
                        <span class="today-text">
                            <strong>{{ $entry['title'] }}</strong>
                            <small>
                                @if ($entry['kind'] === 'wedding')
                                    {{ $entry['years'] ? trans_choice(':count year married|:count years married', $entry['years'], ['count' => $entry['years']]) : __('Wedding anniversary') }}
                                @else
                                    {{ $entry['years'] ? __('Turns :count', ['count' => $entry['years']]) : __('Birthday') }}
                                @endif
                            </small>
                        </span>
                    </div>
                @empty
                    <div class="m-empty"><i class="fa-solid fa-calendar"></i>{{ __('Nothing in the next days.') }}</div>
                @endforelse
                <div class="m-pages"><a href="{{ route('report.anniversaries.index') }}" class="m-link">{{ __('See the whole month') }} →</a></div>
            </section>
        </div>
    </div>

    <script>
        // What is left to send: on the page and per section (the shortcuts and the sections' counts).
        // The name search: accents and case do not matter.
        function todayMessages({ toSend, left, store, destroy, names }) {
            const plain = text => (text || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            return {
                toSend, left, store, destroy, names,
                search: '',
                matches(name) { return plain(name).includes(plain(this.search)); },
                found() { return this.names.some(name => this.matches(name)); },
            };
        }

        // One message: its text (editable before sending) and whether it was sent; marking it tells the server and
        // the counts. The WhatsApp link opens on its own; the mark goes along.
        function todayMessage(item) {
            return {
                item,
                text: item.text,
                sent: item.sent,
                editing: false,
                whatsappUrl(i) { return `${this.item.people[i].whatsapp}?text=${encodeURIComponent(this.text)}`; },
                edit() { this.editing = true; this.$nextTick(() => this.$refs.text?.focus()); },
                sentLabel() { return [this.sent?.by, this.sent?.at].filter(Boolean).join(' · '); },
                // What is left to send, on the page and in the section.
                count(change) { this.toSend += change; this.left[this.section] += change; },
                async mark(sent) {
                    if (!!this.sent === sent) return;
                    const before = this.sent;
                    this.sent = sent ? { by: null, at: null } : null;
                    this.editing = false;
                    this.count(sent ? -1 : 1);
                    try {
                        const response = await fetch(sent ? this.store : this.destroy, {
                            method: sent ? 'POST' : 'DELETE',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ kind: this.item.kind, recipient_id: this.item.recipientId }),
                        });
                        if (!response.ok) throw new Error(response.status);
                        this.sent = (await response.json()).sent;
                    } catch (e) {
                        this.sent = before;
                        this.count(sent ? 1 : -1);
                        alert(@js(__('Something went wrong')));
                    }
                },
            };
        }
    </script>
</x-app-layout>
