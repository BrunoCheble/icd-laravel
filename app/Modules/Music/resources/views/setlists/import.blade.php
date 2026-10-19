<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import schedule') }}</h2>
    </x-slot>

    @include('music::partials.ui')

    <div class="m-page is-narrow">
                <a href="{{ route('setlists.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Setlists') }}</a>
                @include('layouts.alert')

                @if ($import = session('import'))
                    {{-- What the last import did --}}
                    <div class="mb-4 rounded-xl border border-green-300 bg-green-50 p-4 text-sm text-green-900">
                        <p class="font-semibold">
                            {{ __('Setlist saved:') }} <a href="{{ $import['setlist']['url'] }}" class="underline">{{ $import['setlist']['title'] }}{{ $import['setlist']['date'] ? ' · ' . $import['setlist']['date'] : '' }}</a>
                        </p>
                        @if ($import['created'])
                            <p class="mt-2">{{ __('Pre-registered (new):') }} {{ implode(', ', $import['created']) }}</p>
                        @endif
                        @if ($import['existing'])
                            <p class="mt-1">{{ __('Already here (left as they are):') }} {{ implode(', ', $import['existing']) }}</p>
                        @endif
                        @if ($import['pending'])
                            <p class="mt-3 font-semibold">{{ __('Without a chord sheet yet: open each one and click "Send to ICD" on its chord sheet.') }}</p>
                            <ul class="mt-1 space-y-1">
                                @foreach ($import['pending'] as $song)
                                    <li>
                                        <a href="{{ $song['layout'] }}" class="font-semibold underline">{{ $song['title'] }}</a>
                                        @if ($song['source'])
                                            · <a href="{{ $song['source'] }}" target="_blank" rel="noopener" class="underline">{{ __('Open the chord sheet') }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                        @else
                                            · <span class="text-gray-600">{{ __('no chord sheet address') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <div class="m-head">
                    <div>
                        <h1>{{ __('Import schedule') }}</h1>
                        <p>{{ __('Paste a schedule shared as text (e.g. from LouveApp). The setlist is created (or updated, for the same title and day) with the songs in order and their keys; songs not here yet are pre-registered without a chord sheet.') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('setlists.import.store') }}" class="m-form">
                    @csrf
                    <section class="m-card m-section">
                    <h2 class="m-section-title"><i class="fa-solid fa-paste"></i> {{ __('Schedule') }}</h2>
                    <textarea name="text" rows="16" required class="m-input is-code" aria-label="{{ __('Schedule') }}" placeholder="Culto de domingo&#10;Domingo, 18 de outubro de 2026 · 08:00&#10;&#10;Músicas&#10;1. Cristo (Ao Vivo) — Som Do Reino&#10;   Tom: G · BPM: 120&#10;   Vídeo: https://youtu.be/…&#10;   Cifra: https://www.cifraclub.com.br/…">{{ old('text') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('text')" />
                    </section>
                    <div class="m-form-actions">
                        <a href="{{ route('setlists.index') }}" class="m-btn is-ghost">{{ __('Cancel') }}</a>
                        <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-file-import"></i> {{ __('Import') }}</button>
                    </div>
                </form>
    </div>
</x-app-layout>
