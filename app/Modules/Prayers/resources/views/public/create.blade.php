{{-- The public page to leave a prayer request: name, request and, optionally, a mobile number --}}
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Prayer request') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    <style>
        body { margin: 0; min-height: 100vh; background: linear-gradient(180deg, #fef3e2 0, #f4f5f7 18rem); font-family: Figtree, ui-sans-serif, system-ui, sans-serif; color: #111827; }
        .pr { width: min(34rem, 100%); margin: 0 auto; padding: 1.5rem 1rem 3rem; box-sizing: border-box; }
        .pr-logo { display: block; width: 4.25rem; height: 4.25rem; margin: .5rem auto 1rem; padding: .4rem; border-radius: 1.2rem; background: #fff; box-shadow: 0 6px 20px rgba(0, 0, 0, .08); }
        .pr h1 { margin: 0; text-align: center; font-size: 1.5rem; font-weight: 800; letter-spacing: -.01em; }
        .pr .lead { margin: .4rem 0 1.25rem; text-align: center; color: #4b5563; line-height: 1.45; }
        .pr-card { padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .pr-field { margin-bottom: 1rem; }
        .pr-field label { display: block; margin-bottom: .35rem; font-size: .88rem; font-weight: 700; color: #374151; }
        .pr-field label span { font-weight: 500; color: #9ca3af; }
        .pr-field small { display: block; margin-top: .3rem; font-size: .8rem; color: #6b7280; }
        .pr-input { width: 100%; box-sizing: border-box; min-height: 50px; padding: .6rem .85rem; border: 1px solid #d1d5db; border-radius: .8rem; background: #fff; font: inherit; font-size: 16px; color: #111827; }
        textarea.pr-input { min-height: 8rem; line-height: 1.45; resize: vertical; }
        .pr-input:focus { outline: none; border-color: #f8a925; box-shadow: 0 0 0 4px #fdeccd; }
        .pr-error { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: #dc2626; }
        .pr-btn { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 54px; margin-top: .25rem; border: 0; border-radius: .9rem; background: #f8a925; color: #fff; font: inherit; font-size: 1.05rem; font-weight: 800; text-decoration: none; cursor: pointer; box-shadow: 0 8px 20px rgba(248, 169, 37, .3); }
        .pr-btn:hover { background: #d8881c; }
        .pr-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
        .pr-done { padding: 2rem 1.25rem; text-align: center; }
        .pr-done i { display: block; margin-bottom: .75rem; font-size: 2.5rem; color: #f8a925; }
        .pr-done strong { display: block; font-size: 1.2rem; }
        .pr-done p { margin: .4rem 0 1.25rem; color: #4b5563; line-height: 1.45; }
        .pr-foot { margin-top: 1rem; text-align: center; font-size: .8rem; color: #9ca3af; }
    </style>
</head>
<body>
    <main class="pr">
        <img class="pr-logo" src="{{ asset('icons/icon-192.png') }}" alt="">

        @if (session('sent'))
            <div class="pr-card pr-done">
                <i class="fa-solid fa-hands-praying"></i>
                <strong>{{ __('We received your request.') }}</strong>
                <p>{{ __('We will pray for you. God bless you!') }}</p>
                <a href="{{ route('site.prayer') }}" class="pr-btn">{{ __('Send another request') }}</a>
            </div>
        @else
            <h1>{{ __('Prayer request') }} 🙏</h1>
            <p class="lead">{{ __('Write your request: the church will pray for you.') }}</p>

            <form method="POST" action="{{ route('prayer.register') }}" class="pr-card">
                @csrf
                {{-- Left empty by people: a filled one is a bot --}}
                <div class="pr-hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="pr-field">
                    <label for="name">{{ __('Name') }}</label>
                    <input id="name" name="name" class="pr-input" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                    @error('name')<p class="pr-error">{{ $message }}</p>@enderror
                </div>
                <div class="pr-field">
                    <label for="request">{{ __('Request') }}</label>
                    <textarea id="request" name="request" class="pr-input" maxlength="2000" placeholder="{{ __('What would you like us to pray for?') }}">{{ old('request') }}</textarea>
                    @error('request')<p class="pr-error">{{ $message }}</p>@enderror
                </div>
                <div class="pr-field">
                    <label for="phone_number">{{ __('Mobile number') }} <span>({{ __('optional') }})</span></label>
                    <input id="phone_number" name="phone_number" type="tel" class="pr-input" value="{{ old('phone_number') }}" inputmode="tel" autocomplete="tel" placeholder="912 345 678">
                    <small>{{ __('Only if you would like a word from the church.') }}</small>
                    @error('phone_number')<p class="pr-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="pr-btn"><i class="fa-solid fa-paper-plane"></i> {{ __('Send request') }}</button>
            </form>
            {{-- The name and the request may be shown in the church's prayer time (/today-prayers); the number never --}}
            <p class="pr-foot"><i class="fa-solid fa-lock"></i> {{ __('Your name and request may be read in the church\'s prayer time; your mobile number is seen only by the team.') }}</p>
        @endif
    </main>
</body>
</html>
