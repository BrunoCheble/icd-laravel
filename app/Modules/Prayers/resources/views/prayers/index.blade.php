<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Prayer Requests') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        /* The requests: a card each, newest first, read left to right (one column on phones) */
        .prayers { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(22rem, 100%), 1fr)); gap: 1rem; align-items: start; margin: .75rem 0 1rem; }
        .prayer { padding: 1rem 1rem 1.1rem; }
        .prayer.is-new { border-color: var(--brand-200); box-shadow: 0 0 0 3px var(--brand-soft); }
        .prayer-head { display: flex; align-items: center; gap: .7rem; }
        .prayer-who { flex: 1; min-width: 0; }
        .prayer-who strong { display: block; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .prayer-who small { display: block; font-size: .8rem; color: #6b7280; }
        .prayer-text { margin: .75rem 0 0; color: #374151; font-size: .95rem; line-height: 1.55; white-space: pre-line; overflow-wrap: anywhere; }
        .prayer-text.is-empty { color: #9ca3af; font-style: italic; }
        .prayers-empty { padding: 2.5rem 1rem; text-align: center; color: #6b7280; }
        .prayers-empty i { display: block; margin-bottom: .5rem; font-size: 1.6rem; color: #d1d5db; }
    </style>

    @php
        $period = $filters['period'] ?? 'all';
        $search = $filters['search'] ?? '';
        $periods = ['today' => __('Today'), 'week' => __('This week'), 'month' => __('This month'), 'all' => __('All')];
        $initials = function (string $name) {
            $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
            return mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
        };
        // "Today, 10:12", "Yesterday, 18:40", "Mon, 12/10 · 09:00"
        $when = function ($date) {
            $date = \Carbon\Carbon::parse($date)->timezone(config('app.timezone'))->locale(app()->getLocale());
            $day = $date->isToday() ? __('Today') : ($date->isYesterday() ? __('Yesterday') : \Illuminate\Support\Str::ucfirst(rtrim($date->isoFormat('ddd'), '.')) . ', ' . $date->format('d/m' . ($date->isCurrentYear() ? '' : '/Y')));
            return $day . ' · ' . $date->format('H:i');
        };
    @endphp

    <div class="m-page" x-data="{ prayer: null, confirming: false }" @keydown.escape.window="prayer = null">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Prayer Requests') }}</h1>
                <p>{{ trans_choice(':count request|:count requests', $prayers->total(), ['count' => $prayers->total()]) }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('site.todayPrayer') }}" target="_blank" rel="noopener" class="m-btn"><i class="fa-solid fa-display"></i> <span class="m-hide-sm">{{ __('Today on screen') }}</span></a>
            </div>
        </div>

        {{-- The period at hand --}}
        <nav class="m-status" aria-label="{{ __('Period') }}">
            @foreach ($periods as $value => $label)
                <a href="{{ route('prayers.index', array_filter(['period' => $value === 'all' ? null : $value, 'search' => $search])) }}" @class(['is-active' => $period === $value])>{{ $label }} <small>{{ $periodCounts[$value] }}</small></a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('prayers.index') }}" class="m-toolbar">
            @if ($period !== 'all')<input type="hidden" name="period" value="{{ $period }}">@endif
            <label class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Name or request') }}" aria-label="{{ __('Search') }}">
            </label>
        </form>
        @if ($search !== '')
            <div class="m-chips">
                <a class="m-chip" href="{{ route('prayers.index', array_filter(['period' => $period === 'all' ? null : $period])) }}" title="{{ __('Remove') }}">{{ __('Search') }}: "{{ $search }}" <i class="fa-solid fa-xmark"></i></a>
            </div>
        @endif

        @if ($prayers->isEmpty())
            <div class="m-card prayers-empty mt-3"><i class="fa-solid fa-hands-praying"></i>{{ $search !== '' ? __('No requests match this search.') : __('No prayer requests in this period.') }}</div>
        @else
            <div class="prayers">
                @foreach ($prayers as $prayer)
                    <article @class(['m-card prayer', 'is-new' => $prayer->created_at?->isToday()])>
                        <div class="prayer-head">
                            <span class="m-initials is-lg">{{ $initials($prayer->name) }}</span>
                            <div class="prayer-who">
                                <strong>{{ $prayer->name }}</strong>
                                <small>{{ $when($prayer->created_at) }}@if ($prayer->phone_number) · <i class="fa-brands fa-whatsapp"></i> {{ $prayer->phone_number }}@endif</small>
                            </div>
                            <button type="button" class="m-btn is-ghost is-icon" aria-label="{{ __('Actions') }}: {{ $prayer->name }}"
                                @click="prayer = @js(['name' => $prayer->name, 'request' => (string) $prayer->request, 'when' => $when($prayer->created_at), 'phone' => $prayer->phone_number,
                                    'whatsapp' => \App\Helpers\WhatsAppHelper::url($prayer->phone_number, 'A paz, ' . \Illuminate\Support\Str::before(trim($prayer->name), ' ') . '! Recebemos o seu pedido de oração e estamos a orar por si. 🙏'),
                                    'destroy' => route('prayers.destroy', $prayer)]); confirming = false"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        </div>
                        @if (trim((string) $prayer->request) !== '')
                            <p class="prayer-text">{{ $prayer->request }}</p>
                        @else
                            <p class="prayer-text is-empty">{{ __('No request written; pray for this person.') }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
            @if ($prayers->hasPages())
                <div class="m-card m-pages">{!! $prayers->links() !!}</div>
            @endif
        @endif

        {{-- Actions of a request: copy it, delete it --}}
        <template x-teleport="body">
            <div class="m-sheet" x-show="prayer" x-cloak role="dialog" aria-modal="true" :aria-label="prayer?.name">
                <div class="m-sheet-overlay" @click="prayer = null"></div>
                <div class="m-sheet-box" x-show="prayer" x-transition.opacity>
                    <div class="m-sheet-head">
                        <div>
                            <h2 x-text="prayer?.name"></h2>
                            <p x-text="prayer?.when"></p>
                        </div>
                        <button type="button" class="m-btn is-ghost is-icon" @click="prayer = null" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="m-menu" x-show="!confirming">
                        <a x-show="prayer?.whatsapp" :href="prayer?.whatsapp" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" style="color: #25d366;"></i> {{ __('Answer on WhatsApp') }}</a>
                        <button type="button" x-data="{ copied: false }" @click="navigator.clipboard?.writeText(prayer.name + (prayer.request ? ': ' + prayer.request : '')); copied = true; setTimeout(() => copied = false, 2000)">
                            <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i> <span x-text="copied ? @js(__('Copied')) : @js(__('Copy request'))"></span>
                        </button>
                        <button type="button" class="is-danger" @click="confirming = true"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                    </div>
                    <form method="POST" :action="prayer?.destroy" x-show="confirming">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm text-gray-700">{{ __('Delete this prayer request? It cannot be undone.') }}</p>
                        <div class="m-sheet-foot">
                            <button type="button" class="m-btn" @click="confirming = false">{{ __('Cancel') }}</button>
                            <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
