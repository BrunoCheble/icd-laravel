<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New announcement') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ route('announcements.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Announcements') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('New announcement') }}</h1>
                <p>{{ __('Add an announcement of a member.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('announcements.store') }}" role="form" enctype="multipart/form-data">
            @csrf
            @include('announcements::announcements.form')
        </form>
    </div>
</x-app-layout>
