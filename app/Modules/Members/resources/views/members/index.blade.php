<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Members') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        [x-cloak] { display: none !important; }
        /* Quick status choice over the list */
        .members-status { display: flex; gap: .35rem; margin-bottom: .75rem; overflow-x: auto; scrollbar-width: none; }
        .members-status::-webkit-scrollbar { display: none; }
        .members-status a { display: inline-flex; align-items: center; gap: .4rem; min-height: 36px; padding: 0 .8rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #fff; color: #4b5563; font-size: .85rem; font-weight: 600; white-space: nowrap; text-decoration: none; }
        .members-status a:hover { border-color: #d1d5db; color: #111827; }
        .members-status a.is-active { border-color: var(--brand); background: var(--brand-soft); color: var(--brand-strong); }
        .members-status small { font-size: .75rem; font-weight: 700; color: #9ca3af; }
        .members-status a.is-active small { color: var(--brand-text); }

        /* Large screens: a table; phones and tablets: a card per member */
        .members-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .members-table th { padding: .65rem .75rem; border-bottom: 1px solid #e5e7eb; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; text-align: left; white-space: nowrap; }
        .members-table th a { color: inherit; text-decoration: none; }
        .members-table th a:hover, .members-table th a.is-sorted { color: #111827; }
        .members-table td { padding: .6rem .75rem; border-bottom: 1px solid #f1f2f4; font-size: .875rem; color: #4b5563; vertical-align: middle; }
        .members-table tbody tr { transition: background .1s; }
        .members-table tbody tr:hover { background: #fafafa; }
        .member-name { display: flex; align-items: center; gap: .7rem; min-width: 0; text-decoration: none; }
        .member-name strong { display: block; font-weight: 700; color: #111827; }
        .member-name:hover strong { color: var(--brand-text); }
        .member-name small { display: block; font-size: .8rem; color: #6b7280; }
        .member-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
        .m-tag.is-danger { background: #fee2e2; color: #991b1b; }

        .member-cards { display: none; }
        .member-card { display: flex; align-items: center; gap: .75rem; padding: .8rem .9rem; border-bottom: 1px solid #f1f2f4; }
        .member-card:last-child { border-bottom: 0; }
        .member-card-main { flex: 1; min-width: 0; display: flex; align-items: center; gap: .75rem; text-decoration: none; }
        .member-card-text { min-width: 0; }
        .member-card-text strong { display: block; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .member-card-text small { display: block; margin-top: .05rem; font-size: .82rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .member-card .member-tags { margin-top: .35rem; }
        @media (max-width: 1023px) {
            .members-table { display: none; }
            .member-cards { display: block; }
        }
        .members-list { overflow: hidden; }
        .members-empty { padding: 2.5rem 1rem; text-align: center; color: #6b7280; }
        .members-empty i { display: block; margin-bottom: .5rem; font-size: 1.6rem; color: #d1d5db; }
        .members-pages { padding: .75rem 1rem; border-top: 1px solid #f1f2f4; }
    </style>

    @php
        $sort = $filters['sort'] ?? 'created';
        $direction = $filters['direction'] ?? ($sort === 'created' ? 'desc' : 'asc');
        $status = $filters['membership_status'] ?? '';
        $hasFilters = collect($filters)->except(['sort', 'direction'])->filter()->isNotEmpty();
        $genders = \App\Enums\Gender::options();
        $maritalStatus = \App\Enums\MaritalStatus::options();
        $statuses = \App\Enums\MembershipStatus::options();
        // Header link: sorts by the column, reversing the direction when it is already the sort.
        $sortUrl = fn (string $column) => route('members.index', array_merge(request()->except('page'), [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
        $sortArrow = fn (string $column) => new \Illuminate\Support\HtmlString(' <i class="fa-solid ' . ($sort === $column ? ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort" style="opacity: .35;') . '"></i>');
        $sortOptions = ['created' => __('Created At'), 'name' => __('Name'), 'birth' => __('Date of Birth'), 'city' => __('City'), 'status' => __('Status')];
        // Filters of the sheet, each one removable: "Gender: Female".
        $activeFilters = collect([
            'gender' => [__('Gender'), $genders],
            'marital_status' => [__('Marital Status'), $maritalStatus],
        ])
            ->filter(fn ($filter, $name) => ! empty($filters[$name]))
            ->map(fn ($filter, $name) => [
                'text' => $filter[0] . ': ' . ($filter[1][$filters[$name]] ?? $filters[$name]),
                'url' => route('members.index', request()->except(['page', $name])),
            ]);
        $tagOf = ['actived' => 'is-ok', 'pending' => 'is-part', 'inactived' => 'is-danger'];
        $total = array_sum($statusCounts);
    @endphp

    <div class="m-page" x-data="{ filtersOpen: false }" @keydown.escape.window="filtersOpen = false">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Members') }}</h1>
                <p>{{ trans_choice(':count member|:count members', $members->total(), ['count' => $members->total()]) }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('members.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('Add new') }}</a>
            </div>
        </div>

        {{-- Status at hand: all, active, pending (registered on the site), inactive --}}
        <nav class="members-status" aria-label="{{ __('Status') }}">
            <a href="{{ route('members.index', request()->except(['page', 'membership_status'])) }}" @class(['is-active' => $status === ''])>{{ __('All') }} <small>{{ $total }}</small></a>
            @foreach ($statuses as $value => $label)
                <a href="{{ route('members.index', array_merge(request()->except('page'), ['membership_status' => $value])) }}" @class(['is-active' => $status === $value])>{{ $label }} <small>{{ $statusCounts[$value] ?? 0 }}</small></a>
            @endforeach
        </nav>

        {{-- Search (always at hand) and the filters --}}
        <form method="GET" action="{{ route('members.index') }}" class="m-toolbar">
            @foreach (request()->except(['search', 'page']) as $name => $value)
                @if (is_string($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
            @endforeach
            <label class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Name, NIF, phone or email') }}" aria-label="{{ __('Search') }}">
            </label>
            <button type="button" class="m-btn" @click="filtersOpen = true">
                <i class="fa-solid fa-sliders"></i> <span class="m-hide-sm">{{ __('Filter') }}</span>
                @if ($activeFilters->count())<span class="m-count">{{ $activeFilters->count() }}</span>@endif
            </button>
        </form>

        @if (($filters['search'] ?? '') !== '' || $activeFilters->isNotEmpty())
            <div class="m-chips">
                @if (($filters['search'] ?? '') !== '')
                    <a class="m-chip" href="{{ route('members.index', request()->except(['page', 'search'])) }}" title="{{ __('Remove') }}">{{ __('Search') }}: "{{ $filters['search'] }}" <i class="fa-solid fa-xmark"></i></a>
                @endif
                @foreach ($activeFilters as $chip)
                    <a class="m-chip" href="{{ $chip['url'] }}" title="{{ __('Remove') }}">{{ $chip['text'] }} <i class="fa-solid fa-xmark"></i></a>
                @endforeach
                <a href="{{ route('members.index', request()->only(['membership_status', 'sort', 'direction'])) }}" class="m-link">{{ __('Clear Filters') }}</a>
            </div>
        @endif

        <div class="m-card members-list mt-3">
            @if ($members->isEmpty())
                <div class="members-empty"><i class="fa-solid fa-users"></i>{{ $hasFilters ? __('No members match these filters.') : __('No members yet.') }}</div>
            @else
                <table class="members-table">
                    <thead>
                        <tr>
                            <th><a href="{{ $sortUrl('name') }}" @class(['is-sorted' => $sort === 'name'])>{{ __('Name') }}{{ $sortArrow('name') }}</a></th>
                            <th>{{ __('Document Number') }}</th>
                            <th>{{ __('Phone Number') }}</th>
                            <th><a href="{{ $sortUrl('city') }}" @class(['is-sorted' => $sort === 'city'])>{{ __('City') }}{{ $sortArrow('city') }}</a></th>
                            <th><a href="{{ $sortUrl('birth') }}" @class(['is-sorted' => $sort === 'birth'])>{{ __('Age') }}{{ $sortArrow('birth') }}</a></th>
                            <th>{{ __('Marital Status') }}</th>
                            <th><a href="{{ $sortUrl('status') }}" @class(['is-sorted' => $sort === 'status'])>{{ __('Status') }}{{ $sortArrow('status') }}</a></th>
                            <th><a href="{{ $sortUrl('created') }}" @class(['is-sorted' => $sort === 'created'])>{{ __('Created At') }}{{ $sortArrow('created') }}</a></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td>
                                    <a href="{{ route('members.show', $member) }}" class="member-name">
                                        @include('members::members.partials.avatar', ['class' => 'w-9 h-9'])
                                        <span>
                                            <strong>{{ $member->first_and_last_name }}</strong>
                                            <small>{{ $member->email ?: '—' }}</small>
                                        </span>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap">{{ $member->document_number ?: '—' }}</td>
                                <td class="whitespace-nowrap">{{ $member->phone_number ?: '—' }}</td>
                                <td>{{ $member->city ?: '—' }}</td>
                                <td class="whitespace-nowrap">{{ $member->years() ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $member->marital_status_name }}</td>
                                <td>
                                    <div class="member-tags">
                                        <span class="m-tag {{ $tagOf[$member->membership_status] ?? 'is-off' }}">{{ $statuses[$member->membership_status] ?? $member->membership_status }}</span>
                                        @if ($member->pendingInformations)
                                            <span class="m-tag is-part" title="{{ implode(' · ', $member->pendingInformations) }}"><i class="fa-solid fa-circle-exclamation"></i> {{ count($member->pendingInformations) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">{{ $member->created_at?->format('d/m/Y') }}</td>
                                <td style="text-align: right;">@include('members::members.partials.actions-button')</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Phones and tablets --}}
                <div class="member-cards">
                    @foreach ($members as $member)
                        <div class="member-card">
                            <a href="{{ route('members.show', $member) }}" class="member-card-main">
                                @include('members::members.partials.avatar', ['class' => 'w-11 h-11'])
                                <span class="member-card-text">
                                    <strong>{{ $member->first_and_last_name }}</strong>
                                    <small>{{ collect([$member->phone_number, $member->city])->filter()->implode(' · ') ?: '—' }}</small>
                                    <span class="member-tags">
                                        @unless ($member->isActive())
                                            <span class="m-tag {{ $tagOf[$member->membership_status] ?? 'is-off' }}">{{ $statuses[$member->membership_status] ?? $member->membership_status }}</span>
                                        @endunless
                                        @if ($member->pendingInformations)
                                            <span class="m-tag is-part"><i class="fa-solid fa-circle-exclamation"></i> {{ count($member->pendingInformations) }}</span>
                                        @endif
                                    </span>
                                </span>
                            </a>
                            @include('members::members.partials.actions-button', ['icon' => 'fa-ellipsis-vertical'])
                        </div>
                    @endforeach
                </div>

                @if ($members->hasPages())
                    <div class="members-pages">{!! $members->links() !!}</div>
                @endif
            @endif
        </div>

        @include('members::members.partials.actions')

        {{-- Filters and order --}}
        <div class="m-sheet" x-show="filtersOpen" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('Filter') }}">
            <div class="m-sheet-overlay" @click="filtersOpen = false"></div>
            <form method="GET" action="{{ route('members.index') }}" class="m-sheet-box" x-show="filtersOpen" x-transition.opacity>
                <div class="m-sheet-head">
                    <div>
                        <h2>{{ __('Filter') }}</h2>
                        <p>{{ __('Show only the members you need, in the order you want.') }}</p>
                    </div>
                    <button type="button" class="m-btn is-ghost is-icon" @click="filtersOpen = false" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="m-grid">
                    <div class="m-field">
                        <label class="m-label" for="filter_search">{{ __('Search') }}</label>
                        <input id="filter_search" name="search" type="search" class="m-input" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Name, NIF, phone or email') }}">
                    </div>
                    <div class="m-field">
                        <label class="m-label" for="filter_status">{{ __('Status') }}</label>
                        <select id="filter_status" name="membership_status" class="m-input">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-field">
                        <span class="m-label">{{ __('Gender') }}</span>
                        <div class="m-seg" role="radiogroup" aria-label="{{ __('Gender') }}">
                            @foreach (['' => __('All')] + $genders as $value => $text)
                                <label><input type="radio" name="gender" value="{{ $value }}" @checked(($filters['gender'] ?? '') === (string) $value)><span>{{ $text }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="m-field">
                        <label class="m-label" for="filter_marital">{{ __('Marital Status') }}</label>
                        <select id="filter_marital" name="marital_status" class="m-input">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($maritalStatus as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['marital_status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-grid cols-2">
                        <div class="m-field">
                            <label class="m-label" for="filter_sort">{{ __('Sort by') }}</label>
                            <select id="filter_sort" name="sort" class="m-input">
                                @foreach ($sortOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="m-field">
                            <span class="m-label">{{ __('Order by') }}</span>
                            <div class="m-seg">
                                <label><input type="radio" name="direction" value="asc" @checked($direction === 'asc')><span><i class="fa-solid fa-arrow-up-short-wide"></i> {{ __('Ascending') }}</span></label>
                                <label><input type="radio" name="direction" value="desc" @checked($direction === 'desc')><span><i class="fa-solid fa-arrow-down-wide-short"></i> {{ __('Descending') }}</span></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="m-sheet-foot">
                    <a href="{{ route('members.index') }}" class="m-btn is-ghost">{{ __('Clear Filters') }}</a>
                    <button type="submit" class="m-btn is-primary">{{ __('Apply Filters') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
