<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Visitor') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ route('visitors.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Visitors') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Create Visitor') }}</h1>
                <p>{{ __('Register someone who visited the church.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('visitors.store') }}" role="form">
            @csrf
            @include('visitors::visitors.form')
        </form>
    </div>
</x-app-layout>
