<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Announcements') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        /* A card per announcement, newest first, read left to right (one column on phones) */
        .ads { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(19rem, 100%), 1fr)); gap: 1rem; align-items: start; margin: .75rem 0 1rem; }
        .ad { overflow: hidden; display: flex; flex-direction: column; }
        .ad-media { position: relative; display: grid; place-items: center; aspect-ratio: 16 / 10; background: linear-gradient(135deg, var(--brand-soft), var(--bg-fff7ed)); color: var(--brand-hover); font-size: 2rem; text-decoration: none; }
        .ad-media.is-plain { aspect-ratio: auto; height: 4.5rem; font-size: 1.4rem; }
        .ad-media img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .ad-media small { display: block; margin-top: .25rem; font-size: .75rem; font-weight: 700; text-align: center; }
        .ad-media .m-tag { position: absolute; top: .6rem; left: .6rem; box-shadow: 0 1px 2px rgba(0, 0, 0, .15); }
        .ad-body { padding: .85rem 1rem 1rem; }
        .ad-head { display: flex; align-items: flex-start; gap: .5rem; }
        .ad-head h3 { flex: 1; min-width: 0; margin: 0; font-size: 1rem; font-weight: 800; color: var(--tx-111827); line-height: 1.3; }
        .ad-text { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin: .4rem 0 0; font-size: .88rem; line-height: 1.45; color: var(--tx-4b5563); white-space: pre-line; }
        .ad-foot { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .75rem; margin-top: .75rem; font-size: .8rem; color: var(--tx-6b7280); }
        .ad-foot i { margin-right: .25rem; color: var(--tx-9ca3af); }
        .ad-empty { padding: 2.5rem 1rem; text-align: center; color: var(--tx-6b7280); }
        .ad-empty i { display: block; margin-bottom: .5rem; font-size: 1.6rem; color: var(--tx-d1d5db); }
        .ad-detail { margin: 0 0 1rem; font-size: .92rem; line-height: 1.5; color: var(--tx-374151); white-space: pre-line; overflow-wrap: anywhere; }
        .ad-facts { display: grid; margin: 0 0 1rem; }
        .ad-facts > div { display: grid; grid-template-columns: 7rem minmax(0, 1fr); gap: .75rem; padding: .45rem 0; border-bottom: 1px solid var(--bd-f3f4f6); }
        .ad-facts dt { font-size: .82rem; font-weight: 600; color: var(--tx-6b7280); }
        .ad-facts dd { margin: 0; font-size: .9rem; color: var(--tx-111827); overflow-wrap: anywhere; }
        .ad-facts a { color: var(--brand-text); }
    </style>

    @php
        $status = $filters['status'] ?? '';
        $search = $filters['search'] ?? '';
        $types = \App\Enums\AnnouncementType::options();
        $icons = ['resume' => 'fa-file-lines', 'service' => 'fa-screwdriver-wrench', 'donation' => 'fa-hand-holding-heart', 'product' => 'fa-tag'];
        $statuses = ['pending' => [__('Waiting for approval'), 'is-part'], 'published' => [__('Published'), 'is-ok'], 'expired' => [__('Expired'), 'is-off']];
        $tabs = ['' => __('All'), 'pending' => __('To approve'), 'published' => __('Published'), 'expired' => __('Expired')];
        $day = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null;
    @endphp

    <div class="m-page" x-data="{ ad: null, panel: 'menu' }" @keydown.escape.window="ad = null">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Announcements') }}</h1>
                <p>{{ trans_choice(':count announcement|:count announcements', $announcements->total(), ['count' => $announcements->total()]) }}</p>
            </div>
            <div class="m-head-actions">
                <a href="{{ route('announcements.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('Add new') }}</a>
            </div>
        </div>

        {{-- Status at hand: the ones to approve first --}}
        <nav class="m-status" aria-label="{{ __('Status') }}">
            @foreach ($tabs as $value => $label)
                <a href="{{ route('announcements.index', array_filter(['status' => $value, 'type' => $filters['type'] ?? null, 'search' => $search])) }}" @class(['is-active' => $status === $value])>
                    {{ $label }} <small>{{ $value === '' ? array_sum($statusCounts) : $statusCounts[$value] }}</small>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('announcements.index') }}" class="m-toolbar">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Title, text or member') }}" aria-label="{{ __('Search') }}">
            </label>
            <select name="type" class="m-input" style="width: auto; min-height: 40px;" onchange="this.form.submit()" aria-label="{{ __('Type') }}">
                <option value="">{{ __('All types') }}</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        @if ($search !== '')
            <div class="m-chips">
                <a class="m-chip" href="{{ route('announcements.index', array_filter(['status' => $status, 'type' => $filters['type'] ?? null])) }}" title="{{ __('Remove') }}">{{ __('Search') }}: "{{ $search }}" <i class="fa-solid fa-xmark"></i></a>
            </div>
        @endif

        @if ($announcements->isEmpty())
            <div class="m-card ad-empty mt-3"><i class="fa-solid fa-bullhorn"></i>{{ $search !== '' || ! empty($filters['type']) ? __('No announcements match these filters.') : __('No announcements here.') }}</div>
        @else
            <div class="ads">
                @foreach ($announcements as $announcement)
                    @php
                        [$statusLabel, $statusTag] = $statuses[$announcement->status()];
                        $ad = [
                            'title' => $announcement->title,
                            'type' => $announcement->type_name,
                            'description' => $announcement->description,
                            'member' => $announcement->member?->first_and_last_name,
                            'contact' => $announcement->contact,
                            'whatsapp' => \App\Helpers\WhatsAppHelper::url($announcement->contact),
                            'link' => $announcement->link,
                            'file' => $announcement->url_image,
                            'status' => $announcement->status(),
                            'statusLabel' => $statusLabel,
                            'created' => $announcement->created_at?->format('d/m/Y'),
                            'expires' => $day($announcement->expires_at),
                            'edit' => route('announcements.edit', $announcement),
                            'approval' => route('announcements.approval', $announcement),
                            'destroy' => route('announcements.destroy', $announcement),
                        ];
                    @endphp
                    <article class="m-card ad">
                        <a href="{{ route('announcements.edit', $announcement) }}" class="ad-media" aria-label="{{ $announcement->title }}">
                            <img src="{{ $announcement->picture_url }}" alt="" loading="lazy">
                            @if ($announcement->isPdf())<span class="m-tag is-media" style="position: absolute; right: .6rem; top: .6rem; left: auto;"><i class="fa-solid fa-file-pdf"></i> PDF</span>@endif
                            <span class="m-tag {{ $statusTag }}">{{ $statusLabel }}</span>
                        </a>
                        <div class="ad-body">
                            <div class="ad-head">
                                <h3>{{ $announcement->title }}</h3>
                                <button type="button" class="m-btn is-ghost is-icon" style="margin: -.4rem -.5rem 0 0;" aria-label="{{ __('Actions') }}: {{ $announcement->title }}" @click="ad = @js($ad); panel = 'menu'"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            </div>
                            <p class="ad-text">{{ $announcement->description }}</p>
                            <div class="ad-foot">
                                <span><i class="fa-solid {{ $icons[$announcement->type] ?? 'fa-bullhorn' }}"></i>{{ $announcement->type_name }}</span>
                                @if ($announcement->member)<span><i class="fa-solid fa-user"></i>{{ $announcement->member->first_and_last_name }}</span>@endif
                                @if ($announcement->expires_at)<span><i class="fa-regular fa-calendar"></i>{{ $announcement->status() === 'expired' ? __('Expired on :date', ['date' => $day($announcement->expires_at)]) : __('Until :date', ['date' => $day($announcement->expires_at)]) }}</span>@endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($announcements->hasPages())
                <div class="m-card m-pages">{!! $announcements->links() !!}</div>
            @endif
        @endif

        {{-- Actions of an announcement: see it all, approve, edit, delete --}}
        <template x-teleport="body">
            <div class="m-sheet" x-show="ad" x-cloak role="dialog" aria-modal="true" :aria-label="ad?.title">
                <div class="m-sheet-overlay" @click="ad = null"></div>
                <div class="m-sheet-box" x-show="ad" x-transition.opacity>
                    <div class="m-sheet-head">
                        <div>
                            <h2 x-text="ad?.title"></h2>
                            <p x-text="[ad?.type, ad?.statusLabel].filter(Boolean).join(' · ')"></p>
                        </div>
                        <button type="button" class="m-btn is-ghost is-icon" @click="ad = null" aria-label="{{ __('Close') }}"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <div x-show="panel === 'menu'">
                        <p class="ad-detail" x-text="ad?.description"></p>
                        <dl class="ad-facts">
                            <div x-show="ad?.member"><dt>{{ __('Member') }}</dt><dd x-text="ad?.member"></dd></div>
                            <div><dt>{{ __('Contact') }}</dt><dd><a :href="ad?.whatsapp || ('tel:' + ad?.contact)" target="_blank" rel="noopener" x-text="ad?.contact"></a></dd></div>
                            <div x-show="ad?.link"><dt>{{ __('Link') }}</dt><dd><a :href="ad?.link && (/^https?:\/\//.test(ad.link) ? ad.link : 'https://' + ad.link)" target="_blank" rel="noopener" x-text="ad?.link"></a></dd></div>
                            <div x-show="ad?.file"><dt>{{ __('File') }}</dt><dd><a :href="ad?.file" target="_blank" rel="noopener">{{ __('Open file') }}</a></dd></div>
                            <div><dt>{{ __('Created At') }}</dt><dd x-text="ad?.created"></dd></div>
                            <div x-show="ad?.expires"><dt>{{ __('Expires At') }}</dt><dd x-text="ad?.expires"></dd></div>
                        </dl>
                        <div class="m-menu">
                            <form method="POST" :action="ad?.approval" x-show="ad?.status === 'pending'">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="approved" value="1">
                                <button type="submit"><i class="fa-solid fa-check" style="color: var(--tx-16a34a);"></i> {{ __('Approve') }}</button>
                            </form>
                            <a :href="ad?.edit"><i class="fa-solid fa-pen"></i> {{ __('Edit') }}</a>
                            <form method="POST" :action="ad?.approval" x-show="ad && ad.status !== 'pending'">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="approved" value="0">
                                <button type="submit"><i class="fa-solid fa-rotate-left"></i> {{ __('Back to pending') }}</button>
                            </form>
                            <button type="button" class="is-danger" @click="panel = 'remove'"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                        </div>
                    </div>

                    <form method="POST" :action="ad?.destroy" x-show="panel === 'remove'">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm text-gray-700">{{ __('Delete this announcement? It cannot be undone.') }}</p>
                        <div class="m-sheet-foot">
                            <button type="button" class="m-btn" @click="panel = 'menu'">{{ __('Cancel') }}</button>
                            <button type="submit" class="m-btn is-danger"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
