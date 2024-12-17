<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Visitors') }}
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
                                {{ __('Visitors') }}
                            </h1>
                        </div>

                        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-2">
                            <div x-data="{}">
                                @include('visitors.modal-create')
                                <button type="button"
                                        class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                        x-on:click="$dispatch('open-modal', 'modal-create-visitor')">
                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                    {{ __('Create') }} {{ __('Visitor') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flow-root">
                        <div class="mt-8 overflow-x-auto">
                            <div class="inline-block min-w-full min-h-screen py-2 align-middle">
                                @include('visitors.table.index')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
