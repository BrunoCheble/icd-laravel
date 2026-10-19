<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .today-grid { display: grid; gap: 1rem; align-items: start; }
        @media (min-width: 1280px) { .today-grid { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); } }
        .today-main { display: grid; gap: 1rem; }
        .today-title { display: flex; align-items: center; gap: .5rem; margin: 0; padding: 1rem 1.1rem .6rem; font-size: .95rem; font-weight: 800; color: #111827; }
        .today-title i { width: 1.9rem; height: 1.9rem; display: grid; place-items: center; border-radius: .55rem; background: var(--brand-soft); color: var(--brand-hover); font-size: .85rem; }
        .today-title small { margin-left: auto; font-size: .78rem; font-weight: 700; color: #9ca3af; white-space: nowrap; }
        /* Sent messages go to the end of their section */
        .today-section { display: flex; flex-direction: column; }
        .today-section .today-item.is-sent { order: 1; }
        .today-title small.is-done { color: #16a34a; }

        .today-item { padding: .8rem 1.1rem; border-top: 1px solid #f1f2f4; }
        .today-item.is-sent { background: #fafafa; }
        .today-item.is-sent .today-who { opacity: .6; }
        .today-row { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem .85rem; }
        .today-who { flex: 1 1 14rem; min-width: 0; display: flex; align-items: center; gap: .7rem; }
        .today-faces { flex: none; display: flex; }
        .today-faces > * { box-shadow: 0 0 0 2px #fff; }
        .today-faces > * + * { margin-left: -.6rem; }
        .today-face { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; background: #f3f4f6; }
        .today-text { min-width: 0; }
        .today-text strong { display: block; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .today-text strong a { color: inherit; text-decoration: none; }
        .today-text strong a:hover { color: var(--brand-text); }
        .today-text small { display: block; margin-top: .05rem; font-size: .82rem; color: #6b7280; }
        .today-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .4rem; margin-left: auto; }
        .m-btn.is-whatsapp { background: #25d366; border-color: #25d366; color: #fff; }
        .m-btn.is-whatsapp:hover { background: #1ebe5b; border-color: #1ebe5b; }
        .today-sent { display: inline-flex; align-items: center; gap: .4rem; font-size: .82rem; font-weight: 700; color: #16a34a; }
        .today-sent small { font-weight: 600; color: #6b7280; }
        .today-message { margin-top: .6rem; }
        .today-preview { margin: .45rem 0 0; padding: .55rem .75rem; border-radius: .6rem; background: #f0fdf4; color: #374151; font-size: .85rem; line-height: 1.4; white-space: pre-line; }
        @media (max-width: 639px) {
            .today-actions { width: 100%; }
            .today-actions .m-btn.is-whatsapp { flex: 1; }
        }

        .today-done { padding: 2.5rem 1rem; text-align: center; }
        .today-done i { display: block; margin-bottom: .6rem; font-size: 2rem; color: #16a34a; }
        .today-done strong { display: block; font-size: 1.05rem; color: #111827; }
        .today-done small { color: #6b7280; }

        .today-next { display: flex; align-items: center; gap: .75rem; padding: .6rem 1.1rem; border-top: 1px solid #f1f2f4; }
        .today-next-day { flex: none; width: 3rem; padding: .25rem 0; border: 1px solid #e5e7eb; border-radius: .6rem; text-align: center; line-height: 1.1; }
        .today-next-day strong { display: block; font-size: 1rem; font-weight: 800; color: #111827; }
        .today-next-day small { display: block; font-size: .65rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; }
        .today-next i { color: #9ca3af; }
    </style>

    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good morning') : ($hour < 19 ? __('Good afternoon') : __('Good evening'));
        $sections = [
            'birthdays' => ['fa-cake-candles', __('Birthdays today'), $birthdays],
            'weddings' => ['fa-heart', __('Wedding anniversaries today'), $weddings],
            'welcomes' => ['fa-hand', __('Visitors to welcome'), $welcomes],
            'reminders' => ['fa-circle-exclamation', __('Pending members to remind'), $reminders],
        ];
        $all = collect([$birthdays, $weddings, $welcomes, $reminders])->flatten(1);
        $toSend = $all->whereNull('sent')->count();
        $weekday = fn ($date) => rtrim($date->locale(app()->getLocale())->isoFormat('ddd'), '.');
    @endphp

    <div class="m-page" x-data="todayMessages(@js(['toSend' => $toSend, 'store' => route('dashboard.sent.store'), 'destroy' => route('dashboard.sent.destroy')]))">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ $greeting }}, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h1>
                <p>
                    {{ \Illuminate\Support\Str::ucfirst($today->locale(app()->getLocale())->isoFormat('dddd, D [de] MMMM')) }}
                    · <span x-text="toSend === 0 ? @js(__('Everything sent')) : (toSend === 1 ? @js(__('1 message to send')) : @js(__(':count messages to send', ['count' => '#'])).replace('#', toSend))"></span>
                </p>
            </div>
        </div>

        <div class="today-grid">
            <div class="today-main">
                @if ($all->isEmpty())
                    <div class="m-card today-done">
                        <i class="fa-solid fa-circle-check"></i>
                        <strong>{{ __('No messages for today.') }}</strong>
                        <small>{{ __('No birthdays, wedding anniversaries, new visitors or pending members today.') }}</small>
                    </div>
                @endif

                @foreach ($sections as $name => [$icon, $title, $items])
                    @continue($items->isEmpty())
                    <section class="m-card m-list today-section" x-data="{ total: {{ $items->count() }}, sent: {{ $items->whereNotNull('sent')->count() }} }" @message-sent="sent += $event.detail">
                        <h3 class="today-title">
                            <i class="fa-solid {{ $icon }}"></i> {{ $title }}
                            <small :class="{ 'is-done': sent === total }" x-text="sent === total ? '✓ ' + @js(__('All sent')) : sent + '/' + total + ' ' + @js(__('sent'))"></small>
                        </h3>
                        @foreach ($items as $item)
                            <div class="today-item" x-data="todayMessage(@js($item))" :class="{ 'is-sent': sent }">
                                <div class="today-row">
                                    <div class="today-who">
                                        <span class="today-faces">
                                            @foreach ($item['people'] as $person)
                                                @if ($person['photo'])
                                                    <img class="today-face" src="{{ $person['photo'] }}" alt="{{ $person['name'] }}" loading="lazy">
                                                @else
                                                    <span class="m-initials is-lg">{{ $person['initials'] }}</span>
                                                @endif
                                            @endforeach
                                        </span>
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
                                    </div>
                                    <div class="today-actions">
                                        <template x-if="sent">
                                            <span class="today-sent"><i class="fa-solid fa-check"></i> {{ __('Sent') }} <small x-text="sentLabel()"></small></span>
                                        </template>
                                        <template x-if="sent">
                                            <button type="button" class="m-btn is-ghost" @click="mark(false)">{{ __('Undo') }}</button>
                                        </template>
                                        <template x-if="!sent">
                                            <button type="button" class="m-btn is-ghost is-icon" @click="editing = !editing" :aria-pressed="editing" title="{{ __('Edit message') }}" aria-label="{{ __('Edit message') }}"><i class="fa-solid fa-pen"></i></button>
                                        </template>
                                        @foreach ($item['people'] as $i => $person)
                                            @if ($person['whatsapp'])
                                                <a x-show="!sent" href="{{ $person['whatsapp'] }}?text={{ rawurlencode($item['text']) }}" :href="whatsappUrl({{ $i }})" target="_blank" rel="noopener" class="m-btn is-whatsapp" @click="mark(true)">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                    {{ count($item['people']) > 1 ? __('Send to :name', ['name' => $person['name']]) : __('Send') }}
                                                </a>
                                            @endif
                                        @endforeach
                                        @if (collect($item['people'])->whereNotNull('whatsapp')->isEmpty())
                                            <button type="button" x-show="!sent" class="m-btn" @click="mark(true)" title="{{ __('No phone number: mark as done after greeting in person.') }}"><i class="fa-solid fa-check"></i> {{ __('No phone · Mark as done') }}</button>
                                        @endif
                                    </div>
                                </div>
                                <div class="today-message" x-show="!sent">
                                    <p class="today-preview" x-show="!editing" x-text="text">{{ $item['text'] }}</p>
                                    <textarea x-show="editing" x-cloak class="m-input" rows="4" x-model="text"></textarea>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </div>

            {{-- What comes next --}}
            <section class="m-card m-list">
                <h3 class="today-title"><i class="fa-solid fa-calendar-days"></i> {{ __('Next :count days', ['count' => \App\Modules\Dashboard\Services\GetTodayMessagesService::UPCOMING_DAYS]) }} <small>{{ $upcoming->count() }}</small></h3>
                @forelse ($upcoming as $entry)
                    <div class="today-next">
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
        // The page's count of messages still to send.
        function todayMessages({ toSend, store, destroy }) {
            return { toSend, store, destroy };
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
                sentLabel() { return [this.sent?.by, this.sent?.at].filter(Boolean).join(' · '); },
                async mark(sent) {
                    if (!!this.sent === sent) return;
                    const before = this.sent;
                    this.sent = sent ? { by: null, at: null } : null;
                    this.editing = false;
                    this.$dispatch('message-sent', sent ? 1 : -1);
                    this.toSend += sent ? -1 : 1;
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
                        this.$dispatch('message-sent', sent ? -1 : 1);
                        this.toSend += sent ? 1 : -1;
                        alert(@js(__('Something went wrong')));
                    }
                },
            };
        }
    </script>
</x-app-layout>
