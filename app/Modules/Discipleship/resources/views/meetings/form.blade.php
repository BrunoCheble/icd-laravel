<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $meeting->exists ? __('Edit meeting') : __('New meeting') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .dm-pick { display: grid; gap: .4rem; }
        .dm-pick label { display: flex; align-items: center; gap: .6rem; padding: .6rem .75rem; border: 1px solid var(--bd-e5e7eb); border-radius: .6rem; cursor: pointer; font-weight: 600; color: var(--tx-111827); }
        .dm-pick label:has(input:checked) { border-color: var(--brand); background: var(--brand-soft); }
        .dm-pick input { width: 1.15rem; height: 1.15rem; border-radius: .3rem; color: #f8a925; }
    </style>

    @php
        $isAdmin = auth()->user()->is_admin;
        $at = $meeting->exists ? $meeting->localAt() : null;
    @endphp

    <div class="m-page">
        <a href="{{ $meeting->exists ? route('discipleships.meetings.show', $meeting) : route('discipleships.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ $meeting->exists ? __('Meeting') : __('Discipleships') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ $meeting->exists ? __('Edit meeting') : __('New meeting') }}</h1>
                <p>{{ __('The day and time, and who comes. The mentor is reminded on the day and 2 hours before.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ $meeting->exists ? route('discipleships.meetings.update', $meeting) : route('discipleships.meetings.store') }}">
            @csrf
            @if ($meeting->exists) @method('PUT') @endif

            <div class="m-form" x-data="meetingForm(@js([
                    'mentors' => $mentors,
                    'mentor' => (string) old('mentor_id', $meeting->mentor_id ?: (count($mentors) === 1 ? $mentors[0]['id'] : '')),
                    'chosen' => array_map('strval', (array) old('disciples', $chosen)),
                ]))">
                @if ($errors->any())
                    <div class="m-card m-section" style="border-color: var(--bd-fecaca); background: var(--bg-fef2f2);">
                        <p class="m-error"><i class="fa-solid fa-triangle-exclamation"></i> {{ __('Check the fields marked below.') }}</p>
                    </div>
                @endif

                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-solid fa-users"></i> {{ __('Who') }}</h3>
                    @if ($isAdmin)
                        <div class="m-field">
                            <label class="m-label" for="mentor_id">{{ __('Mentor') }}</label>
                            <select id="mentor_id" name="mentor_id" class="m-input" x-model="mentor" @change="chosen = []" @disabled($meeting->exists)>
                                <option value="">{{ __('Choose…') }}</option>
                                <template x-for="option in mentors" :key="option.id"><option :value="option.id" x-text="option.name" :selected="option.id === mentor"></option></template>
                            </select>
                            @if ($meeting->exists)<input type="hidden" name="mentor_id" value="{{ $meeting->mentor_id }}">@endif
                            @error('mentor_id')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                    @else
                        @error('mentor_id')<p class="m-error">{{ $message }}</p>@enderror
                    @endif
                    <div class="m-field">
                        <span class="m-label">{{ __('Disciples') }}</span>
                        <div class="dm-pick">
                            <template x-for="disciple in disciples" :key="disciple.id">
                                <label><input type="checkbox" name="disciples[]" :value="disciple.id" x-model="chosen"> <span x-text="disciple.name"></span></label>
                            </template>
                        </div>
                        <p class="m-help" x-show="!disciples.length">{{ $isAdmin ? __('Choose the mentor first.') : __('Add your disciples first.') }}</p>
                        <p class="m-help" x-show="disciples.length > 1">{{ __('One or more: a meeting can be with a group.') }}</p>
                        @error('disciples')<p class="m-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="m-card m-section">
                    <h3 class="m-section-title"><i class="fa-solid fa-calendar-day"></i> {{ __('When and where') }}</h3>
                    <div class="m-grid cols-2">
                        <div class="m-field">
                            <label class="m-label" for="date">{{ __('Date') }}</label>
                            <input id="date" name="date" type="date" class="m-input" value="{{ old('date', $at?->format('Y-m-d')) }}" required>
                            @error('date')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="time">{{ __('Time') }}</label>
                            <input id="time" name="time" type="time" class="m-input" value="{{ old('time', $at?->format('H:i')) }}" required>
                            @error('time')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field m-span-2">
                            <label class="m-label" for="location">{{ __('Place') }} <span class="m-optional">({{ __('optional') }})</span></label>
                            <input id="location" name="location" type="text" class="m-input" value="{{ old('location', $meeting->location) }}" maxlength="120" placeholder="{{ __('E.g. At the church, online, a café') }}">
                            @error('location')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <div class="m-form-actions">
                    <a href="{{ $meeting->exists ? route('discipleships.meetings.show', $meeting) : route('discipleships.index') }}" class="m-btn">{{ __('Cancel') }}</a>
                    <button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // The mentor's disciples to choose (one or more).
        function meetingForm({ mentors, mentor, chosen }) {
            return {
                mentors, mentor, chosen,
                get disciples() { return (this.mentors.find(option => option.id === this.mentor) || {}).disciples || []; },
            };
        }
    </script>
</x-app-layout>
