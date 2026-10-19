{{-- The reception's page to register the visitors of a service, one after the other. $remembered (who registers,
     and the details shared by a family, kept for the next visitor), $genders, $groups, $invitedBy, $today --}}
@php $who = \App\Enums\InvitedBy::WHO; @endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Visitors registration') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { margin: 0; min-height: 100vh; background: linear-gradient(180deg, #fef3e2 0, #f4f5f7 16rem); font-family: Figtree, ui-sans-serif, system-ui, sans-serif; color: #111827; }
        .vr { width: min(36rem, 100%); margin: 0 auto; padding: 1rem 1rem 3rem; box-sizing: border-box; }
        .vr-top { display: flex; align-items: center; gap: .75rem; margin: .25rem 0 1rem; }
        .vr-top img { width: 3rem; height: 3rem; padding: .3rem; border-radius: .9rem; background: #fff; box-shadow: 0 4px 14px rgba(0, 0, 0, .08); }
        .vr-top h1 { flex: 1; margin: 0; font-size: 1.3rem; font-weight: 800; }
        .vr-top h1 small { display: block; font-size: .82rem; font-weight: 600; color: #6b7280; }
        .vr-today { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .75rem; border-radius: 999px; background: #fff; color: #92400e; font-size: .85rem; font-weight: 800; text-decoration: none; box-shadow: 0 1px 2px rgba(0, 0, 0, .06); }
        .vr-done { display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; padding: .8rem 1rem; border-radius: .9rem; background: #dcfce7; color: #166534; font-weight: 700; }
        .vr-card { padding: 1.15rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .vr-by { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; padding: .55rem .75rem; border-radius: .7rem; background: #f9fafb; font-size: .88rem; color: #4b5563; }
        .vr-by strong { color: #111827; }
        .vr-by button { margin-left: auto; border: 0; background: none; color: #b45309; font: inherit; font-weight: 700; cursor: pointer; }
        .vr-field { margin-bottom: 1rem; }
        .vr-field > label, .vr-field > .label { display: block; margin-bottom: .4rem; font-size: .88rem; font-weight: 700; color: #374151; }
        .vr-field > label span, .vr-field > .label span { font-weight: 500; color: #9ca3af; }
        .vr-input { width: 100%; box-sizing: border-box; min-height: 50px; padding: .6rem .85rem; border: 1px solid #d1d5db; border-radius: .8rem; background: #fff; font: inherit; font-size: 16px; color: #111827; }
        .vr-input:focus { outline: none; border-color: #f8a925; box-shadow: 0 0 0 4px #fdeccd; }
        .vr-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .75rem; }
        @media (max-width: 479px) { .vr-row { grid-template-columns: 1fr; gap: 0; } }
        /* Choices as buttons: one tap */
        .vr-choices { display: flex; flex-wrap: wrap; gap: .4rem; }
        .vr-choices label { position: relative; }
        .vr-choices input { position: absolute; opacity: 0; pointer-events: none; }
        .vr-choices span { display: inline-flex; align-items: center; gap: .4rem; min-height: 42px; padding: 0 .9rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #fff; color: #4b5563; font-size: .9rem; font-weight: 700; cursor: pointer; }
        .vr-choices span i { color: #9ca3af; }
        .vr-choices input:checked + span { border-color: #f8a925; background: #fef3e2; color: #92400e; }
        .vr-choices input:checked + span i { color: #f8a925; }
        .vr-choices input:focus-visible + span { outline: 2px solid #f8a925; }
        .vr-choices.is-wide label { flex: 1; }
        .vr-choices.is-wide span { justify-content: center; width: 100%; box-sizing: border-box; }
        .vr-error { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: #dc2626; }
        .vr-btn { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 54px; border: 0; border-radius: .9rem; background: #f8a925; color: #fff; font: inherit; font-size: 1.05rem; font-weight: 800; cursor: pointer; box-shadow: 0 8px 20px rgba(248, 169, 37, .3); }
        .vr-btn:hover { background: #d8881c; }
        .vr-btn:disabled { opacity: .7; cursor: default; }
        .vr-family { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-top: 1rem; font-size: .82rem; color: #6b7280; }
        .vr-family button { flex: none; padding: .45rem .8rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #fff; color: #374151; font: inherit; font-weight: 700; cursor: pointer; }
        .vr-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
    </style>
</head>
<body>
    <main class="vr" x-data="{ sending: false, editingBy: @js(! $remembered['created_by'] || $errors->has('created_by')), how: @js(old('invited_by_other', $remembered['invited_by_other'])) }">
        <div class="vr-top">
            <img src="{{ asset('icons/icon-192.png') }}" alt="">
            <h1>{{ __('Visitors registration') }} <small>{{ now()->locale('pt')->isoFormat('dddd, D [de] MMMM') }}</small></h1>
            <a href="{{ route('site.todayVisitor') }}" class="vr-today" title="{{ __('Today\'s visitors') }}"><i class="fa-solid fa-user-group"></i> {{ $today }}</a>
        </div>

        @if (session('registered'))
            <div class="vr-done"><i class="fa-solid fa-circle-check"></i> {{ __(':name registered. Welcome!', ['name' => session('registered')]) }}</div>
        @endif

        <form method="POST" action="{{ route('visitor.register') }}" class="vr-card" @submit="sending = true">
            @csrf
            {{-- Left empty by people: a filled one is a bot --}}
            <div class="vr-hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>

            {{-- Who registers: asked once, then kept --}}
            <div class="vr-by" x-show="!editingBy">
                <i class="fa-solid fa-user-pen"></i> {{ __('Registered by') }}: <strong>{{ $remembered['created_by'] }}</strong>
                <button type="button" @click="editingBy = true; $nextTick(() => $refs.by.focus())">{{ __('Change') }}</button>
            </div>
            <div class="vr-field" x-show="editingBy" x-cloak>
                <label for="created_by">{{ __('Registered by') }}</label>
                <input id="created_by" name="created_by" x-ref="by" class="vr-input" value="{{ old('created_by', $remembered['created_by']) }}" required autocomplete="name" placeholder="{{ __('Your name') }}">
                @error('created_by')<p class="vr-error">{{ $message }}</p>@enderror
            </div>

            <div class="vr-field">
                <label for="name">{{ __('Visitor\'s name') }}</label>
                <input id="name" name="name" class="vr-input" value="{{ old('name') }}" required maxlength="255" autocomplete="off" @if ($remembered['created_by']) autofocus @endif>
                @error('name')<p class="vr-error">{{ $message }}</p>@enderror
            </div>

            <div class="vr-field">
                <span class="label">{{ __('Gender') }}</span>
                <div class="vr-choices is-wide" role="radiogroup" aria-label="{{ __('Gender') }}">
                    @foreach ($genders as $value => $label)
                        <label><input type="radio" name="gender" value="{{ $value }}" @checked(old('gender') === $value) required><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                @error('gender')<p class="vr-error">{{ $message }}</p>@enderror
            </div>

            <div class="vr-field">
                <span class="label">{{ __('Group') }} <span>({{ __('optional') }})</span></span>
                <div class="vr-choices" role="radiogroup" aria-label="{{ __('Group') }}">
                    @foreach ($groups as $value => $label)
                        <label><input type="radio" name="group" value="{{ $value }}" @checked(old('group') === $value)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                @error('group')<p class="vr-error">{{ $message }}</p>@enderror
            </div>

            <div class="vr-field">
                <span class="label">{{ __('How did they hear of the church?') }}</span>
                <div class="vr-choices" role="radiogroup" aria-label="{{ __('How did they hear of the church?') }}">
                    @foreach ($invitedBy as $value => $label)
                        <label><input type="radio" name="invited_by_other" value="{{ $value }}" x-model="how"><span>@if ($value === $who)<i class="fa-solid fa-user"></i> {{ __('Someone invited') }}@else{{ $label }}@endif</span></label>
                    @endforeach
                </div>
                <input name="invited_by" class="vr-input" style="margin-top: .5rem;" x-show="how === @js($who)" :disabled="how !== @js($who)" value="{{ old('invited_by', $remembered['invited_by']) }}" placeholder="{{ __('Who invited?') }}" autocomplete="off">
                @error('invited_by')<p class="vr-error">{{ $message }}</p>@enderror
            </div>

            <div class="vr-row">
                <div class="vr-field">
                    <label for="phone_number">{{ __('Mobile number') }} <span>({{ __('optional') }})</span></label>
                    <input id="phone_number" name="phone_number" type="tel" class="vr-input" value="{{ old('phone_number', $remembered['phone_number']) }}" inputmode="tel" autocomplete="off" placeholder="912 345 678">
                    @error('phone_number')<p class="vr-error">{{ $message }}</p>@enderror
                </div>
                <div class="vr-field">
                    <label for="city">{{ __('City') }} <span>({{ __('optional') }})</span></label>
                    <input id="city" name="city" class="vr-input" value="{{ old('city', $remembered['city']) }}" autocomplete="off">
                    @error('city')<p class="vr-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit" class="vr-btn" :disabled="sending"><i class="fa-solid" :class="sending ? 'fa-spinner fa-spin' : 'fa-user-plus'"></i> {{ __('Register visitor') }}</button>
        </form>

        {{-- The family's details stay for the next visitor; a new family starts clean --}}
        @if ($remembered['phone_number'] || $remembered['city'] || $remembered['invited_by'])
            <form method="POST" action="{{ route('site.visitor.new-family') }}" class="vr-family">
                @csrf
                <span>{{ __('Phone, city and who invited are kept for the next person of the same family.') }}</span>
                <button type="submit"><i class="fa-solid fa-broom"></i> {{ __('New family') }}</button>
            </form>
        @endif
    </main>
</body>
</html>
