<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Visitor') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ route('visitors.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Visitors') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ __('Edit Visitor') }}</h1>
                <p>{{ $visitor->name }} · {{ __('Visit') }} {{ $visitor->created_at_formatted }}</p>
            </div>
            <div class="m-head-actions">
                @include('visitors::visitors.partials.actions-button')
            </div>
        </div>

        <form method="POST" action="{{ route('visitors.update', $visitor) }}" role="form">
            @csrf
            @method('PATCH')
            @include('visitors::visitors.form')
        </form>

        @include('visitors::visitors.partials.actions')
    </div>
</x-app-layout>
