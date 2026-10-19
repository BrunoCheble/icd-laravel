<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Setlist') }}
        </h2>
    </x-slot>

    @include('music::partials.ui')

    <div class="m-page is-narrow">
        <a href="{{ route('setlists.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Setlists') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Create Setlist') }}</h1>
                <p>{{ __('Add a new setlist.') }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('setlists.import') }}" class="m-btn"><i class="fa-solid fa-paste"></i> {{ __('Import schedule') }}</a>
            </div>
        </div>

        <form method="POST" action="{{ route('setlists.store') }}" role="form">
            @csrf
            @include('music::setlists.form')
        </form>
    </div>
</x-app-layout>
