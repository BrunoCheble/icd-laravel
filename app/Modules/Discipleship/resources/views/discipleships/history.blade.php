<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('History') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .dm-show { display: grid; gap: 1rem; max-width: 46rem; }
        .dm-card { overflow: hidden; }
        .dm-card > h3 { display: flex; align-items: center; gap: .5rem; margin: 0; padding: .85rem 1rem; border-bottom: 1px solid var(--bd-f1f2f4); font-size: .95rem; font-weight: 800; color: var(--tx-111827); }
        .dm-card > h3 small { margin-left: auto; font-size: .8rem; font-weight: 600; color: var(--tx-6b7280); }
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
        .dm-mentors { display: grid; gap: .1rem; padding: .4rem 0; }
        .dm-mentors div { display: flex; align-items: center; gap: .6rem; padding: .45rem 1rem; font-size: .92rem; color: var(--tx-374151); }
        .dm-mentors div strong { color: var(--tx-111827); }
    </style>

    <div class="m-page">
        <a href="{{ route('discipleships.index') }}" class="m-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Discipleships') }}</a>
        <div class="m-head">
            <div>
                <h1>{{ $discipleship->disciple->full_name }}</h1>
                <p>{{ __('All the meetings, with every mentor.') }}</p>
            </div>
        </div>

        <div class="dm-show">
            <section class="m-card dm-card">
                <h3><i class="fa-solid fa-user-tie m-muted"></i> {{ __('Mentors') }}</h3>
                <div class="dm-mentors">
                    @foreach ($mentors as $item)
                        <div>
                            <strong>{{ $item->mentor->full_name }}</strong>
                            <span class="m-muted">{{ $item->started_at ? __('since :date', ['date' => $item->started_at->format('d/m/Y')]) : '' }}</span>
                            @if ($item->is_active)<span class="m-tag is-ok">{{ __('Ongoing') }}</span>@else<span class="m-tag is-off">{{ __('Ended') }}</span>@endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="m-card dm-card">
                <h3><i class="fa-solid fa-clock-rotate-left m-muted"></i> {{ __('Meetings') }} <small>{{ $meetings->count() }}</small></h3>
                @forelse ($meetings as $meeting)
                    @include('discipleship::discipleships.partials.meeting-row', ['showMentor' => true])
                @empty
                    <p class="dm-empty">{{ __('No meetings yet.') }}</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
