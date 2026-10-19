<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Member') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ route('members.show', $member) }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ $member->first_and_last_name }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Edit Member') }}</h1>
                <p>{{ $member->full_name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('members.update', $member) }}" role="form">
            @csrf
            @method('PATCH')
            @include('members::members.form')
        </form>
    </div>
</x-app-layout>
