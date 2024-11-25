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

                    @include('layouts.alert')


                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">

                                @if ($view === 'table')
                                    <a href="{{ route('members.index', ['view' => 'card']) }}">
                                        <i class="fa fa-id-card" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <a href="{{ route('members.index', ['view' => 'table']) }}">
                                        <i class="fa fa-table" aria-hidden="true"></i>
                                    </a>
                                @endif
                                {{ __('Members') }}
                            </h1>
                        </div>

                        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-2">
                            <div>
                                <a type="button" href="{{ route('members.create') }}"
                                    class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                    {{ __('Create') }}
                                    {{ __('Member') }}
                                </a>
                            </div>

                            <div>
                                @include('member.filter')
                            </div>
                        </div>
                    </div>



                    <div x-data="{ selectedMember: null }" class="flow-root">

                        <div class="mt-8 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle">

                                @foreach ($members as $member)
                                    @include('member.modal-delete')
                                @endforeach

                                @if ($view === 'table')
                                    @include('member.table')
                                @else
                                    @include('member.list')
                                @endif

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
