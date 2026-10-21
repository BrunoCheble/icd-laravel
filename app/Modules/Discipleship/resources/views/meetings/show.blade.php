<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Meeting') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .dm-show { display: grid; gap: 1rem; max-width: 46rem; }
        .dm-when { display: flex; align-items: center; gap: 1rem; }
        .dm-when .dm-day { flex: none; display: grid; place-items: center; width: 4rem; height: 4rem; border-radius: 1rem; background: var(--brand-soft); line-height: 1; }
        .dm-when .dm-day strong { font-size: 1.5rem; font-weight: 800; color: var(--tx-111827); }
        .dm-when .dm-day small { font-size: .72rem; font-weight: 700; text-transform: uppercase; color: var(--brand-text); }
        .dm-when p { margin: 0; color: var(--tx-4b5563); }
        .dm-when p strong { display: block; font-size: 1.1rem; color: var(--tx-111827); }
        .dm-people { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .9rem; }
        .dm-outcome { display: grid; gap: .9rem; }
        .dm-attend { display: grid; gap: .4rem; }
        .dm-attend label { display: flex; align-items: center; gap: .6rem; font-weight: 600; color: var(--tx-111827); }
        .dm-attend input { width: 1.15rem; height: 1.15rem; border-radius: .3rem; color: #f8a925; }
        .dm-comment { padding: .75rem 0; border-top: 1px solid var(--bd-f1f2f4); }
        .dm-comment:first-of-type { border-top: 0; }
        .dm-comment header { display: flex; align-items: center; gap: .5rem; font-size: .82rem; color: var(--tx-6b7280); }
        .dm-comment header strong { color: var(--tx-111827); }
        .dm-comment header form { margin-left: auto; }
        .dm-comment p { margin: .3rem 0 0; color: var(--tx-374151); white-space: pre-line; overflow-wrap: anywhere; }
    </style>

    @php
        $at = $meeting->localAt()->locale(app()->getLocale());
        $reasons = \App\Modules\Discipleship\Models\DiscipleshipMeeting::REASONS;
        $attended = $meeting->disciples->filter(fn ($disciple) => $disciple->pivot->attended !== false)->pluck('id')->map(fn ($id) => (string) $id)->values();
    @endphp

    <div class="m-page">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('discipleships.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Back') }}</a>
        @include('layouts.alert')

        <div class="dm-show">
            <section class="m-card m-section">
                <div class="dm-when">
                    <span class="dm-day"><strong>{{ $at->format('d') }}</strong><small>{{ \Illuminate\Support\Str::ucfirst(rtrim($at->isoFormat('MMM'), '.')) }}</small></span>
                    <p>
                        <strong>{{ \Illuminate\Support\Str::ucfirst($at->isoFormat('dddd')) }}, {{ $at->format('H:i') }}</strong>
                        {{ $meeting->location ?: __('No place given') }} · {{ __('Mentor') }}: {{ $meeting->mentor->full_name }}
                    </p>
                </div>
                <div class="dm-people">
                    @foreach ($meeting->disciples as $disciple)
                        <span @class(['m-tag', 'is-ok' => $disciple->pivot->attended === true, 'is-danger' => $disciple->pivot->attended === false, 'is-media' => $disciple->pivot->attended === null])>
                            @if ($disciple->pivot->attended === true)<i class="fa-solid fa-check"></i>@elseif ($disciple->pivot->attended === false)<i class="fa-solid fa-xmark"></i>@endif
                            {{ $disciple->full_name }}
                        </span>
                    @endforeach
                </div>
                @if ($canChange)
                @can('discipleships.update')
                    <div class="mt-4" style="display: flex; gap: .5rem; flex-wrap: wrap;">
                        <a href="{{ route('discipleships.meetings.edit', $meeting) }}" class="m-btn"><i class="fa-solid fa-pen"></i> {{ __('Change date, time or people') }}</a>
                    </div>
                @endcan
                @endif
            </section>

            {{-- Did it take place? --}}
            <section class="m-card m-section">
                <h3 class="m-section-title"><i class="fa-solid fa-clipboard-check"></i> {{ __('Did it take place?') }}</h3>
                @if ($canChange)
                <form method="POST" action="{{ route('discipleships.meetings.outcome', $meeting) }}" class="dm-outcome"
                    x-data="{ status: @js(old('status', $meeting->status)) }">
                    @csrf
                    @method('PATCH')
                    <div class="m-seg" role="radiogroup" aria-label="{{ __('Did it take place?') }}">
                        <label><input type="radio" name="status" value="held" x-model="status"><span><i class="fa-solid fa-check"></i> {{ __('Yes') }}</span></label>
                        <label><input type="radio" name="status" value="not_held" x-model="status"><span><i class="fa-solid fa-xmark"></i> {{ __('No') }}</span></label>
                        <label><input type="radio" name="status" value="scheduled" x-model="status"><span>{{ __('Not yet') }}</span></label>
                    </div>

                    <div x-show="status === 'held'" x-cloak>
                        @if ($meeting->disciples->count() > 1)
                            <span class="m-label">{{ __('Who was there') }}</span>
                        @endif
                        <div class="dm-attend" @if ($meeting->disciples->count() === 1) hidden @endif>
                            @foreach ($meeting->disciples as $disciple)
                                <label><input type="checkbox" name="attended[]" value="{{ $disciple->id }}" @checked($attended->contains((string) $disciple->id))> {{ $disciple->full_name }}</label>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="status === 'not_held'" x-cloak class="m-grid cols-2">
                        <div class="m-field">
                            <label class="m-label" for="reason">{{ __('Why') }}</label>
                            <select id="reason" name="reason" class="m-input">
                                <option value="">{{ __('Choose…') }}</option>
                                @foreach ($reasons as $key => $label)
                                    <option value="{{ $key }}" @selected(old('reason', $meeting->reason) === $key)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            @error('reason')<p class="m-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="reason_note">{{ __('Details') }} <span class="m-optional">({{ __('optional') }})</span></label>
                            <input id="reason_note" name="reason_note" type="text" class="m-input" maxlength="255" value="{{ old('reason_note', $meeting->reason_note) }}">
                        </div>
                    </div>

                    @can('discipleships.update')
                        <div><button type="submit" class="m-btn is-primary"><i class="fa-solid fa-check"></i> {{ __('Save') }}</button></div>
                    @endcan
                </form>
                @else
                    {{-- An earlier meeting with another mentor: as it was marked --}}
                    <p class="m-muted">
                        @if ($meeting->status === 'held')
                            <i class="fa-solid fa-check" style="color: #16a34a;"></i> {{ __('Held') }}
                        @elseif ($meeting->status === 'not_held')
                            <i class="fa-solid fa-xmark" style="color: #dc2626;"></i> {{ __('Did not take place') }}: {{ __($reasons[$meeting->reason] ?? $meeting->reason) }}@if ($meeting->reason_note) — {{ $meeting->reason_note }}@endif
                        @else
                            {{ __('Not marked by :name.', ['name' => $meeting->mentor->full_name]) }}
                        @endif
                    </p>
                @endif
            </section>

            {{-- Comments --}}
            <section class="m-card m-section" id="comments">
                <h3 class="m-section-title"><i class="fa-regular fa-comment"></i> {{ __('Comments') }}</h3>
                @if ($canComment)
                @can('discipleships.update')
                    <form method="POST" action="{{ route('discipleships.meetings.comments.store', $meeting) }}" class="m-field">
                        @csrf
                        <textarea name="body" rows="3" class="m-input" required maxlength="5000" placeholder="{{ __('What was talked about, prayer requests, next steps…') }}">{{ old('body') }}</textarea>
                        @error('body')<p class="m-error">{{ $message }}</p>@enderror
                        <div class="mt-2"><button type="submit" class="m-btn"><i class="fa-solid fa-paper-plane"></i> {{ __('Add comment') }}</button></div>
                    </form>
                @endcan
                @endif
                @forelse ($meeting->comments as $comment)
                    <article class="dm-comment">
                        <header>
                            <strong>{{ $comment->user?->name ?? __('Deleted user') }}</strong> · {{ $comment->created_at->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}
                            @if (auth()->user()->is_admin || $comment->user_id === auth()->id())
                                <form method="POST" action="{{ route('discipleships.meetings.comments.destroy', $comment) }}" x-data @submit="if (!confirm(@js(__('Delete this comment?')))) $event.preventDefault()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="m-btn is-ghost is-icon" aria-label="{{ __('Delete') }}"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endif
                        </header>
                        <p>{{ $comment->body }}</p>
                    </article>
                @empty
                    <p class="m-muted">{{ __('No comments yet.') }}</p>
                @endforelse
            </section>

            @if ($canChange)
            @can('discipleships.delete')
                <form method="POST" action="{{ route('discipleships.meetings.destroy', $meeting) }}" x-data @submit="if (!confirm(@js(__('Delete this meeting?')))) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete meeting') }}</button>
                </form>
            @endcan
            @endif
        </div>
    </div>
</x-app-layout>
