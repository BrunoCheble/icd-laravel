<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Setlists') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('layouts.alert')
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Setlists') }}</h1>
                            <p class="mt-2 text-sm text-gray-700">{{ __('A list of all the setlists.') }}</p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                            <a type="button" href="{{ route('setlists.create') }}"
                                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ __('Add new') }}</a>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('setlists.index') }}" class="mt-6 flex items-center gap-4 max-w-xl">
                        <x-text-input name="search" type="search" class="block w-full" :value="$search" :placeholder="__('Search by title or minister...')" />
                        <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
                        @if ($search)
                            <a href="{{ route('setlists.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Clear') }}</a>
                        @endif
                    </form>

                    <div class="flow-root">
                        <div class="mt-8 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table class="w-full divide-y divide-gray-300">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Title') }}</th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Ministers') }}</th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Event Date') }}</th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Songs') }}</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @forelse ($setlists as $setlist)
                                            <tr class="even:bg-gray-50">
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-semibold text-gray-900">{{ $setlist->title }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $setlist->ministers }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $setlist->event_date?->format('d/m/Y') }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $setlist->songs_count }}</td>
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                                                    <form action="{{ route('setlists.destroy', $setlist) }}" method="POST">
                                                        <a href="{{ route('setlists.show', $setlist) }}"
                                                            class="text-gray-600 font-bold hover:text-gray-900 mr-2">{{ __('Show') }}</a>
                                                        <a href="{{ route('setlists.edit', $setlist) }}"
                                                            class="text-indigo-600 font-bold hover:text-indigo-900 mr-2">{{ __('Edit') }}</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="{{ route('setlists.destroy', $setlist) }}"
                                                            class="text-red-600 font-bold hover:text-red-900"
                                                            onclick="event.preventDefault(); confirm(@js(__('Are you sure to delete?'))) ? this.closest('form').submit() : false;">{{ __('Delete') }}</a>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-4 text-sm text-gray-500">{{ __('No setlists found.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="mt-4 px-4">
                                    {!! $setlists->withQueryString()->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
