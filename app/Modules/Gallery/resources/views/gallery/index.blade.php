<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gallery') }}
        </h2>
    </x-slot>

    @include('partials.ui')

    <style>
        .gl-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(15rem, 100%), 1fr)); gap: 1rem; margin-top: .75rem; }
        .gl-card { display: flex; flex-direction: column; overflow: hidden; color: inherit; text-decoration: none; }
        .gl-cover { display: grid; place-items: center; aspect-ratio: 4 / 3; background: var(--bg-f3f4f6); color: var(--tx-9ca3af); font-size: 2rem; }
        .gl-cover img { width: 100%; height: 100%; object-fit: cover; }
        .gl-body { display: block; padding: .8rem 1rem 1rem; }
        .gl-body strong { display: block; font-weight: 800; color: var(--tx-111827); }
        .gl-card:hover strong { color: var(--brand-text); }
        .gl-body small { display: block; margin-top: .1rem; font-size: .82rem; color: var(--tx-6b7280); }
        .gl-body .m-tags { display: flex; margin-top: .45rem; }
    </style>

    <div class="m-page">
        @include('layouts.alert')

        <div class="m-head">
            <div>
                <h1>{{ __('Gallery') }}</h1>
                <p>{{ __('The church\'s events with their photos, on the site\'s "Galeria".') }}</p>
            </div>
            @can('gallery.write')
                <div class="m-head-actions">
                    <a href="{{ route('gallery.create') }}" class="m-btn is-primary"><i class="fa-solid fa-plus"></i> {{ __('New event') }}</a>
                </div>
            @endcan
        </div>

        @if ($events->isEmpty())
            <div class="m-card m-section"><p class="m-muted">{{ __('No events yet.') }}</p></div>
        @else
            <div class="gl-list">
                @foreach ($events as $event)
                    @php($cover = $event->cover())
                    <a href="{{ route('gallery.show', $event) }}" class="m-card gl-card">
                        <span class="gl-cover">
                            @if ($cover)<img src="{{ $cover->thumbUrl() }}" alt="" loading="lazy">@else<i class="fa-regular fa-images"></i>@endif
                        </span>
                        <span class="gl-body">
                            <strong>{{ $event->name }}</strong>
                            <small>{{ $event->event_date->translatedFormat('d M Y') }} · {{ trans_choice(':count photo|:count photos', $event->photos->count(), ['count' => $event->photos->count()]) }}</small>
                            <span class="m-tags">
                                @if ($event->is_published)
                                    <span class="m-tag is-ok"><i class="fa-solid fa-globe"></i> {{ __('On the site') }}</span>
                                @else
                                    <span class="m-tag is-off"><i class="fa-solid fa-eye-slash"></i> {{ __('Not on the site') }}</span>
                                @endif
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
