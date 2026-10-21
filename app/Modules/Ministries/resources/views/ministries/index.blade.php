<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ministries') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .mn-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(18rem, 100%), 1fr)); gap: 1rem; margin-top: .75rem; }
        .mn-card { display: flex; align-items: center; gap: .85rem; padding: 1rem; color: inherit; text-decoration: none; }
        .mn-card:hover strong { color: var(--brand-text); }
        .mn-icon { flex: none; display: grid; place-items: center; width: 2.75rem; height: 2.75rem; border-radius: .8rem; background: var(--brand-soft); color: var(--brand-text); font-size: 1.1rem; }
        .mn-card strong { display: block; font-weight: 800; color: var(--tx-111827); }
        .mn-card small { display: block; margin-top: .1rem; font-size: .82rem; color: var(--tx-6b7280); }
        .mn-card .m-tags { margin-top: .35rem; }
    </style>

    <div class="m-page">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Ministries') }}</h1>
                <p>{{ __('Each ministry with its roles and members.') }}</p>
            </div>
            @can('members.write')
                <div class="m-head-actions">
                    <a href="{{ route('ministries.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('New ministry') }}</a>
                </div>
            @endcan
        </div>

        @if ($ministries->isEmpty())
            <div class="m-card m-section"><p class="m-muted">{{ __('No ministries yet.') }}</p></div>
        @else
            <div class="mn-list">
                @foreach ($ministries as $ministry)
                    <a href="{{ route('ministries.show', $ministry) }}" class="m-card mn-card">
                        <span class="mn-icon"><i class="fa-solid {{ $ministry->isPastoral() ? 'fa-user-tie' : 'fa-people-group' }}"></i></span>
                        <span>
                            <strong>{{ $ministry->name }}</strong>
                            <small>{{ trans_choice(':count member|:count members', $ministry->members_count, ['count' => $ministry->members_count]) }} · {{ trans_choice(':count role|:count roles', $ministry->roles_count, ['count' => $ministry->roles_count]) }}</small>
                            @if ($ministry->isPastoral())
                                <span class="m-tags"><span class="m-tag is-ok"><i class="fa-solid fa-globe"></i> {{ __('On the site: Pastors') }}</span></span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
