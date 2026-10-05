<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Songs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('layouts.alert')
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Songs') }}</h1>
                            <p class="mt-2 text-sm text-gray-700">{{ __('A list of all the songs.') }}</p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex sm:flex-none sm:gap-4">
                            <a type="button" href="{{ route('songs.bookmarklet') }}"
                                class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-700 shadow-sm border border-gray-300 hover:bg-gray-50">{{ __('Import from a chord site') }}</a>
                            <a type="button" href="{{ route('songs.create') }}"
                                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ __('Add new') }}</a>
                        </div>
                    </div>

                    <div class="flow-root">
                        <div class="mt-8 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table class="w-full divide-y divide-gray-300">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Title') }}</th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Artist') }}</th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Key') }}</th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Video Lessons') }}</th>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Updated At') }}</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($songs as $song)
                                            <tr class="even:bg-gray-50">
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-semibold text-gray-900">{{ $song->title }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $song->artist }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $song->musical_key }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ count($song->video_lesson ?? []) }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $song->updated_at?->format('d/m/Y H:i') }}</td>
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                                                    <form action="{{ route('songs.destroy', $song) }}" method="POST">
                                                        <a href="{{ route('songs.show', $song) }}"
                                                            class="text-gray-600 font-bold hover:text-gray-900 mr-2">{{ __('Show') }}</a>
                                                        <a href="{{ route('songs.edit', $song) }}"
                                                            class="text-indigo-600 font-bold hover:text-indigo-900 mr-2">{{ __('Edit') }}</a>
                                                        <a href="{{ route('songs.timing.edit', $song) }}"
                                                            class="text-gray-600 font-bold hover:text-gray-900 mr-2">{{ __('Times') }}</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="{{ route('songs.destroy', $song) }}"
                                                            class="text-red-600 font-bold hover:text-red-900"
                                                            onclick="event.preventDefault(); confirm(@js(__('Are you sure to delete?'))) ? this.closest('form').submit() : false;">{{ __('Delete') }}</a>
                                                    </form>
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
