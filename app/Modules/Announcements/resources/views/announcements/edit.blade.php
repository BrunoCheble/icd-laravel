<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Announcement') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ route('announcements.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Announcements') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Edit Announcement') }}</h1>
                <p>{{ $announcement->title }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('announcements.update', $announcement) }}" role="form" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            @include('announcements::announcements.form')
        </form>
    </div>
</x-app-layout>
