<x-app-layout>
    <x-slot name="header">
        @include('music::songs.partials.switcher', ['song' => $song, 'current' => 'edit', 'label' => __('Edit')])
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('music::songs.partials.tabs', ['song' => $song, 'current' => 'edit'])
                <div class="w-full">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">{{ __('Update Song') }}</h1>
                            <p class="mt-2 text-sm text-gray-700">{{ __('Update an existing song.') }}</p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                            <a type="button" href="{{ route('songs.index') }}" class="block rounded-md bg-primary-500 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500">{{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="flow-root">
                        <div class="mt-8 overflow-x-auto">
                            <div class="max-w-3xl py-2 align-middle">
                                <form method="POST" action="{{ route('songs.update', $song) }}" role="form" enctype="multipart/form-data">
                                    {{ method_field('PATCH') }}
                                    @csrf
                                    @include('music::songs.form')
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
