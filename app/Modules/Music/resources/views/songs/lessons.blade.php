<x-app-layout>
    <x-slot name="header">
        @include('music::songs.partials.switcher', ['song' => $song, 'current' => 'lessons', 'label' => __('Lessons')])
    </x-slot>

    <style>
        .lessons-page { padding-bottom: 3rem; }
        .lessons-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: 1rem; }
        .lessons-toolbar .hint { font-size: .85rem; color: var(--tx-6b7280); }
        .lesson-group { margin: 1.25rem 0; }
        .lesson-group > summary { display: flex; align-items: center; gap: .5rem; padding: .4rem 0; font-size: 1.05rem; font-weight: 800; color: var(--tx-111827); cursor: pointer; list-style: none; }
        .lesson-group > summary::-webkit-details-marker { display: none; }
        .lesson-group > summary .count { padding: .05rem .5rem; border-radius: 999px; background: var(--brand-soft); color: var(--brand-text); font-size: .75rem; }
        .lesson-group > summary i { color: var(--tx-9ca3af); transition: transform .15s; }
        .lesson-group[open] > summary i { transform: rotate(90deg); }
        .lesson-list { display: grid; gap: .75rem; }
        .lesson-item { scroll-margin-top: 5rem; }
        .lesson-actions { display: flex; justify-content: flex-end; gap: .35rem; margin-top: .35rem; }
        .lesson-form { display: grid; gap: .6rem; padding: .9rem; border: 1px solid var(--brand-200); border-radius: .6rem; background: var(--bg-f8fafc); }
        .lf-row { display: flex; flex-wrap: wrap; gap: .6rem; }
        .lf-types { display: flex; flex-wrap: wrap; gap: .35rem; }
        .lf-type { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .7rem; border: 1px solid var(--bd-d1d5db); border-radius: 999px; background: var(--bg-fff); font-size: .85rem; font-weight: 600; color: var(--tx-374151); cursor: pointer; }
        .lf-type input { position: absolute; opacity: 0; pointer-events: none; }
        .lf-type.is-on { border-color: var(--brand); background: var(--brand-soft); color: var(--brand-text); }
        .lf-warn { margin: 0; font-size: .8rem; color: var(--tx-b45309); }
        .lf-field { display: grid; gap: .2rem; font-size: .8rem; font-weight: 600; color: var(--tx-4b5563); }
        .lf-grow { flex: 1 1 16rem; }
        .lf-field input[type=text], .lf-field input[type=url], .lf-field input[type=number], .lf-field textarea { width: 100%; padding: .4rem .55rem; border: 1px solid var(--bd-d1d5db); border-radius: .4rem; font-size: .9rem; font-weight: 400; }
        .lf-check { display: inline-flex; align-items: center; gap: .4rem; font-size: .85rem; }
        .lf-actions { display: flex; gap: .4rem; }
        .lf-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .75rem; border: 1px solid var(--bd-d1d5db); border-radius: .45rem; background: var(--bg-fff); font-size: .85rem; font-weight: 600; color: var(--tx-374151); cursor: pointer; }
        .lf-btn.is-primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .lf-btn.is-danger { color: var(--tx-dc2626); }
        .lessons-empty { padding: 2rem 1rem; text-align: center; color: var(--tx-6b7280); border: 1px dashed var(--bd-d1d5db); border-radius: .6rem; }
    </style>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-3 sm:p-6">
                @include('music::songs.partials.tabs', ['song' => $song, 'current' => 'lessons'])
                @include('layouts.alert')
                @if ($errors->any())
                    <div class="p-3 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                <datalist id="lesson-instruments">
                    @foreach ($instruments as $name)<option value="{{ $name }}"></option>@endforeach
                </datalist>

                <div class="lessons-page" x-data="{ adding: @js(($errors->any() && ! old('lesson_id')) || ! $groups) }">
                    <div class="lessons-toolbar">
                        <button type="button" class="lf-btn is-primary" x-show="!adding" @click="adding = true"><i class="fa-solid fa-plus"></i> {{ __('New lesson') }}</button>
                        <span class="hint">{{ __('Lessons appear on the public setlist page, for whoever picks the instrument. Mark A and B on a video or audio to repeat a stretch.') }}</span>
                    </div>
                    <div x-show="adding" x-cloak style="margin-bottom: 1rem;">
                        @include('music::songs.partials.lesson-form', ['lesson' => null])
                    </div>

                    @forelse ($groups as $group)
                        <details class="lesson-group" open>
                            <summary><i class="fa-solid fa-chevron-right"></i> {{ $group['instrument'] }} <span class="count">{{ count($group['lessons']) }}</span></summary>
                            <div class="lesson-list">
                                @foreach ($group['lessons'] as $payload)
                                    @php($model = $song->lessons->firstWhere('id', $payload['id']))
                                    <div class="lesson-item" id="lesson-{{ $payload['id'] }}"
                                        x-data="{ lesson: @js($payload), editing: @js((int) old('lesson_id') === $payload['id']) }">
                                        <div x-show="!editing">
                                            @include('music::songs.partials.lesson-card', ['saveUrl' => \Illuminate\Support\Js::from(route('songs.lessons.loops', [$song, $payload['id']]))])
                                            <div class="lesson-actions">
                                                <button type="button" class="lf-btn" @click="editing = true"><i class="fa-solid fa-pen"></i> {{ __('Edit') }}</button>
                                                <form method="POST" action="{{ route('songs.lessons.destroy', [$song, $payload['id']]) }}" onsubmit="return confirm(@js(__('Delete this lesson?')))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="lf-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                        <template x-if="editing">
                                            <div>@include('music::songs.partials.lesson-form', ['lesson' => $model])</div>
                                        </template>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="lessons-empty" x-show="!adding">{{ __('No lessons for this song yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/lesson-player.js') }}?v={{ filemtime(public_path('js/lesson-player.js')) }}"></script>
</x-app-layout>
