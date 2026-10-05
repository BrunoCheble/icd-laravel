<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $song->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Show Song') }}</h1>
                            <p class="mt-2 text-sm text-gray-700">{{ __('Details of the song.') }}</p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex sm:flex-none sm:gap-4">
                            <a type="button" href="{{ route('songs.timing.edit', $song) }}" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50">{{ __('Section times') }}</a>
                            <a type="button" href="{{ route('songs.edit', $song) }}" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50">{{ __('Edit') }}</a>
                            <a type="button" href="{{ route('songs.index') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-100">
                        <dl class="divide-y divide-gray-100">
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Title') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $song->title }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Artist') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $song->artist }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Key') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $song->musical_key ?? '—' }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('YouTube') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 break-all">
                                    @if ($song->youtube_url)
                                        <a href="{{ $song->youtube_url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">{{ $song->youtube_url }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Source') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 break-all">
                                    @if ($song->source_url)
                                        <a href="{{ $song->source_url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">{{ $song->source_url }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Video Lessons') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                                    @forelse ($song->video_lesson ?? [] as $lesson)
                                        <div class="py-1">
                                            <span class="font-semibold">{{ $instrumentOptions[$lesson['instrument']] ?? $lesson['instrument'] }}</span> —
                                            <a href="{{ $lesson['link'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-900">{{ $lesson['title'] }}</a>
                                        </div>
                                    @empty
                                        —
                                    @endforelse
                                </dd>
                            </div>
                            @if ($lyricsBlocks = $song->lyricsBlocks())
                                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Lyrics') }}</dt>
                                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 space-y-4">
                                        @foreach ($lyricsBlocks as $block)
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $block['name'] }}</div>
                                                <div style="white-space: pre-line;">{{ $block['lyrics'] }}</div>
                                            </div>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                            @if (! empty($song->chord_sheet['sections']))
                                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Chord sheet') }}</dt>
                                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 space-y-4">
                                        @foreach ($song->chord_sheet['sections'] as $section)
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    {{ $section['label'] ?? $section['section'] ?? '' }}
                                                </div>
                                                <div style="white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">{{ implode("\n", $section['lines'] ?? []) }}</div>
                                            </div>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                            <div class="px-4 py-6 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Structure') }}</dt>
                                <dd class="mt-2">
                                    <pre class="overflow-x-auto rounded-md bg-gray-50 border border-gray-200 p-4 text-sm text-gray-800" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">{{ $song->structureAsJson() }}</pre>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
