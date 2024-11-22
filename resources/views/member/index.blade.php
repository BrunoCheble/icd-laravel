<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Members') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Members') }}</h1>
                        </div>

                        @include('member.filter')

                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                            <a
                            type="button" href="{{ route('members.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Add new</a>
                        </div>
                        <!-- button to toggle table or cards view -->

                    </div>

                    <div x-data="{ selectedMember: null, toggleView: 'table' }" class="flow-root">

                        <a x-on:click="toggleView = toggleView === 'table' ? 'card' : 'table'"
                            href="#">
                            <i class="fa-solid fa-table" x-show="toggleView === 'table'"></i>
                            <i class="fa-solid fa-list" x-show="toggleView === 'card'"></i>
                        </a>
                        <div class="mt-8 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table x-show="toggleView === 'table'" class="w-full divide-y divide-gray-300">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"></th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Document</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">City</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Age</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Marital Status</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($members as $member)
                                            @include('member.row')
                                        @endforeach
                                    </tbody>
                                </table>
                                <div x-show="toggleView === 'card'" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                    @foreach($members as $member)
                                        @include('member.card')
                                    @endforeach
                                </div>

                                <div class="mt-4 px-4">
                                    {!! $members->withQueryString()->links() !!}
                                </div>
                            </div>
                        </div>

                        <x-modal name="modal-info" :show="false" focusable>
                            @include('member.modal')
                        </x-modal>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
