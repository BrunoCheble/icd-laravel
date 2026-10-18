<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import schedule') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('layouts.alert')

                @if ($import = session('import'))
                    {{-- What the last import did --}}
                    <div class="mb-6 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-900">
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

                <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Import schedule') }}</h1>
                <p class="mt-2 text-sm text-gray-700">{{ __('Paste a schedule shared as text (e.g. from LouveApp). The setlist is created (or updated, for the same title and day) with the songs in order and their keys; songs not here yet are pre-registered without a chord sheet.') }}</p>

                <form method="POST" action="{{ route('setlists.import.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <textarea name="text" rows="18" required class="block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm" placeholder="Culto de domingo&#10;Domingo, 18 de outubro de 2026 · 08:00&#10;&#10;Músicas&#10;1. Cristo (Ao Vivo) — Som Do Reino&#10;   Tom: G · BPM: 120&#10;   Vídeo: https://youtu.be/…&#10;   Cifra: https://www.cifraclub.com.br/…">{{ old('text') }}</textarea>
                    <x-input-error :messages="$errors->get('text')" />
                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Import') }}</x-primary-button>
                        <a href="{{ route('setlists.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
