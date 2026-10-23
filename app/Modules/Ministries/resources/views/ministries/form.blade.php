<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $ministry->exists ? $ministry->name : __('New ministry') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ $ministry->exists ? route('ministries.show', $ministry) : route('ministries.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ $ministry->exists ? $ministry->name : __('Ministries') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ $ministry->exists ? __('Edit ministry') : __('New ministry') }}</h1>
            </div>
        </div>

        <form method="POST" action="{{ $ministry->exists ? route('ministries.update', $ministry) : route('ministries.store') }}">
            @csrf
            @if ($ministry->exists) @method('PUT') @endif
            <div class="m-form">
                <section class="m-card m-section">
                    <div class="m-field">
                        <label class="m-label" for="name">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" class="m-input" value="{{ old('name', $ministry->name) }}" required maxlength="255" placeholder="{{ __('E.g. ICD Pastoral') }}">
                        @error('name')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="mt-3" style="display: flex; gap: .6rem; align-items: flex-start; cursor: pointer;">
                        <input type="checkbox" name="is_pastoral" value="1" @checked(old('is_pastoral', $ministry->isPastoral())) style="margin-top: .2rem; width: 1.1rem; height: 1.1rem; border-radius: .3rem; color: #f8a925;">
                        <span>
                            <strong>{{ __('Show on the site as "Pastores"') }}</strong>
                            <span class="m-help" style="display: block;">{{ __('Its active members show on the church\'s site, with photo, role and biography, in the order chosen here.') }}</span>
                        </span>
                    </label>
                </section>
                <div class="m-form-actions">
                    <a href="{{ $ministry->exists ? route('ministries.show', $ministry) : route('ministries.index') }}" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                </div>
            </div>
        </form>

        @if ($ministry->exists)
            @can('members.delete')
                <form method="POST" action="{{ route('ministries.destroy', $ministry) }}" class="mt-4" data-confirm="{{ __('Delete this ministry? Its roles and the list of its members go too (the members stay).') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete ministry') }}</button>
                </form>
            @endcan
        @endif
    </div>
</x-app-layout>
