<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $setlist->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ $setlist->title }}</h1>
                            <p class="mt-2 text-sm text-gray-700">{{ __('Date') }}: {{ $setlist->event_date?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex sm:flex-none sm:gap-4">
                            <a type="button" href="{{ route('site.setlist.show', $setlist) }}" target="_blank" rel="noopener" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50">{{ __('Public page') }}</a>
                            <a type="button" href="{{ route('setlists.edit', $setlist) }}" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50">{{ __('Edit') }}</a>
                            <a type="button" href="{{ route('setlists.index') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Songs') }}</h3>

                        @if ($setlist->songs->isEmpty())
                            <p class="mt-3 text-sm text-gray-500">{{ __('No songs in this setlist yet.') }}</p>
                        @else
                            <ol class="mt-3 space-y-2">
                                @foreach ($setlist->songs as $song)
                                    <li class="text-sm text-gray-700">
                                        {{ $loop->iteration }}.
                                        <a href="{{ route('songs.show', $song) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">{{ $song->title }}</a>
                                        — {{ $song->artist }}
                                        @if ($song->pivot->musical_key ?? $song->musical_key)
                                            <span class="text-gray-500">({{ $song->pivot->musical_key ?? $song->musical_key }})</span>
                                        @endif
                                        @if ($song->pivot->minister_name)
                                            <span class="text-gray-500">· {{ __('Minister') }}: {{ $song->pivot->minister_name }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
