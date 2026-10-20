<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Anniversaries') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .anniv-months { display: flex; gap: .35rem; margin-bottom: 1rem; overflow-x: auto; scrollbar-width: none; scroll-snap-type: x proximity; }
        .anniv-months::-webkit-scrollbar { display: none; }
        .anniv-months a { flex: none; scroll-snap-align: center; display: inline-flex; align-items: center; gap: .35rem; min-height: 36px; padding: 0 .85rem; border: 1px solid var(--bd-e5e7eb); border-radius: 999px; background: var(--bg-fff); color: var(--tx-4b5563); font-size: .85rem; font-weight: 600; text-decoration: none; text-transform: capitalize; }
        .anniv-months a:hover { border-color: var(--bd-d1d5db); color: var(--tx-111827); }
        .anniv-months a.is-active { border-color: var(--brand); background: var(--brand-soft); color: var(--brand-strong); }
        .anniv-months a.is-now::after { content: ''; width: .4rem; height: .4rem; border-radius: 999px; background: var(--brand); }

        .anniv-grid { display: grid; gap: 1rem; align-items: start; }
        @media (min-width: 1280px) { .anniv-grid { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); } }
        .anniv-title { display: flex; align-items: center; gap: .5rem; margin: 0; padding: 1rem 1.1rem .5rem; font-size: .95rem; font-weight: 800; color: var(--tx-111827); }
        .anniv-title i { width: 1.9rem; height: 1.9rem; display: grid; place-items: center; border-radius: .55rem; background: var(--brand-soft); color: var(--brand-hover); font-size: .85rem; }
        .anniv-title small { margin-left: auto; font-size: .78rem; font-weight: 700; color: var(--tx-9ca3af); }

        .anniv-row { display: flex; align-items: center; gap: .85rem; padding: .7rem 1.1rem; border-top: 1px solid var(--bd-f1f2f4); }
        .anniv-row.is-past { opacity: .55; }
        .anniv-row.is-today { background: var(--brand-soft); }
        .anniv-day { flex: none; width: 3rem; padding: .3rem 0; border: 1px solid var(--bd-e5e7eb); border-radius: .65rem; background: var(--bg-fff); text-align: center; line-height: 1.1; }
        .anniv-day strong { display: block; font-size: 1.15rem; font-weight: 800; color: var(--tx-111827); }
        .anniv-day small { display: block; font-size: .68rem; font-weight: 700; color: var(--tx-9ca3af); text-transform: uppercase; }
        .anniv-row.is-today .anniv-day { border-color: var(--brand); }
        .anniv-row.is-today .anniv-day strong { color: var(--brand-strong); }
        .anniv-main { flex: 1; min-width: 0; display: flex; align-items: center; gap: .7rem; color: inherit; text-decoration: none; }
        .anniv-text { min-width: 0; }
        .anniv-text strong { display: block; font-weight: 700; color: var(--tx-111827); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .anniv-text strong a { color: inherit; text-decoration: none; }
        .anniv-text a:hover, a.anniv-main:hover strong { color: var(--brand-text); }
        .anniv-text small { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-top: .1rem; font-size: .82rem; color: var(--tx-6b7280); }
        .anniv-couple { flex: none; display: flex; }
        .anniv-couple > * { box-shadow: 0 0 0 2px #fff; }
        .anniv-couple > * + * { margin-left: -.6rem; }
    </style>

    @php
        $today = now();
        $isThisMonth = $month === $today->month;
        $monthName = fn (int $m) => \Carbon\Carbon::create(null, $m, 1)->locale(app()->getLocale())->isoFormat('MMMM');
        $statuses = \App\Enums\MembershipStatus::options();
        $tagOf = ['pending' => 'is-part', 'inactived' => 'is-danger'];
        $rowClass = fn ($date) => $isThisMonth ? ($date->isSameDay($today) ? 'is-today' : ($date->lt($today->copy()->startOfDay()) ? 'is-past' : '')) : '';
        $weekday = fn ($date) => rtrim($date->locale(app()->getLocale())->isoFormat('ddd'), '.');
    @endphp

    <div class="m-page">
        <div class="m-head">
            <div>
                <h1 style="text-transform: capitalize;">{{ __('Anniversaries') }} · {{ $monthName($month) }}</h1>
                <p><strong style="color: var(--tx-111827); text-transform: capitalize;">{{ $monthName($month) }}</strong> · {{ trans_choice(':count birthday|:count birthdays', $birthdays->count(), ['count' => $birthdays->count()]) }} · {{ trans_choice(':count wedding anniversary|:count wedding anniversaries', $weddings->count(), ['count' => $weddings->count()]) }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('report.anniversaries.index', ['month' => $month === 1 ? 12 : $month - 1]) }}" class="m-btn is-icon" aria-label="{{ __('Previous month') }}"><i class="fa-solid fa-chevron-left"></i></a>
                @unless ($isThisMonth)
                    <a href="{{ route('report.anniversaries.index') }}" class="m-btn">{{ __('This month') }}</a>
                @endunless
                <a href="{{ route('report.anniversaries.index', ['month' => $month === 12 ? 1 : $month + 1]) }}" class="m-btn is-icon" aria-label="{{ __('Next month') }}"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
        </div>

        <nav class="anniv-months" aria-label="{{ __('Month') }}" x-data x-init="$el.querySelector('.is-active')?.scrollIntoView({ inline: 'center', block: 'nearest' })">
            @foreach (range(1, 12) as $m)
                <a href="{{ route('report.anniversaries.index', ['month' => $m]) }}" @class(['is-active' => $m === $month, 'is-now' => $m === $today->month])>{{ $monthName($m) }}</a>
            @endforeach
        </nav>

        <div class="anniv-grid">
            {{-- Birthdays --}}
            <section class="m-card m-list">
                <h3 class="anniv-title"><i class="fa-solid fa-cake-candles"></i> {{ __('Birthdays') }} <small>{{ $birthdays->count() }}</small></h3>
                @forelse ($birthdays as $entry)
                    @php($member = $entry['member'])
                    <div class="anniv-row {{ $rowClass($entry['date']) }}">
                        <span class="anniv-day"><strong>{{ $entry['date']->day }}</strong><small>{{ $weekday($entry['date']) }}</small></span>
                        <a href="{{ route('members.show', $member) }}" class="anniv-main">
                            @include('members::members.partials.avatar', ['class' => 'w-10 h-10'])
                            <span class="anniv-text">
                                <strong>{{ $member->first_and_last_name }}</strong>
                                <small>
                                    @if ($entry['date']->isSameDay($today))<span class="m-tag is-ok"><i class="fa-solid fa-gift"></i> {{ __('Today') }}</span>@endif
                                    @if ($entry['years']){{ __('Turns :count', ['count' => $entry['years']]) }}@endif
                                    @if (isset($tagOf[$member->membership_status]))<span class="m-tag {{ $tagOf[$member->membership_status] }}">{{ $statuses[$member->membership_status] }}</span>@endif
                                </small>
                            </span>
                        </a>
                        @include('members::members.partials.actions-button', ['icon' => 'fa-ellipsis-vertical'])
                    </div>
                @empty
                    <div class="m-empty"><i class="fa-solid fa-cake-candles"></i>{{ __('No birthdays this month.') }}</div>
                @endforelse
            </section>

            {{-- Wedding anniversaries: one row per couple --}}
            <section class="m-card m-list">
                <h3 class="anniv-title"><i class="fa-solid fa-heart"></i> {{ __('Wedding anniversaries') }} <small>{{ $weddings->count() }}</small></h3>
                @forelse ($weddings as $entry)
                    <div class="anniv-row {{ $rowClass($entry['date']) }}">
                        <span class="anniv-day"><strong>{{ $entry['date']->day }}</strong><small>{{ $weekday($entry['date']) }}</small></span>
                        <div class="anniv-main">
                            <span class="anniv-couple">
                                @foreach ($entry['members'] as $person)
                                    @include('members::members.partials.avatar', ['member' => $person, 'class' => 'w-10 h-10'])
                                @endforeach
                            </span>
                            <span class="anniv-text">
                                <strong>
                                    @foreach ($entry['members'] as $person)
                                        @unless ($loop->first) & @endunless<a href="{{ route('members.show', $person) }}">{{ $person->first_name }}</a>
                                    @endforeach
                                    {{ $entry['members']->first()->last_name }}
                                </strong>
                                <small>
                                    @if ($entry['date']->isSameDay($today))<span class="m-tag is-ok"><i class="fa-solid fa-heart"></i> {{ __('Today') }}</span>@endif
                                    @if ($entry['years']){{ trans_choice(':count year married|:count years married', $entry['years'], ['count' => $entry['years']]) }}@endif
                                </small>
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="m-empty"><i class="fa-solid fa-heart"></i>{{ __('No wedding anniversaries this month.') }}</div>
                @endforelse
            </section>
        </div>

        @include('members::members.partials.actions')
    </div>
</x-app-layout>
