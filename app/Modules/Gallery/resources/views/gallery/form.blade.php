<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $event->exists ? $event->name : ($event->isSpace() ? __('New space') : __('New event')) }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <div class="m-page">
        <a href="{{ $event->exists ? route('gallery.show', $event) : route('gallery.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ $event->exists ? $event->name : __('Gallery') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ $event->exists ? ($event->isSpace() ? __('Edit space') : __('Edit event')) : ($event->isSpace() ? __('New space') : __('New event')) }}</h1>
                @unless ($event->exists)<p>{{ __('After saving it, you add its photos.') }}</p>@endunless
            </div>
        </div>

        <form method="POST" action="{{ $event->exists ? route('gallery.update', $event) : route('gallery.store') }}">
            @csrf
            @if ($event->exists) @method('PUT') @endif
            <div class="m-form">
                {{-- An event has its date; a space has none (on the site's "Galeria", after the events) --}}
                <section class="m-card m-section" x-data="{ kind: @js(old('kind', $event->kind)) }">
                    <div class="m-field">
                        <span class="m-label">{{ __('Type') }}</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 1.25rem; margin-top: .25rem;">
                            <label style="display: flex; gap: .45rem; align-items: center; cursor: pointer;"><input type="radio" name="kind" value="event" x-model="kind" style="color: #f8a925;"> {{ __('Event') }}</label>
                            <label style="display: flex; gap: .45rem; align-items: center; cursor: pointer;"><input type="radio" name="kind" value="space" x-model="kind" style="color: #f8a925;"> {{ __('Space of the church') }}</label>
                        </div>
                        @error('kind')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="m-field mt-3">
                        <label class="m-label" for="name">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" class="m-input" value="{{ old('name', $event->name) }}" required maxlength="120" placeholder="{{ __('E.g. Culto de Santa Ceia') }}">
                        @error('name')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="m-field mt-3" x-show="kind === 'event'">
                        <label class="m-label" for="event_date">{{ __('Date') }}</label>
                        <input id="event_date" name="event_date" type="date" class="m-input" value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}" :required="kind === 'event'" :disabled="kind !== 'event'" style="max-width: 14rem;">
                        @error('event_date')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="mt-3" style="display: flex; gap: .6rem; align-items: flex-start; cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published)) style="margin-top: .2rem; width: 1.1rem; height: 1.1rem; border-radius: .3rem; color: #f8a925;">
                        <span>
                            <strong>{{ __('Show on the site') }}</strong>
                            <span class="m-help" style="display: block;" x-show="kind === 'event'">{{ __('Its photos show on the site\'s "Galeria" (the most recent events first).') }}</span>
                            <span class="m-help" style="display: block;" x-show="kind === 'space'" x-cloak>{{ __('Its photos show on the site\'s "Galeria", after the events (the spaces in the order they were made).') }}</span>
                        </span>
                    </label>
                </section>
                <div class="m-form-actions">
                    <a href="{{ $event->exists ? route('gallery.show', $event) : route('gallery.index') }}" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                </div>
            </div>
        </form>

        @if ($event->exists)
            @can('gallery.delete')
                <form method="POST" action="{{ route('gallery.destroy', $event) }}" class="mt-4" data-confirm="{{ __('Delete this event? Its photos go too.') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete event') }}</button>
                </form>
            @endcan
        @endif
    </div>
</x-app-layout>
