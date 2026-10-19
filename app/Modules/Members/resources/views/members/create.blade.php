<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Member') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ route('members.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Members') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Create Member') }}</h1>
                <p>{{ __('Add a new member of the church.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('members.store') }}" role="form">
            @csrf
            @include('members::members.form')
        </form>
    </div>
</x-app-layout>
