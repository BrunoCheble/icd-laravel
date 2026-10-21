<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Temporary password') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .tp-box { display: grid; gap: 1rem; max-width: 36rem; }
        .tp-password { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1rem; border: 2px dashed var(--brand-200); border-radius: .8rem; background: var(--brand-soft); }
        .tp-password code { font-size: 1.6rem; font-weight: 800; letter-spacing: .08em; color: var(--tx-111827); }
        .tp-facts { display: grid; gap: .35rem; margin: 0; font-size: .92rem; color: var(--tx-4b5563); }
        .tp-facts strong { color: var(--tx-111827); }
        .tp-message { margin: 0; padding: .8rem .9rem; border-radius: .7rem; background: var(--bg-f3f4f6); font-size: .85rem; color: var(--tx-374151); white-space: pre-line; overflow-wrap: anywhere; }
        .tp-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .m-btn.is-whatsapp { border-color: #25d366; background: #25d366; color: #fff; }
        .m-btn.is-whatsapp:hover { background: #1ebe5a; }
    </style>

    <div class="m-page" x-data="{ copied: '' }">
        <a href="{{ route('users.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Users') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ $user->name }}</h1>
                <p>{{ __('Send it now: it is shown only this once.') }}</p>
            </div>
        </div>

        <section class="m-card m-section tp-box">
            <div class="tp-password">
                <code>{{ $password }}</code>
                <button type="button" class="m-btn" @click="navigator.clipboard?.writeText(@js($password)); copied = 'password'; setTimeout(() => copied = '', 2000)">
                    <i class="fa-solid" :class="copied === 'password' ? 'fa-check' : 'fa-copy'"></i> <span class="m-hide-sm">{{ __('Copy') }}</span>
                </button>
            </div>
            <dl class="tp-facts">
                <div><strong>{{ __('Signs in with') }}:</strong> {{ $login }}</div>
                <div><strong>{{ __('Valid until') }}:</strong> {{ $expires?->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}</div>
            </dl>

            <p class="tp-message">{{ $message }}</p>

            <div class="tp-actions">
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="m-btn is-whatsapp"><i class="fa-brands fa-whatsapp"></i> {{ __('Send by WhatsApp') }}</a>
                @endif
                <button type="button" class="m-btn" @click="navigator.clipboard?.writeText(@js($message)); copied = 'message'; setTimeout(() => copied = '', 2000)">
                    <i class="fa-solid" :class="copied === 'message' ? 'fa-check' : 'fa-copy'"></i> {{ __('Copy message') }}
                </button>
                <a href="{{ route('users.index') }}" class="m-btn">{{ __('Done') }}</a>
            </div>
            @unless ($whatsapp)
                <p class="m-help">{{ __('Without a mobile number: copy the message and send it another way.') }}</p>
            @endunless
        </section>
    </div>
</x-app-layout>
