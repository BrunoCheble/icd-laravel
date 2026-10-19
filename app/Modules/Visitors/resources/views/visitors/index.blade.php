<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Visitors') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        /* Headings splitting the list by the period of the visit */
        .m-group-row td, div.m-group-row { padding: .5rem .9rem; background: #f9fafb; border-bottom: 1px solid #f1f2f4; font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--brand-text); }
        .m-table tbody tr.m-group-row:hover { background: none; }
    </style>

    @php
        $sort = $filters['sort'] ?? 'created';
        $direction = $filters['direction'] ?? ($sort === 'created' ? 'desc' : 'asc');
        $status = $filters['status'] ?? '';
        $hasFilters = collect($filters)->except(['sort', 'direction'])->filter()->isNotEmpty();
        $genders = \App\Enums\Gender::options();
        $groups = \App\Enums\VisitorGroup::options();
        $statuses = \App\Enums\VisitorStatus::options();
        // Header link: sorts by the column, reversing the direction when it is already the sort.
        $sortUrl = fn (string $column) => route('visitors.index', array_merge(request()->except('page'), [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
        $sortArrow = fn (string $column) => new \Illuminate\Support\HtmlString(' <i class="fa-solid ' . ($sort === $column ? ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort" style="opacity: .35;') . '"></i>');
        $sortOptions = ['created' => __('Created At'), 'name' => __('Name'), 'city' => __('City'), 'group' => __('Group')];
        // Filters of the sheet, each one removable: "Group: Youth".
        $activeFilters = collect([
            'status' => [__('Status'), $statuses],
            'gender' => [__('Gender'), $genders],
            'group' => [__('Group'), $groups],
        ])
            ->filter(fn ($filter, $name) => ! empty($filters[$name]))
            ->map(fn ($filter, $name) => [
                'text' => $filter[0] . ': ' . ($filter[1][$filters[$name]] ?? $filters[$name]),
                'url' => route('visitors.index', request()->except(['page', $name])),
            ]);
        $tagOf = ['actived' => 'is-ok', 'inactived' => 'is-danger'];
        $period = $filters['period'] ?? '';
        $periods = ['week' => __('This week'), 'month' => __('This month'), 'year' => __('This year'), '' => __('All')];
        // Headings in the list when it shows every period by visit date, newest first.
        $groupHeadings = $period === '' && $sort === 'created' && $direction === 'desc'
            ? ['week' => __('This week'), 'month' => __('Earlier this month'), 'year' => __('Earlier this year'), 'older' => __('Before this year')]
            : null;
        $groupOf = fn ($visitor) => \App\Modules\Visitors\Services\ListVisitorsService::periodOf($visitor->created_at);
    @endphp

    <div class="m-page" x-data="{ filtersOpen: false }" @keydown.escape.window="filtersOpen = false">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Visitors') }}</h1>
                <p>{{ trans_choice(':count visitor|:count visitors', $visitors->total(), ['count' => $visitors->total()]) }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('visitors.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('Add new') }}</a>
            </div>
        </div>

        {{-- When they visited: this week, this month, this year, all (status is in the filters) --}}
        <nav class="m-status" aria-label="{{ __('Period') }}">
            @foreach ($periods as $value => $label)
                <a href="{{ route('visitors.index', array_merge(request()->except(['page', 'period']), $value === '' ? [] : ['period' => $value])) }}" @class(['is-active' => $period === $value])>{{ $label }} <small>{{ $periodCounts[$value === '' ? 'all' : $value] }}</small></a>
            @endforeach
        </nav>

        {{-- Search (always at hand) and the filters --}}
        <form method="GET" action="{{ route('visitors.index') }}" class="m-toolbar">
            @foreach (request()->except(['search', 'page']) as $name => $value)
                @if (is_string($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
            @endforeach
            <label class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Name, phone, city or who invited') }}" aria-label="{{ __('Search') }}">
            </label>
            <button type="button" class="m-btn" @click="filtersOpen = true">
                <i class="fa-solid fa-sliders"></i> <span class="m-hide-sm">{{ __('Filter') }}</span>
                @if ($activeFilters->count())<span class="m-count">{{ $activeFilters->count() }}</span>@endif
            </button>
        </form>

        @if (($filters['search'] ?? '') !== '' || $activeFilters->isNotEmpty())
            <div class="m-chips">
                @if (($filters['search'] ?? '') !== '')
                    <a class="m-chip" href="{{ route('visitors.index', request()->except(['page', 'search'])) }}" title="{{ __('Remove') }}">{{ __('Search') }}: "{{ $filters['search'] }}" <i class="fa-solid fa-xmark"></i></a>
                @endif
                @foreach ($activeFilters as $chip)
                    <a class="m-chip" href="{{ $chip['url'] }}" title="{{ __('Remove') }}">{{ $chip['text'] }} <i class="fa-solid fa-xmark"></i></a>
                @endforeach
                <a href="{{ route('visitors.index', request()->only(['period', 'sort', 'direction'])) }}" class="m-link">{{ __('Clear Filters') }}</a>
            </div>
        @endif

        <div class="m-card m-list mt-3">
            @if ($visitors->isEmpty())
                <div class="m-empty"><i class="fa-solid fa-hand"></i>{{ $hasFilters ? __('No visitors match these filters.') : __('No visitors yet.') }}</div>
            @else
                <table class="m-table">
                    <thead>
                        <tr>
                            <th><a href="{{ $sortUrl('name') }}" @class(['is-sorted' => $sort === 'name'])>{{ __('Name') }}{{ $sortArrow('name') }}</a></th>
                            <th>{{ __('Phone Number') }}</th>
                            <th><a href="{{ $sortUrl('city') }}" @class(['is-sorted' => $sort === 'city'])>{{ __('City') }}{{ $sortArrow('city') }}</a></th>
                            <th><a href="{{ $sortUrl('group') }}" @class(['is-sorted' => $sort === 'group'])>{{ __('Group') }}{{ $sortArrow('group') }}</a></th>
                            <th>{{ __('Invited by') }}</th>
                            <th>{{ __('Registered by') }}</th>
                            <th><a href="{{ $sortUrl('created') }}" @class(['is-sorted' => $sort === 'created'])>{{ __('Visit') }}{{ $sortArrow('created') }}</a></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visitors as $visitor)
                            @if ($groupHeadings && ($loop->first || $groupOf($visitors[$loop->index - 1]) !== $groupOf($visitor)))
                                <tr class="m-group-row"><td colspan="8">{{ $groupHeadings[$groupOf($visitor)] }}</td></tr>
                            @endif
                            <tr>
                                <td>
                                    <a href="{{ route('visitors.edit', $visitor) }}" class="m-name">
                                        <span class="m-initials">{{ $visitor->initials }}</span>
                                        <span>
                                            <strong>{{ $visitor->name }}</strong>
                                            <small class="m-tags">
                                                {{ $visitor->gender_name }}
                                                @unless ($visitor->isActive())<span class="m-tag {{ $tagOf[$visitor->status] ?? 'is-off' }}">{{ $statuses[$visitor->status] ?? $visitor->status }}</span>@endunless
                                            </small>
                                        </span>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap">{{ $visitor->phone_number ?: '—' }}</td>
                                <td>{{ $visitor->city ?: '—' }}</td>
                                <td>@if ($visitor->group)<span class="m-tag is-media">{{ $visitor->group_name }}</span>@else — @endif</td>
                                <td>{{ $visitor->invited_by_name ?: '—' }}</td>
                                <td>{{ $visitor->created_by ?: '—' }}</td>
                                <td class="whitespace-nowrap">{{ $visitor->created_at?->format('d/m/Y') }}</td>
                                <td style="text-align: right;">@include('visitors::visitors.partials.actions-button')</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Phones and tablets --}}
                <div class="m-rows">
                    @foreach ($visitors as $visitor)
                        @if ($groupHeadings && ($loop->first || $groupOf($visitors[$loop->index - 1]) !== $groupOf($visitor)))
                            <div class="m-group-row">{{ $groupHeadings[$groupOf($visitor)] }}</div>
                        @endif
                        <div class="m-row">
                            <a href="{{ route('visitors.edit', $visitor) }}" class="m-row-main">
                                <span class="m-initials is-lg">{{ $visitor->initials }}</span>
                                <span class="m-row-text">
                                    <strong>{{ $visitor->name }}</strong>
                                    <small>{{ collect([$visitor->created_at?->format('d/m/Y'), $visitor->city, $visitor->invited_by_name ? __('Invited by') . ' ' . $visitor->invited_by_name : null])->filter()->implode(' · ') }}</small>
                                    @if ($visitor->group || ! $visitor->isActive())
                                        <span class="m-tags">
                                            @if ($visitor->group)<span class="m-tag is-media">{{ $visitor->group_name }}</span>@endif
                                            @unless ($visitor->isActive())<span class="m-tag {{ $tagOf[$visitor->status] ?? 'is-off' }}">{{ $statuses[$visitor->status] ?? $visitor->status }}</span>@endunless
                                        </span>
                                    @endif
                                </span>
                            </a>
                            @include('visitors::visitors.partials.actions-button', ['icon' => 'fa-ellipsis-vertical'])
                        </div>
                    @endforeach
                </div>

                @if ($visitors->hasPages())
                    <div class="m-pages">{!! $visitors->links() !!}</div>
                @endif
            @endif
        </div>

        @include('visitors::visitors.partials.actions')

        {{-- Filters and order --}}
        <div class="m-sheet" x-show="filtersOpen" x-cloak role="dialog" aria-modal="true" aria-label="{{ __('Filter') }}">
            <div class="m-sheet-overlay" @click="filtersOpen = false"></div>
            <form method="GET" action="{{ route('visitors.index') }}" class="m-sheet-box" x-show="filtersOpen" x-transition.opacity>
                <div class="m-sheet-head">
                    <div>
                        <h2>{{ __('Filter') }}</h2>
                        <p>{{ __('Show only the visitors you need, in the order you want.') }}</p>
                    </div>
                    <button type="button" class="m-btn is-ghost is-icon" @click="filtersOpen = false" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="m-grid">
                    <div class="m-field">
                        <label class="m-label" for="filter_search">{{ __('Search') }}</label>
                        <input id="filter_search" name="search" type="search" class="m-input" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Name, phone, city or who invited') }}">
                    </div>
                    <div class="m-field">
                        <label class="m-label" for="filter_status">{{ __('Status') }}</label>
                        <select id="filter_status" name="status" class="m-input">
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
                        <label class="m-label" for="filter_group">{{ __('Group') }}</label>
                        <select id="filter_group" name="group" class="m-input">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($groups as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['group'] ?? '') === $value)>{{ $label }}</option>
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
                    <a href="{{ route('visitors.index') }}" class="m-btn is-ghost">{{ __('Clear Filters') }}</a>
                    <button type="submit" class="m-btn is-primary">{{ __('Apply Filters') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
