{{-- The public page to leave a prayer request: name, request and, optionally, a mobile number. In Portuguese, or
     in English with "lang=en". --}}
@php
    $lang = app()->getLocale();
    // Topics a request often starts with (a tap writes it at the start of the request).
    $topics = [
        ['fa-heart-pulse', __('Health')], ['fa-people-roof', __('Family')], ['fa-briefcase', __('Work')],
        ['fa-hands-praying', __('Thanksgiving')], ['fa-route', __('Guidance')], ['fa-dove', __('Peace')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Prayer request') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { margin: 0; min-height: 100vh; background: #f4f5f7; font-family: Figtree, ui-sans-serif, system-ui, sans-serif; color: #111827; }
        /* The top: the church's picture, darkened, with the title and a verse */
        .pr-hero { position: relative; padding: 1rem 1rem 5.5rem; color: #fff; text-align: center; background: linear-gradient(180deg, rgba(17, 24, 39, .55), rgba(17, 24, 39, .75)), url("{{ asset('img/bg-prayer.jpg') }}") center / cover; }
        .pr-lang { display: flex; justify-content: flex-end; gap: .25rem; max-width: 34rem; margin: 0 auto; }
        .pr-lang a { padding: .3rem .65rem; border-radius: 999px; color: rgba(255, 255, 255, .8); font-size: .8rem; font-weight: 700; text-decoration: none; }
        .pr-lang a.is-active { background: rgba(255, 255, 255, .95); color: #111827; }
        .pr-logo { display: block; width: 4rem; height: 4rem; margin: .5rem auto .9rem; padding: .35rem; border-radius: 1.1rem; background: #fff; box-shadow: 0 6px 20px rgba(0, 0, 0, .25); }
        .pr-hero h1 { margin: 0; font-size: 1.7rem; font-weight: 800; letter-spacing: -.01em; }
        .pr-hero .verse { max-width: 26rem; margin: .6rem auto 0; font-size: .95rem; line-height: 1.5; opacity: .92; font-style: italic; }
        .pr-hero .verse small { display: block; margin-top: .25rem; font-style: normal; font-weight: 700; opacity: .8; }

        .pr { width: min(34rem, 100%); margin: -4.25rem auto 0; padding: 0 1rem 3rem; box-sizing: border-box; position: relative; }
        .pr-card { padding: 1.25rem; border-radius: 1.1rem; background: #fff; box-shadow: 0 12px 30px rgba(16, 24, 40, .12); }
        .pr-field { margin-bottom: 1.1rem; }
        .pr-field label, .pr-field .label { display: block; margin-bottom: .4rem; font-size: .88rem; font-weight: 700; color: #374151; }
        .pr-field label span { font-weight: 500; color: #9ca3af; }
        .pr-field small { display: block; margin-top: .3rem; font-size: .8rem; color: #6b7280; }
        .pr-input { width: 100%; box-sizing: border-box; min-height: 50px; padding: .6rem .85rem; border: 1px solid #d1d5db; border-radius: .8rem; background: #fff; font: inherit; font-size: 16px; color: #111827; }
        textarea.pr-input { min-height: 8.5rem; line-height: 1.45; resize: vertical; }
        .pr-input:focus { outline: none; border-color: #f8a925; box-shadow: 0 0 0 4px #fdeccd; }
        .pr-count { display: flex; justify-content: flex-end; margin-top: .25rem; font-size: .75rem; color: #9ca3af; }
        .pr-count.is-near { color: #b45309; font-weight: 700; }
        .pr-topics { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .6rem; }
        .pr-topics button { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .7rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #fff; color: #4b5563; font: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; }
        .pr-topics button i { color: #f8a925; }
        .pr-topics button.is-on { border-color: #f8a925; background: #fef3e2; color: #92400e; }
        .pr-remember { display: flex; align-items: center; gap: .5rem; margin: -.2rem 0 1rem; font-size: .85rem; color: #4b5563; cursor: pointer; }
        .pr-remember input { width: 1.05rem; height: 1.05rem; accent-color: #f8a925; }
        .pr-error { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: #dc2626; }
        .pr-btn { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 54px; border: 0; border-radius: .9rem; background: #f8a925; color: #fff; font: inherit; font-size: 1.05rem; font-weight: 800; text-decoration: none; cursor: pointer; box-shadow: 0 8px 20px rgba(248, 169, 37, .3); }
        .pr-btn:hover { background: #d8881c; }
        .pr-btn:disabled { opacity: .7; cursor: default; }
        .pr-btn.is-light { background: #fff; color: #92400e; border: 1px solid #fde68a; box-shadow: none; }
        .pr-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
        .pr-done { padding: 2rem 1.25rem 1.5rem; text-align: center; }
        .pr-done .icon { width: 4rem; height: 4rem; margin: 0 auto 1rem; display: grid; place-items: center; border-radius: 999px; background: #fef3e2; color: #f8a925; font-size: 1.8rem; }
        .pr-done strong { display: block; font-size: 1.25rem; }
        .pr-done p { margin: .45rem 0 0; color: #4b5563; line-height: 1.5; }
        .pr-done blockquote { margin: 1.25rem 0; padding: .85rem 1rem; border-left: 3px solid #f8a925; border-radius: .5rem; background: #fffbeb; color: #78350f; font-style: italic; text-align: left; line-height: 1.5; }
        .pr-done blockquote small { display: block; margin-top: .3rem; font-style: normal; font-weight: 700; }
        .pr-foot { margin-top: 1rem; text-align: center; font-size: .8rem; line-height: 1.45; color: #9ca3af; }
    </style>
</head>
<body>
    <header class="pr-hero">
        {{-- Language --}}
        <nav class="pr-lang" aria-label="Language">
            <a href="{{ route('site.prayer') }}" @class(['is-active' => $lang === 'pt']) lang="pt" hreflang="pt">PT</a>
            <a href="{{ route('site.prayer', ['lang' => 'en']) }}" @class(['is-active' => $lang === 'en']) lang="en" hreflang="en">EN</a>
        </nav>
        <img class="pr-logo" src="{{ asset('icons/icon-192.png') }}" alt="">
        <h1>{{ __('Prayer request') }}</h1>
        <p class="verse">“{{ __('Do not be anxious about anything, but in every situation, by prayer and petition, present your requests to God.') }}” <small>{{ __('Philippians 4:6') }}</small></p>
    </header>

    <main class="pr">
        @if (session('sent'))
            <div class="pr-card pr-done">
                <div class="icon"><i class="fa-solid fa-hands-praying"></i></div>
                <strong>{{ __('We received your request.') }}</strong>
                <p>{{ __('We will pray for you. God bless you!') }}</p>
                <blockquote>“{{ __('The prayer of a righteous person is powerful and effective.') }}” <small>{{ __('James 5:16') }}</small></blockquote>
                <a href="{{ route('site.prayer', $lang === 'pt' ? [] : ['lang' => $lang]) }}" class="pr-btn is-light"><i class="fa-solid fa-plus"></i> {{ __('Send another request') }}</a>
            </div>
        @else
            <form method="POST" action="{{ route('prayer.register') }}" class="pr-card" x-data="prayerForm(@js(['request' => old('request', '')]))" x-init="restore()" @submit="send()">
                @csrf
                <input type="hidden" name="lang" value="{{ $lang }}">
                {{-- Left empty by people: a filled one is a bot --}}
                <div class="pr-hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="pr-field">
                    <label for="name">{{ __('Name') }}</label>
                    <input id="name" name="name" class="pr-input" x-model="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                    @error('name')<p class="pr-error">{{ $message }}</p>@enderror
                </div>

                <div class="pr-field">
                    <span class="label">{{ __('Request') }}</span>
                    <div class="pr-topics" role="group" aria-label="{{ __('Topics') }}">
                        @foreach ($topics as [$icon, $topic])
                            <button type="button" :class="{ 'is-on': hasTopic(@js($topic)) }" @click="toggleTopic(@js($topic))"><i class="fa-solid {{ $icon }}"></i> {{ $topic }}</button>
                        @endforeach
                    </div>
                    <textarea id="request" name="request" class="pr-input" x-model="request" x-ref="request" maxlength="2000" placeholder="{{ __('What would you like us to pray for?') }}" aria-label="{{ __('Request') }}"></textarea>
                    <div class="pr-count" :class="{ 'is-near': request.length > 1800 }" x-text="`${request.length} / 2000`"></div>
                    @error('request')<p class="pr-error">{{ $message }}</p>@enderror
                </div>

                <div class="pr-field">
                    <label for="phone_number">{{ __('Mobile number') }} <span>({{ __('optional') }})</span></label>
                    <input id="phone_number" name="phone_number" type="tel" class="pr-input" x-model="phone" value="{{ old('phone_number') }}" inputmode="tel" autocomplete="tel" placeholder="912 345 678">
                    <small>{{ __('Only if you would like a word from the church.') }}</small>
                    @error('phone_number')<p class="pr-error">{{ $message }}</p>@enderror
                </div>

                <label class="pr-remember"><input type="checkbox" x-model="remember"> {{ __('Remember my name and number on this device') }}</label>

                <button type="submit" class="pr-btn" :disabled="sending">
                    <i class="fa-solid" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                    <span x-text="sending ? @js(__('Sending…')) : @js(__('Send request'))">{{ __('Send request') }}</span>
                </button>
            </form>
            {{-- The name and the request may be shown in the church's prayer time (/today-prayers); the number never --}}
            <p class="pr-foot"><i class="fa-solid fa-lock"></i> {{ __('Your name and request may be read in the church\'s prayer time; your mobile number is seen only by the team.') }}</p>
        @endif
    </main>

    <script>
        // The form: topics written at the start of the request, a characters count, sending once, and the name and
        // number remembered on this device when asked (only here, in the browser).
        function prayerForm({ request }) {
            const KEY = 'icd-prayer-person';
            return {
                name: '', phone: '', request, remember: false, sending: false,
                restore() {
                    // The values the server sent back (after an error) come first.
                    this.name = this.$el.querySelector('#name').value;
                    this.phone = this.$el.querySelector('#phone_number').value;
                    try {
                        const saved = JSON.parse(localStorage.getItem(KEY) || 'null');
                        if (saved) {
                            this.remember = true;
                            this.name ||= saved.name || '';
                            this.phone ||= saved.phone || '';
                        }
                    } catch (e) { /* no storage here: nothing remembered */ }
                },
                hasTopic(topic) { return this.request.startsWith(topic + ':') || this.request.includes('\n' + topic + ':'); },
                toggleTopic(topic) {
                    const line = topic + ': ';
                    if (this.hasTopic(topic)) {
                        this.request = this.request.split('\n').filter(text => !text.startsWith(topic + ':')).join('\n');
                    } else {
                        this.request = (this.request.trim() ? this.request.replace(/\s*$/, '\n') : '') + line;
                    }
                    this.$nextTick(() => { const area = this.$refs.request; area.focus(); area.setSelectionRange(area.value.length, area.value.length); });
                },
                send() {
                    this.sending = true;
                    try {
                        if (this.remember) localStorage.setItem(KEY, JSON.stringify({ name: this.name, phone: this.phone }));
                        else localStorage.removeItem(KEY);
                    } catch (e) { /* no storage here */ }
                },
            };
        }
    </script>
</body>
</html>
