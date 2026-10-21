<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Announcements') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        /* A card per announcement, newest first, read left to right (one column on phones) */
        .notices { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(19rem, 100%), 1fr)); gap: 1rem; align-items: start; margin: .75rem 0 1rem; }
        .notice { overflow: hidden; display: flex; flex-direction: column; }
        .notice-media { position: relative; display: grid; place-items: center; aspect-ratio: 16 / 10; background: linear-gradient(135deg, var(--brand-soft), var(--bg-fff7ed)); color: var(--brand-hover); font-size: 2rem; text-decoration: none; }
        .notice-media.is-plain { aspect-ratio: auto; height: 4.5rem; font-size: 1.4rem; }
        .notice-media img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .notice-media small { display: block; margin-top: .25rem; font-size: .75rem; font-weight: 700; text-align: center; }
        .notice-media .m-tag { position: absolute; top: .6rem; left: .6rem; box-shadow: 0 1px 2px rgba(0, 0, 0, .15); }
        .notice-body { padding: .85rem 1rem 1rem; }
        .notice-head { display: flex; align-items: flex-start; gap: .5rem; }
        .notice-head h3 { flex: 1; min-width: 0; margin: 0; font-size: 1rem; font-weight: 800; color: var(--tx-111827); line-height: 1.3; }
        .notice-text { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin: .4rem 0 0; font-size: .88rem; line-height: 1.45; color: var(--tx-4b5563); white-space: pre-line; }
        .notice-foot { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .75rem; margin-top: .75rem; font-size: .8rem; color: var(--tx-6b7280); }
        .notice-foot i { margin-right: .25rem; color: var(--tx-9ca3af); }
        .notice-empty { padding: 2.5rem 1rem; text-align: center; color: var(--tx-6b7280); }
        .notice-empty i { display: block; margin-bottom: .5rem; font-size: 1.6rem; color: var(--tx-d1d5db); }
        .notice-detail { margin: 0 0 1rem; font-size: .92rem; line-height: 1.5; color: var(--tx-374151); white-space: pre-line; overflow-wrap: anywhere; }
        .notice-facts { display: grid; margin: 0 0 1rem; }
        .notice-facts > div { display: grid; grid-template-columns: 7rem minmax(0, 1fr); gap: .75rem; padding: .45rem 0; border-bottom: 1px solid var(--bd-f3f4f6); }
        .notice-facts dt { font-size: .82rem; font-weight: 600; color: var(--tx-6b7280); }
        .notice-facts dd { margin: 0; font-size: .9rem; color: var(--tx-111827); overflow-wrap: anywhere; }
        .notice-facts a { color: var(--brand-text); }
        .notice-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .6rem; }
        .notice-chips span { padding: .15rem .55rem; border-radius: 999px; background: var(--bg-f3f4f6); color: var(--tx-4b5563); font-size: .75rem; font-weight: 600; }
        .notice-talk { font-weight: 700; color: var(--brand-text); }
        .notice-comments { display: grid; gap: .5rem; margin: 0 0 1rem; }
        .notice-comments h3 { margin: 0; font-size: .8rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--tx-6b7280); }
        .notice-comment { padding: .65rem .75rem; border: 1px solid var(--bd-e5e7eb); border-radius: .7rem; }
        .notice-comment.is-new { border-color: var(--bd-fcd34d); background: var(--bg-fffbeb); }
        .notice-private { margin: 0; font-size: .78rem; color: var(--tx-6b7280); }
        .notice-stars { color: #f59e0b; letter-spacing: .05em; }
        .notice-comment p { margin: .25rem 0 .5rem; font-size: .9rem; line-height: 1.45; color: var(--tx-374151); white-space: pre-line; overflow-wrap: anywhere; }
        .notice-comment small { font-size: .78rem; color: var(--tx-6b7280); }
        .notice-comment-actions { display: flex; gap: .4rem; }
        .notice-comment-actions form { margin: 0; }
        .notice-comment-actions .m-btn { min-height: 32px; padding: 0 .65rem; font-size: .8rem; }
    </style>

    @php
        $status = $filters['status'] ?? '';
        $search = $filters['search'] ?? '';
        $categories = \App\Modules\Announcements\Support\AnnouncementCategories::forForms();
        // The categories, then the old types still found in older announcements.
        $types = collect($categories)->map(fn ($category) => $category['label'])->all() + \App\Enums\AnnouncementType::options();
        $icons = collect($categories)->map(fn ($category) => $category['icon'])->all() + ['donation' => 'fa-hand-holding-heart', 'product' => 'fa-tag'];
        $statuses = ['pending' => [__('Waiting for approval'), 'is-part'], 'published' => [__('Published'), 'is-ok'], 'expired' => [__('Expired'), 'is-off']];
        $tabs = ['' => __('All'), 'pending' => __('To approve'), 'published' => __('Published'), 'expired' => __('Expired'), 'comments' => __('Comments not read')];
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
                @can('announcements.write')
                    <a href="{{ route('announcements.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('Add new') }}</a>
                @endcan
            </div>
        </div>

        {{-- Status at hand: the ones to approve first --}}
        <nav class="m-status" aria-label="{{ __('Status') }}">
            @foreach ($tabs as $value => $label)
                <a href="{{ route('announcements.index', array_filter(['status' => $value, 'type' => $filters['type'] ?? null, 'search' => $search])) }}" @class(['is-active' => $status === $value])>
                    {{ $label }} <small>{{ $value === '' ? $statusCounts['pending'] + $statusCounts['published'] + $statusCounts['expired'] : $statusCounts[$value] }}</small>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('announcements.index') }}" class="m-toolbar">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="m-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Title, text or member') }}" aria-label="{{ __('Search') }}">
            </label>
            <select name="type" class="m-input" style="width: auto; min-height: 40px;" onchange="this.form.submit()" aria-label="{{ __('Category') }}">
                <option value="">{{ __('All categories') }}</option>
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
            <div class="m-card notice-empty mt-3"><i class="fa-solid fa-bullhorn"></i>{{ $search !== '' || ! empty($filters['type']) ? __('No announcements match these filters.') : __('No announcements here.') }}</div>
        @else
            <div class="notices">
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
                            'file' => $announcement->isPdf() ? $announcement->url_image : null,
                            'details' => $announcement->detail_lines,
                            'photos' => $announcement->photo_urls,
                            'comments' => $announcement->comments->map(fn ($comment) => [
                                'name' => $comment->member?->first_and_last_name ?? __('Member'),
                                'body' => $comment->body,
                                'date' => $comment->created_at?->format('d/m/Y H:i'),
                                'read' => $comment->read_at !== null,
                                'readUrl' => route('announcements.comments.read', $comment),
                                'destroy' => route('announcements.comments.destroy', $comment),
                            ])->values()->all(),
                            'rating' => $announcement->ratingSummary(),
                            'ratings' => $announcement->ratings->sortByDesc('updated_at')->map(fn ($rating) => [
                                'name' => $rating->member?->first_and_last_name ?? __('Member'),
                                'stars' => $rating->stars,
                            ])->values()->all(),
                            'status' => $announcement->status(),
                            'statusLabel' => $statusLabel,
                            'created' => $announcement->created_at?->format('d/m/Y'),
                            'expires' => $day($announcement->expires_at),
                            'edit' => route('announcements.edit', $announcement),
                            'approval' => route('announcements.approval', $announcement),
                            'destroy' => route('announcements.destroy', $announcement),
                        ];
                    @endphp
                    <article class="m-card notice">
                        <a href="{{ route('announcements.edit', $announcement) }}" class="notice-media" aria-label="{{ $announcement->title }}">
                            <img src="{{ $announcement->picture_url }}" alt="" loading="lazy">
                            @if ($announcement->isPdf())<span class="m-tag is-media" style="position: absolute; right: .6rem; top: .6rem; left: auto;"><i class="fa-solid fa-file-pdf"></i> PDF</span>@endif
                            @if ($announcement->photo_urls)<span class="m-tag is-media" style="position: absolute; right: .6rem; top: .6rem; left: auto;"><i class="fa-solid fa-images"></i> {{ count($announcement->photo_urls) }}</span>@endif
                            <span class="m-tag {{ $statusTag }}">{{ $statusLabel }}</span>
                        </a>
                        <div class="notice-body">
                            <div class="notice-head">
                                <h3>{{ $announcement->title }}</h3>
                                <button type="button" class="m-btn is-ghost is-icon" style="margin: -.4rem -.5rem 0 0;" aria-label="{{ __('Actions') }}: {{ $announcement->title }}" @click="ad = @js($ad); panel = 'menu'"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            </div>
                            <p class="notice-text">{{ $announcement->description }}</p>
                            @if ($announcement->detail_lines)
                                <div class="notice-chips">@foreach ($announcement->detail_lines as $line)<span title="{{ $line['label'] }}">{{ $line['short'] }}</span>@endforeach</div>
                            @endif
                            <div class="notice-foot">
                                <span><i class="fa-solid {{ $icons[$announcement->type] ?? 'fa-bullhorn' }}"></i>{{ $announcement->type_name }}</span>
                                @if ($announcement->member)<span><i class="fa-solid fa-user"></i>{{ $announcement->member->first_and_last_name }}</span>@endif
                                @if ($announcement->expires_at)<span><i class="fa-regular fa-calendar"></i>{{ $announcement->status() === 'expired' ? __('Expired on :date', ['date' => $day($announcement->expires_at)]) : __('Until :date', ['date' => $day($announcement->expires_at)]) }}</span>@endif
                                @php($rating = $announcement->ratingSummary())
                                @if ($rating['count'])<span title="{{ __('Members\' rating') }}"><span class="notice-stars">★</span> {{ number_format($rating['average'], 1, ',', '') }} ({{ $rating['count'] }})</span>@endif
                                @php($unread = $announcement->comments->whereNull('read_at')->count())
                                @if ($announcement->comments->isNotEmpty())
                                    <button type="button" class="notice-talk" style="border: 0; background: none; padding: 0; cursor: pointer;" @click="ad = @js($ad); panel = 'menu'">
                                        <i class="fa-regular fa-comment"></i>{{ trans_choice(':count comment|:count comments', $announcement->comments->count(), ['count' => $announcement->comments->count()]) }}@if ($unread) · {{ __(':count not read', ['count' => $unread]) }}@endif
                                    </button>
                                @endif
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
                        <p class="notice-detail" x-text="ad?.description"></p>
                        <dl class="notice-facts">
                            <div x-show="ad?.member"><dt>{{ __('Member') }}</dt><dd x-text="ad?.member"></dd></div>
                            <div><dt>{{ __('Contact') }}</dt><dd><a :href="ad?.whatsapp || ('tel:' + ad?.contact)" target="_blank" rel="noopener" x-text="ad?.contact"></a></dd></div>
                            <div x-show="ad?.link"><dt>{{ __('Link') }}</dt><dd><a :href="ad?.link && (/^https?:\/\//.test(ad.link) ? ad.link : 'https://' + ad.link)" target="_blank" rel="noopener" x-text="ad?.link"></a></dd></div>
                            <div x-show="ad?.file"><dt>{{ __('File') }}</dt><dd><a :href="ad?.file" target="_blank" rel="noopener">{{ __('Open file') }}</a></dd></div>
                            <div><dt>{{ __('Created At') }}</dt><dd x-text="ad?.created"></dd></div>
                            <div x-show="ad?.expires"><dt>{{ __('Expires At') }}</dt><dd x-text="ad?.expires"></dd></div>
                            <template x-for="line in (ad?.details || [])" :key="line.label">
                                <div><dt x-text="line.label"></dt><dd x-text="line.value"></dd></div>
                            </template>
                        </dl>
                        {{-- The photos of a donation or a sale --}}
                        <div class="notice-chips" x-show="ad?.photos?.length" style="margin: 0 0 1rem;">
                            <template x-for="photo in (ad?.photos || [])" :key="photo">
                                <a :href="photo" target="_blank" rel="noopener"><img :src="photo" alt="" style="width: 5.5rem; aspect-ratio: 16 / 10; object-fit: cover; border-radius: .5rem;"></a>
                            </template>
                        </div>
                        {{-- The members' stars --}}
                        <div class="notice-comments" x-show="ad?.ratings?.length">
                            <h3>{{ __('Rating') }} · <span class="notice-stars">★</span> <span x-text="String(ad?.rating?.average ?? '').replace('.', ',')"></span> (<span x-text="ad?.rating?.count"></span>)</h3>
                            <template x-for="rating in (ad?.ratings || [])" :key="rating.name">
                                <small><span class="notice-stars" x-text="'★'.repeat(rating.stars) + '☆'.repeat(5 - rating.stars)"></span> <span x-text="rating.name"></span></small>
                            </template>
                        </div>
                        {{-- The members' private comments, only for the team --}}
                        <div class="notice-comments" x-show="ad?.comments?.length">
                            <h3>{{ __('Private comments') }}</h3>
                            <p class="notice-private">{{ __('Sent by members to the church\'s team; they are not shown on the site.') }}</p>
                            <template x-for="comment in (ad?.comments || [])" :key="comment.readUrl">
                                <div class="notice-comment" :class="{ 'is-new': !comment.read }">
                                    <small><strong x-text="comment.name"></strong> · <span x-text="comment.date"></span><span x-show="!comment.read"> · {{ __('Not read') }}</span></small>
                                    <p x-text="comment.body"></p>
                                    <div class="notice-comment-actions">
                                        <form method="POST" :action="comment.readUrl">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="read" :value="comment.read ? 0 : 1">
                                            <button type="submit" class="m-btn" :class="{ 'is-primary': !comment.read }">
                                                <i class="fa-solid" :class="comment.read ? 'fa-envelope' : 'fa-check'"></i> <span x-text="comment.read ? @js(__('Mark as not read')) : @js(__('Mark as read'))"></span>
                                            </button>
                                        </form>
                                        <form method="POST" :action="comment.destroy" onsubmit="return confirm(@js(__('Delete this comment?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="m-btn is-ghost"><i class="fa-solid fa-trash"></i> {{ __('Delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>
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
