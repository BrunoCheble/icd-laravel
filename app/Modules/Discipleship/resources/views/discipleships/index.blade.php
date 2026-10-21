<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Discipleships') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .dm-grid { display: grid; gap: 1rem; margin-top: .75rem; }
        @media (min-width: 1100px) { .dm-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: start; } }
        .dm-card { overflow: hidden; }
        .dm-card > h3 { display: flex; align-items: center; gap: .5rem; margin: 0; padding: .85rem 1rem; border-bottom: 1px solid var(--bd-f1f2f4); font-size: .95rem; font-weight: 800; color: var(--tx-111827); }
        .dm-card > h3 small { margin-left: auto; font-size: .8rem; font-weight: 600; color: var(--tx-6b7280); }
        .dm-card.is-warning { border-color: var(--bd-fde68a); }
        .dm-card.is-warning > h3 { background: var(--bg-fffbeb); }
        .dm-empty { margin: 0; padding: 1rem; color: var(--tx-6b7280); font-size: .9rem; }
        .dm-row { display: flex; align-items: center; gap: .75rem; padding: .7rem 1rem; border-bottom: 1px solid var(--bd-f1f2f4); color: inherit; text-decoration: none; }
        .dm-row:last-child { border-bottom: 0; }
        .dm-row:hover { background: var(--bg-fafafa); }
        .dm-date { flex: none; display: grid; place-items: center; width: 2.9rem; height: 2.9rem; border-radius: .7rem; background: var(--bg-f3f4f6); line-height: 1; }
        .dm-date strong { font-size: 1.05rem; font-weight: 800; color: var(--tx-111827); }
        .dm-date small { font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--tx-6b7280); }
        .dm-date.is-today { background: var(--brand-soft); box-shadow: inset 0 0 0 2px var(--brand-200); }
        .dm-date.is-overdue { background: var(--bg-fef3c7); }
        .dm-text { flex: 1; min-width: 0; }
        .dm-text strong { display: block; font-weight: 700; color: var(--tx-111827); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dm-text small { display: block; font-size: .82rem; color: var(--tx-6b7280); }
        .dm-comments { font-size: .8rem; white-space: nowrap; }
        .dm-mentor { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding: .7rem 1rem; background: var(--bg-fafafa); border-bottom: 1px solid var(--bd-f1f2f4); font-weight: 700; color: var(--tx-111827); }
        .dm-mentor .m-tag { font-weight: 600; }
        .dm-disciple { display: flex; align-items: center; gap: .75rem; padding: .7rem 1rem; border-bottom: 1px solid var(--bd-f1f2f4); }
        .dm-disciple.is-off { opacity: .6; }
        .dm-disciple .dm-text small i { width: 1rem; }
        .dm-actions { display: flex; gap: .35rem; }
    </style>

    @php
        $initials = function (string $name) {
            $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
            return mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
        };
        $isAdmin = auth()->user()->is_admin;
        $short = fn ($meeting) => $meeting->localAt()->format('d/m') . ' · ' . $meeting->localAt()->format('H:i');
    @endphp

    <div class="m-page">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Discipleships') }}</h1>
                <p>{{ $isAdmin ? __('Who follows whom, and their meetings.') : __('The members you follow, and your meetings with them.') }}</p>
            </div>
            @if ($linked)
                <div class="m-head-actions">
                    @can('discipleships.write')
                        <a href="{{ route('discipleships.create', array_filter(['mentor' => $mentor])) }}" class="m-btn"><i class="fa-solid fa-user-plus"></i> <span class="m-hide-sm">{{ __('Add disciple') }}</span></a>
                        @if ($groups->isNotEmpty())
                            <a href="{{ route('discipleships.meetings.create', array_filter(['mentor' => $mentor])) }}" class="m-btn is-primary"><i class="fa-solid fa-calendar-plus"></i> {{ __('New meeting') }}</a>
                        @endif
                    @endcan
                </div>
            @endif
        </div>

        @unless ($linked)
            <div class="m-card m-section"><p class="m-muted"><i class="fa-solid fa-circle-info"></i> {{ __('Your user is not linked to a member: ask an administrator.') }}</p></div>
        @else
            @if ($isAdmin && $mentors->count() > 1)
                <form method="GET" class="m-toolbar">
                    <select name="mentor" class="m-input" onchange="this.form.submit()" aria-label="{{ __('Mentor') }}" style="max-width: 22rem;">
                        <option value="">{{ __('All mentors') }}</option>
                        @foreach ($mentors as $option)
                            <option value="{{ $option->id }}" @selected($mentor === $option->id)>{{ $option->full_name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <div class="dm-grid">
                <div style="display: grid; gap: 1rem;">
                    {{-- Meetings whose time went by: did they take place? --}}
                    @if ($overdue->isNotEmpty())
                        <section class="m-card dm-card is-warning">
                            <h3><i class="fa-solid fa-circle-question" style="color: #d97706;"></i> {{ __('Did they take place?') }} <small>{{ $overdue->count() }}</small></h3>
                            @foreach ($overdue as $meeting)
                                @include('discipleship::discipleships.partials.meeting-row', ['showMentor' => $isAdmin && ! $mentor])
                            @endforeach
                        </section>
                    @endif

                    <section class="m-card dm-card">
                        <h3><i class="fa-solid fa-calendar-day" style="color: var(--brand-text);"></i> {{ __('Next meetings') }} <small>{{ $upcoming->count() }}</small></h3>
                        @forelse ($upcoming as $meeting)
                            @include('discipleship::discipleships.partials.meeting-row', ['showMentor' => $isAdmin && ! $mentor])
                        @empty
                            <p class="dm-empty">{{ __('No meetings scheduled.') }}</p>
                        @endforelse
                    </section>

                    @if ($recent->isNotEmpty())
                        <section class="m-card dm-card">
                            <h3><i class="fa-solid fa-clock-rotate-left m-muted"></i> {{ __('Latest meetings') }}</h3>
                            @foreach ($recent as $meeting)
                                @include('discipleship::discipleships.partials.meeting-row', ['showMentor' => $isAdmin && ! $mentor])
                            @endforeach
                        </section>
                    @endif
                </div>

                {{-- The disciples, by mentor --}}
                <section class="m-card dm-card">
                    <h3><i class="fa-solid fa-seedling" style="color: #16a34a;"></i> {{ __('Disciples') }} <small>{{ $groups->sum(fn ($group) => $group['items']->where('is_active', true)->count()) }}</small></h3>
                    @forelse ($groups as $group)
                        @if ($isAdmin)
                            <div class="dm-mentor">
                                <i class="fa-solid fa-user-tie m-muted"></i> {{ $group['mentor']->full_name }}
                                @unless (in_array($group['mentor']->id, $withUser, true))
                                    <span class="m-tag is-off" title="{{ __('Without a user of the admin: does not receive the reminders.') }}"><i class="fa-regular fa-bell-slash"></i> {{ __('No reminders') }}</span>
                                @endunless
                            </div>
                        @endif
                        @foreach ($group['items'] as $discipleship)
                            <div @class(['dm-disciple', 'is-off' => ! $discipleship->is_active])>
                                <span class="m-initials">{{ $initials($discipleship->disciple->full_name) }}</span>
                                <span class="dm-text">
                                    <strong>{{ $discipleship->disciple->full_name }}@unless ($discipleship->is_active) <span class="m-tag is-off">{{ __('Ended') }}</span>@endunless</strong>
                                    <small><i class="fa-regular fa-calendar"></i> {{ __('Next') }}: {{ $discipleship->next_meeting ? $short($discipleship->next_meeting) : '—' }}</small>
                                    <small><i class="fa-solid fa-check"></i> {{ __('Last held') }}: {{ $discipleship->last_held ? $discipleship->last_held->localAt()->format('d/m/Y') : '—' }}@if ($discipleship->held_count) · {{ trans_choice(':count meeting|:count meetings', $discipleship->held_count, ['count' => $discipleship->held_count]) }}@endif</small>
                                </span>
                                <span class="dm-actions">
                                    @if ($discipleship->is_active)
                                        @can('discipleships.write')
                                            <a href="{{ route('discipleships.meetings.create', ['mentor' => $discipleship->mentor_id, 'disciples' => [$discipleship->disciple_id]]) }}" class="m-btn is-ghost is-icon" title="{{ __('New meeting') }}" aria-label="{{ __('New meeting') }}"><i class="fa-solid fa-calendar-plus"></i></a>
                                        @endcan
                                    @endif
                                    @if ($discipleship->is_active || $isAdmin)
                                        <a href="{{ route('discipleships.history', $discipleship) }}" class="m-btn is-ghost is-icon" title="{{ __('History') }}" aria-label="{{ __('History') }}"><i class="fa-solid fa-clock-rotate-left"></i></a>
                                    @endif
                                    @if ($phone = \App\Helpers\WhatsAppHelper::url($discipleship->disciple->phone_number))
                                        <a href="{{ $phone }}" target="_blank" rel="noopener" class="m-btn is-ghost is-icon" title="WhatsApp" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp" style="color: #25d366;"></i></a>
                                    @endif
                                    @can('discipleships.update')
                                        <a href="{{ route('discipleships.edit', $discipleship) }}" class="m-btn is-ghost is-icon" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                </span>
                            </div>
                        @endforeach
                    @empty
                        <p class="dm-empty">{{ __('No disciples yet. Add the members you follow.') }}</p>
                    @endforelse
                </section>
            </div>
        @endunless
    </div>
</x-app-layout>
