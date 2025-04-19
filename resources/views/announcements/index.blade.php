<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Wallets') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Announcements') }}</h1>
                            <p class="mt-2 text-sm text-gray-700">A list of all the {{ __('Announcements') }}.</p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                            <a type="button" href="{{ route('announcements.create') }}"
                                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ __('Add new') }}</a>
                        </div>
                    </div>

                    <div class="flow-root">
                        <div class="mt-8 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table class="w-full divide-y divide-gray-300">
                                    <thead>
                                        <tr>

                                            <th scope="col"
                                                class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Member</th>
                                            <th scope="col"
                                                class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Type</th>
                                            <th scope="col"
                                                class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Title</th>
                                            <th scope="col"
                                                class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Created At</th>
                                            <th scope="col"
                                                class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Approved At</th>
                                            <th scope="col"
                                                class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Expires At</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($announcements as $i => $announcement)
                                            <tr class="even:bg-gray-50">

                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $announcement->member->first_and_last_name }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $announcement->type }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $announcement->title }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $announcement->created_at->format('d-m-Y') }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $announcement->is_approved ? $announcement->approved_at : 'Pending' }}</td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $announcement->expires_at }}</td>
                                                <td
                                                    class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                                                    <form
                                                        action="{{ route('announcements.destroy', $announcement->id) }}"
                                                        method="POST">
                                                        <a href="{{ route('announcements.edit', $announcement->id) }}"
                                                            class="text-indigo-600 font-bold hover:text-indigo-900  mr-2">{{ __('Edit') }}</a>
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="{{ route('announcements.destroy', $announcement->id) }}"
                                                            class="text-red-600 font-bold hover:text-red-900"
                                                            onclick="event.preventDefault(); confirm('Are you sure to delete?') ? this.closest('form').submit() : false;">{{ __('Delete') }}</a>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="mt-4 px-4">
                                    {!! $announcements->withQueryString()->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
