<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Song') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page is-narrow">
        <a href="{{ route('songs.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Songs') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Create Song') }}</h1>
                <p>{{ __('Add a new song.') }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('songs.bookmarklet') }}" class="m-btn"><i class="fa-solid fa-file-import"></i> {{ __('Import from a chord site') }}</a>
            </div>
        </div>

        <form method="POST" action="{{ route('songs.store') }}" role="form" enctype="multipart/form-data">
            @csrf
            @include('music::songs.form')
        </form>
    </div>
</x-app-layout>
