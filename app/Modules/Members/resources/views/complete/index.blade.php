{{-- The page a pending member opens from the link sent by WhatsApp: only the fields still missing, nothing of what
     is already known (the link could be forwarded). $member (null: invalid or expired link), $fields --}}
@php
    $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
    $done = $member && empty($fields);
    $lang = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- The link carries its signature: never send it to other sites, never index it --}}
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Complete your registration') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    <style>
        body { margin: 0; min-height: 100vh; background: linear-gradient(180deg, #fef3e2 0, #f4f5f7 18rem); font-family: Figtree, ui-sans-serif, system-ui, sans-serif; color: #111827; }
        .cr { width: min(34rem, 100%); margin: 0 auto; padding: 1.5rem 1rem 3rem; box-sizing: border-box; }
        .cr-logo { display: block; width: 4.25rem; height: 4.25rem; margin: .5rem auto 1rem; padding: .4rem; border-radius: 1.2rem; background: #fff; box-shadow: 0 6px 20px rgba(0, 0, 0, .08); }
        .cr h1 { margin: 0; text-align: center; font-size: 1.5rem; font-weight: 800; letter-spacing: -.01em; }
        .cr .lead { margin: .4rem 0 1.25rem; text-align: center; color: #4b5563; line-height: 1.45; }
        .cr-card { padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .cr-field { margin-bottom: 1rem; }
        .cr-field:last-of-type { margin-bottom: 0; }
        .cr-field label { display: block; margin-bottom: .35rem; font-size: .88rem; font-weight: 700; color: #374151; }
        .cr-field small { display: block; margin-top: .3rem; font-size: .8rem; color: #6b7280; }
        .cr-input { width: 100%; box-sizing: border-box; min-height: 50px; padding: .6rem .85rem; border: 1px solid #d1d5db; border-radius: .8rem; background: #fff; font-size: 16px; color: #111827; }
        .cr-input:focus { outline: none; border-color: #f8a925; box-shadow: 0 0 0 4px #fdeccd; }
        .cr-row { display: grid; grid-template-columns: 9rem minmax(0, 1fr); gap: .6rem; }
        .cr-error { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: #dc2626; }
        .cr-alert { margin-bottom: 1rem; padding: .8rem 1rem; border-radius: .8rem; font-size: .92rem; line-height: 1.4; }
        .cr-alert.is-ok { background: #dcfce7; color: #166534; }
        .cr-alert.is-error { background: #fef2f2; color: #991b1b; }
        .cr-btn { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 54px; margin-top: 1.25rem; border: 0; border-radius: .9rem; background: #f8a925; color: #fff; font-size: 1.05rem; font-weight: 800; cursor: pointer; box-shadow: 0 8px 20px rgba(248, 169, 37, .3); }
        .cr-btn:hover { background: #d8881c; }
        .cr-state { padding: 2rem 1.25rem; text-align: center; }
        .cr-state i { display: block; margin-bottom: .75rem; font-size: 2.5rem; }
        .cr-state strong { display: block; font-size: 1.15rem; }
        .cr-state p { margin: .4rem 0 0; color: #4b5563; line-height: 1.45; }
        .cr-lang { display: flex; justify-content: flex-end; gap: .25rem; }
        .cr-lang a { padding: .3rem .65rem; border-radius: 999px; color: #6b7280; font-size: .8rem; font-weight: 700; text-decoration: none; }
        .cr-lang a.is-active { background: #fff; color: #111827; box-shadow: 0 1px 2px rgba(0, 0, 0, .1); }
        .cr-foot { margin-top: 1rem; text-align: center; font-size: .8rem; color: #9ca3af; }
    </style>
</head>
<body>
    <main class="cr">
        {{-- Language: the signature does not cover "lang", so the link keeps working --}}
        <nav class="cr-lang" aria-label="Language">
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'pt']) }}" @class(['is-active' => $lang === 'pt']) lang="pt" hreflang="pt">PT</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" @class(['is-active' => $lang === 'en']) lang="en" hreflang="en">EN</a>
        </nav>
        <img class="cr-logo" src="{{ asset('icons/icon-192.png') }}" alt="">

        @if (! $member)
            {{-- Forged, broken or expired link --}}
            <div class="cr-card cr-state">
                <i class="fa-solid fa-link-slash" style="color: #9ca3af;"></i>
                <strong>{{ __('This link is no longer valid.') }}</strong>
                <p>{{ __('Ask the church for a new link.') }}</p>
            </div>
        @elseif ($done)
            <div class="cr-card cr-state">
                <i class="fa-solid fa-circle-check" style="color: #16a34a;"></i>
                <strong>{{ __('Thank you, :name!', ['name' => $member->first_name]) }}</strong>
                <p>{{ __('Your registration has everything it needs. God bless you!') }}</p>
            </div>
        @else
            <h1>{{ __('Hello, :name!', ['name' => $member->first_name]) }} 👋</h1>
            <p class="lead">{{ __('To complete your registration as a member of the church, we only need a few more details.') }}</p>

            @if (session('success'))
                <div class="cr-alert is-ok"><i class="fa-solid fa-check"></i> {{ __('Thank you! We received your details. Only these are still missing:') }}</div>
            @endif
            @error('form')<div class="cr-alert is-error">{{ $message }}</div>@enderror

            <form method="POST" action="{{ route('members.complete.update', ['member' => $member->id] + request()->except('lang')) }}" class="cr-card">
                @csrf
                <input type="hidden" name="lang" value="{{ $lang }}">
                @foreach ($fields as $field)
                    @switch($field)
                        @case('spouse')
                            <div class="cr-field">
                                <label for="spouse_name">{{ $member->gender === 'M' ? __('Full name of your wife') : __('Full name of your husband') }}</label>
                                <input id="spouse_name" name="spouse_name" class="cr-input" value="{{ old('spouse_name') }}" autocomplete="off">
                                @error('spouse_name')<p class="cr-error">{{ $message }}</p>@enderror
                            </div>
                            @break
                        @case('date_joined')
                            <div class="cr-field">
                                <label for="date_joined">{{ __('Wedding Date') }}</label>
                                <input id="date_joined" name="date_joined" type="date" class="cr-input" value="{{ old('date_joined') }}" max="{{ now()->toDateString() }}">
                                @error('date_joined')<p class="cr-error">{{ $message }}</p>@enderror
                            </div>
                            @break
                        @case('email')
                            <div class="cr-field">
                                <label for="email">{{ __('Email') }}</label>
                                <input id="email" name="email" type="email" class="cr-input" value="{{ old('email') }}" autocomplete="email" inputmode="email">
                                @error('email')<p class="cr-error">{{ $message }}</p>@enderror
                            </div>
                            @break
                        @case('phone_number')
                            <div class="cr-field">
                                <label for="phone_number">{{ __('Mobile number') }}</label>
                                <input id="phone_number" name="phone_number" type="tel" class="cr-input" value="{{ old('phone_number') }}" autocomplete="tel" inputmode="tel" placeholder="912 345 678">
                                @error('phone_number')<p class="cr-error">{{ $message }}</p>@enderror
                            </div>
                            @break
                        @case('address')
                            <div class="cr-field">
                                <label for="address">{{ __('Address') }}</label>
                                <input id="address" name="address" class="cr-input" value="{{ old('address') }}" autocomplete="street-address" placeholder="{{ __('Street, number, floor') }}">
                                @error('address')<p class="cr-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cr-field cr-row">
                                <div>
                                    <label for="zip_code">{{ __('Zip Code') }}</label>
                                    <input id="zip_code" name="zip_code" class="cr-input" value="{{ old('zip_code') }}" autocomplete="postal-code" inputmode="numeric" placeholder="1234-567">
                                </div>
                                <div>
                                    <label for="city">{{ __('City') }}</label>
                                    <input id="city" name="city" class="cr-input" value="{{ old('city') }}" autocomplete="address-level2">
                                </div>
                            </div>
                            @error('zip_code')<p class="cr-error">{{ $message }}</p>@enderror
                            @error('city')<p class="cr-error">{{ $message }}</p>@enderror
                            @break
                        @case('date_of_birth')
                            <div class="cr-field">
                                <label for="date_of_birth">{{ __('Date of Birth') }}</label>
                                <input id="date_of_birth" name="date_of_birth" type="date" class="cr-input" value="{{ old('date_of_birth') }}" max="{{ now()->subDay()->toDateString() }}">
                                @error('date_of_birth')<p class="cr-error">{{ $message }}</p>@enderror
                            </div>
                            @break
                        @case('document_number')
                            <div class="cr-field">
                                <label for="document_number">NIF</label>
                                <input id="document_number" name="document_number" class="cr-input" value="{{ old('document_number') }}" inputmode="numeric" maxlength="11" autocomplete="off">
                                <small>{{ __('To link your offerings and tithes.') }}</small>
                                @error('document_number')<p class="cr-error">{{ $message }}</p>@enderror
                            </div>
                            @break
                    @endswitch
                @endforeach
                <button type="submit" class="cr-btn"><i class="fa-solid fa-paper-plane"></i> {{ __('Send') }}</button>
            </form>
            <p class="cr-foot"><i class="fa-solid fa-lock"></i> {{ __('This link is personal; please do not share it.') }}</p>
        @endif
    </main>
</body>
</html>
