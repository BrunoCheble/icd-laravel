<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $discipleship->exists ? __('Discipleship') : __('Add disciple') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    @php
        $field = fn (string $name) => old($name, $discipleship->{$name});
        $isAdmin = auth()->user()->is_admin;
        if (! $discipleship->exists && $isAdmin && request('mentor')) {
            $discipleship->mentor_id = (int) request('mentor');
        }
    @endphp

    <div class="m-page">
        <a href="{{ route('discipleships.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Discipleships') }}</a>
        @include('layouts.alert')
        <div class="m-head">
            <div>
                <h1>{{ $discipleship->exists ? $discipleship->disciple->full_name : __('Add disciple') }}</h1>
                <p>{{ $isAdmin ? __('A member followed by another (the mentor).') : __('A member you follow.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ $discipleship->exists ? route('discipleships.update', $discipleship) : route('discipleships.store') }}">
            @csrf
            @if ($discipleship->exists) @method('PUT') @endif

            <div class="m-form">
                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-solid fa-seedling"></i> {{ __('Discipleship') }}</h3>
                    <div class="m-grid cols-2">
                        @if ($isAdmin)
                            <div class="m-field m-span-2">
                                <span class="m-label">{{ __('Mentor') }}</span>
                                @include('members::members.partials.person-picker', ['member' => $discipleship, 'name' => 'mentor_id', 'want' => null])
                                @error('mentor_id')<p class="m-error">{{ $message }}</p>@enderror
                            </div>
                        @else
                            @error('mentor_id')<p class="m-error m-span-2">{{ $message }}</p>@enderror
                        @endif
                        <div class="m-field m-span-2">
                            <span class="m-label">{{ __('Disciple') }}</span>
                            @include('members::members.partials.person-picker', ['member' => $discipleship, 'name' => 'disciple_id', 'want' => null])
                            @error('disciple_id')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="started_at">{{ __('Since') }} <span class="m-optional">({{ __('optional') }})</span></label>
                            <input id="started_at" name="started_at" type="date" class="m-input" value="{{ old('started_at', $discipleship->started_at?->format('Y-m-d')) }}">
                            @error('started_at')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        @if ($discipleship->exists)
                            <div class="m-field">
                                <span class="m-label">{{ __('State') }}</span>
                                <div class="m-seg" role="radiogroup" aria-label="{{ __('State') }}">
                                    <label><input type="radio" name="is_active" value="1" @checked((bool) $field('is_active'))><span>{{ __('Ongoing') }}</span></label>
                                    <label><input type="radio" name="is_active" value="0" @checked(! (bool) $field('is_active'))><span>{{ __('Ended') }}</span></label>
                                </div>
                            </div>
                        @endif
                        <div class="m-field m-span-2">
                            <label class="m-label" for="notes">{{ __('Notes') }} <span class="m-optional">({{ __('optional') }})</span></label>
                            <textarea id="notes" name="notes" rows="4" class="m-input" maxlength="2000">{{ $field('notes') }}</textarea>
                            @error('notes')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <div class="m-form-actions">
                    <a href="{{ route('discipleships.index') }}" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                </div>
            </div>
        </form>

        @if ($discipleship->exists)
            @can('discipleships.delete')
                <form method="POST" action="{{ route('discipleships.destroy', $discipleship) }}" class="mt-4" x-data @submit="if (!confirm(@js(__('Delete this discipleship? The meetings stay in the history.')))) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                </form>
            @endcan
        @endif
    </div>

    <script>
        window.memberPeople = @js($people);
    </script>
</x-app-layout>
