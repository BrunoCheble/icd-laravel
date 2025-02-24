<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ministry') }}: {{ $ministry->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="w-full">

                    @include('layouts.alert')

                    <div class="flow-root">
                        <div class="mt-8 overflow-x-auto">
                            <div x-data="{}" class="inline-block min-w-full min-h-screen py-2 align-middle">
                                @include('ministries-members.form')
                                @include('ministries-members.table.index')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
