<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Songs') }}
        </h2>
    </x-slot>

    <style>
        [x-cloak] { display: none !important; }
        .song-menu-button { width: 2.25rem; height: 2.25rem; border: 1px solid #d1d5db; border-radius: .375rem; background: #fff; font-size: 1.1rem; font-weight: 700; line-height: 1; color: #374151; }
        .song-menu-button:hover { background: #f3f4f6; }
        .song-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .song-modal-overlay { position: absolute; inset: 0; background: rgba(17, 24, 39, .5); }
        .song-modal-box { position: relative; width: min(24rem, 100%); padding: 1.25rem; border-radius: .75rem; background: #fff; box-shadow: 0 20px 40px rgba(0, 0, 0, .2); }
        .song-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .song-modal-actions { display: grid; gap: .5rem; }
        .song-modal-actions a, .song-modal-actions button { display: block; width: 100%; padding: .65rem .9rem; border: 1px solid #e5e7eb; border-radius: .5rem; background: #fff; font-size: .9rem; font-weight: 600; color: #374151; text-align: left; }
        .song-modal-actions a:hover, .song-modal-actions button:hover { background: #f3f4f6; }
        .song-modal-actions .is-danger { color: #dc2626; }
    </style>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" x-data="{ actions: null, confirmDelete: false, filtersOpen: false }" @keydown.escape.window="actions = null; filtersOpen = false">
                @include('layouts.alert')

                    @php
                        $sort = $filters['sort'] ?? 'title';
                        $direction = $filters['direction'] ?? 'asc';
                        $hasFilters = collect($filters)->except(['sort', 'direction'])->filter()->isNotEmpty();
                        // Header link: sorts by the column, reversing the direction when it is already the sort.
                        $sortUrl = fn (string $column) => route('songs.index', array_merge(request()->except('page'), [
                            'sort' => $column,
                            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
                        ]));
                        $sortArrow = fn (string $column) => new \Illuminate\Support\HtmlString(' <i class="fa-solid ' . ($sort === $column ? ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort" style="opacity: .35;') . '"></i>');
                    @endphp
                    @php
                        $statusFilters = ['reviewed' => __('Reviewed'), 'times' => __('Times'), 'youtube' => 'YouTube'];
                        $sortOptions = [
                            'title' => __('Title'), 'artist' => __('Artist'), 'key' => __('Key'), 'reviewed' => __('Reviewed'),
                            'times' => __('Times'), 'youtube' => 'YouTube', 'lessons' => __('Video Lessons'), 'updated' => __('Updated At'),
                        ];
                        // Active filters shown next to the button, e.g. "Reviewed: Yes".
                        $activeFilters = collect($statusFilters)
                            ->filter(fn ($label, $name) => in_array($filters[$name] ?? null, ['yes', 'no'], true))
                            ->map(fn ($label, $name) => $label . ': ' . ($filters[$name] === 'yes' ? __('Yes') : __('No')));
                        if (($filters['search'] ?? '') !== '') {
                            $activeFilters->prepend(__('Search') . ': "' . $filters['search'] . '"', 'search');
                        }
                    @endphp

                {{-- Actions of the song picked in the list --}}
                <div class="song-modal" x-show="actions" x-cloak role="dialog" aria-modal="true" :aria-label="actions?.title">
                    <div class="song-modal-overlay" @click="actions = null"></div>
                    <div class="song-modal-box">
                        <div class="song-modal-head">
                            <div>
                                <div class="text-base font-semibold text-gray-900" x-text="actions?.title"></div>
                                <div class="text-sm text-gray-500" x-text="actions?.artist"></div>
                            </div>
                            <button type="button" class="song-menu-button" @click="actions = null" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="song-modal-actions" x-show="!confirmDelete">
                            <a :href="actions?.show"><i class="fa-solid fa-eye"></i> {{ __('Show') }}</a>
                            <a :href="actions?.edit"><i class="fa-solid fa-pen"></i> {{ __('Edit') }}</a>
                            <a :href="actions?.layout"><i class="fa-solid fa-layer-group"></i> {{ __('Layout') }}</a>
                            <a :href="actions?.practice"><i class="fa-solid fa-graduation-cap"></i> {{ __('Practice') }}</a>
                            <button type="button" class="is-danger" @click="confirmDelete = true"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                        </div>
                        <form method="POST" :action="actions?.destroy" x-show="confirmDelete" class="song-modal-confirm">
                            @csrf
                            @method('DELETE')
                            <p class="text-sm text-gray-700">{{ __('Are you sure to delete?') }}</p>
                            <div class="mt-4 flex justify-end gap-2">
                                <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50" @click="confirmDelete = false">{{ __('Cancel') }}</button>
                                <button type="submit" class="rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm" style="background: #dc2626;">{{ __('Delete') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Songs') }}</h1>
                            <p class="mt-2 text-sm text-gray-700">{{ __('A list of all the songs.') }}</p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex sm:flex-none sm:gap-4">
                            <button type="button" @click="filtersOpen = true; setTimeout(() => $refs.search.focus(), 50)"
                                class="block rounded-md bg-blue-500 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <i class="fa fa-filter"></i>
                                {{ __('Filter') }}@if ($activeFilters->isNotEmpty()) ({{ $activeFilters->count() }})@endif
                            </button>
                            <a type="button" href="{{ route('songs.bookmarklet') }}"
                                class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50">{{ __('Import from a chord site') }}</a>
                            <a type="button" href="{{ route('songs.create') }}"
                                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ __('Add new') }}</a>
                        </div>
                    </div>



                    {{-- Active filters; they are changed in the Filter modal --}}
                    <div class="mt-6 flex items-center" style="flex-wrap: wrap; gap: .5rem .75rem;">
                        @foreach ($activeFilters as $text)
                            <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background: #eef2ff; color: #4338ca;">{{ $text }}</span>
                        @endforeach
                        @if ($hasFilters)
                            <a href="{{ route('songs.index', ['sort' => $sort, 'direction' => $direction]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">{{ __('Clear Filters') }}</a>
                        @endif
                        <span class="text-sm text-gray-500">{{ trans_choice(':count song|:count songs', $songs->total(), ['count' => $songs->total()]) }}</span>
                    </div>

                    {{-- Filter modal --}}
                    <div class="song-modal" x-show="filtersOpen" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('Filter') }}">
                        <div class="song-modal-overlay" @click="filtersOpen = false"></div>
                        <div class="song-modal-box space-y-6" style="width: min(36rem, 100%); padding: 1.5rem;">
                            <div class="flex justify-between items-center">
                                <h2 class="text-xl font-semibold">{{ __('Filter') }}</h2>
                                <button type="button" class="text-gray-400 hover:text-gray-600" style="font-size: 1.5rem; line-height: 1;" @click="filtersOpen = false" aria-label="{{ __('Close') }}">&times;</button>
                            </div>

                            <form method="GET" action="{{ route('songs.index') }}" class="space-y-4">
                                <div>
                                    <x-input-label for="filter_search" :value="__('Search')" />
                                    <x-text-input id="filter_search" name="search" type="search" x-ref="search" :value="$filters['search'] ?? ''"
                                        :placeholder="__('Title or artist')" class="mt-1 w-full" />
                                </div>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    @foreach ($statusFilters as $name => $label)
                                        <div>
                                            <x-input-label :for="'filter_' . $name" :value="$label" />
                                            <select id="filter_{{ $name }}" name="{{ $name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                                <option value="">{{ __('All') }}</option>
                                                <option value="yes" @selected(($filters[$name] ?? null) === 'yes')>{{ __('Yes') }}</option>
                                                <option value="no" @selected(($filters[$name] ?? null) === 'no')>{{ __('No') }}</option>
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="filter_sort" :value="__('Sort by')" />
                                        <select id="filter_sort" name="sort" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            @foreach ($sortOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="filter_direction" :value="__('Order by')" />
                                        <select id="filter_direction" name="direction" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="asc" @selected($direction === 'asc')>{{ __('Ascending') }}</option>
                                            <option value="desc" @selected($direction === 'desc')>{{ __('Descending') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <a href="{{ route('songs.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4 py-2">{{ __('Clear Filters') }}</a>
                                    <x-primary-button>{{ __('Apply Filters') }}</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="flow-root">
                        <div class="mt-4 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table class="w-full divide-y divide-gray-300">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('title') }}" class="hover:text-gray-900" @if ($sort === 'title') style="color: #111827;" @endif>{{ __('Title') }}{{ $sortArrow('title') }}</a></th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('artist') }}" class="hover:text-gray-900" @if ($sort === 'artist') style="color: #111827;" @endif>{{ __('Artist') }}{{ $sortArrow('artist') }}</a></th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('key') }}" class="hover:text-gray-900" @if ($sort === 'key') style="color: #111827;" @endif>{{ __('Key') }}{{ $sortArrow('key') }}</a></th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('reviewed') }}" class="hover:text-gray-900" @if ($sort === 'reviewed') style="color: #111827;" @endif>{{ __('Reviewed') }}{{ $sortArrow('reviewed') }}</a></th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('times') }}" class="hover:text-gray-900" @if ($sort === 'times') style="color: #111827;" @endif>{{ __('Times') }}{{ $sortArrow('times') }}</a></th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('youtube') }}" class="hover:text-gray-900" @if ($sort === 'youtube') style="color: #111827;" @endif>YouTube{{ $sortArrow('youtube') }}</a></th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('lessons') }}" class="hover:text-gray-900" @if ($sort === 'lessons') style="color: #111827;" @endif>{{ __('Video Lessons') }}{{ $sortArrow('lessons') }}</a></th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><a href="{{ $sortUrl('updated') }}" class="hover:text-gray-900" @if ($sort === 'updated') style="color: #111827;" @endif>{{ __('Updated At') }}{{ $sortArrow('updated') }}</a></th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($songs as $song)
                                            <tr class="even:bg-gray-50">
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-semibold text-gray-900">{{ $song->title }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $song->artist }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $song->musical_key }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                    @if ($song->isReviewed())
                                                        <span class="font-semibold" style="color: #15803d;" title="{{ __('Every block has a start time') }}"><i class="fa-solid fa-check"></i> {{ __('Yes') }}</span>
                                                    @else
                                                        <span style="color: #9ca3af;">—</span>
                                                    @endif
                                                </td>
                                                @php
                                                    [$timed, $sections] = $song->timedSections();
                                                @endphp
                                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                    <a href="{{ route('songs.layout.edit', ['song' => $song, 'mode' => 'time']) }}" class="font-semibold"
                                                        style="color: {{ $sections && $timed === $sections ? '#15803d' : ($timed ? '#b45309' : '#9ca3af') }};"
                                                        title="{{ __(':timed of :total blocks have a start time', ['timed' => $timed, 'total' => $sections]) }}">{{ $sections ? "$timed/$sections" : '—' }}</a>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                    @if ($song->youtube_url)
                                                        <a href="{{ $song->youtube_url }}" target="_blank" rel="noopener" class="font-semibold" style="color: #15803d;"><i class="fa-brands fa-youtube"></i> {{ __('Open') }}</a>
                                                    @else
                                                        <span style="color: #9ca3af;">—</span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ count($song->video_lesson ?? []) }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $song->updated_at?->format('d/m/Y H:i') }}</td>
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-right">
                                                    <button type="button" class="song-menu-button" aria-label="{{ __('Actions') }}" @click="actions = @js([
                                                        'title' => $song->title,
                                                        'artist' => $song->artist,
                                                        'show' => route('songs.show', $song),
                                                        'edit' => route('songs.edit', $song),
                                                        'layout' => route('songs.layout.edit', $song),
                                                        'practice' => route('songs.practice', $song),
                                                        'destroy' => route('songs.destroy', $song),
                                                    ]); confirmDelete = false"><i class="fa-solid fa-ellipsis"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="mt-4 px-4">
                                    {!! $songs->withQueryString()->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
