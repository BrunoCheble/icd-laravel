<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('History') }} · {{ $song->title }}
        </h2>
    </x-slot>

    <style>
        .revision { border-top: 1px solid #f3f4f6; padding: .75rem 0; }
        .revision:first-of-type { border-top: 0; }
        .revision-head { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .75rem; }
        .revision-date { font-weight: 700; font-variant-numeric: tabular-nums; }
        .revision-source { font-size: .85rem; color: #4b5563; }
        .revision-tags { display: flex; flex-wrap: wrap; gap: .25rem; }
        .revision-tag { padding: .05rem .5rem; border-radius: 999px; background: #eef2ff; color: #4338ca; font-size: .72rem; font-weight: 700; }
        .revision-summary { font-size: .8rem; color: #6b7280; }
        .revision details summary { cursor: pointer; font-size: .85rem; font-weight: 600; color: #4f46e5; margin-top: .35rem; }
        .revision-preview { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; margin-top: .5rem; }
        @media (min-width: 1024px) { .revision-preview { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
        .revision-preview h4 { margin: 0 0 .3rem; font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }
        .revision-preview pre { margin: 0; padding: .6rem .7rem; border-radius: .4rem; background: #f9fafb; font: .8rem/1.45 ui-monospace, SFMono-Regular, Menlo, monospace; white-space: pre-wrap; max-height: 28rem; overflow: auto; }
    </style>

    @include('songs.partials.workspace')

    <div class="ws-page">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="ws-card bg-white shadow sm:rounded-lg">
                @include('songs.partials.tabs', ['song' => $song, 'current' => 'history'])
                @include('layouts.alert')

                <p class="text-sm text-gray-600 mb-3">{{ __('Each time the chord map, the chord sheet, the key or the tempo change, the version before is kept here (the last :count). Restoring a version keeps the current one in the history too.', ['count' => \App\Models\SongRevision::KEEP]) }}</p>

                @forelse ($revisions as $item)
                    @php
                        $revision = $item['revision'];
                        $labels = ['structure' => __('Map'), 'chord_sheet' => __('Chord sheet'), 'musical_key' => __('Key'), 'bpm' => 'BPM'];
                    @endphp
                    <div class="revision">
                        <div class="revision-head">
                            <span class="revision-date">{{ $revision->created_at->format('d/m/Y H:i') }}</span>
                            <span class="revision-source">{{ $item['source'] }}@if ($revision->user) · {{ $revision->user->name }}@endif</span>
                            <span class="revision-tags">
                                @foreach ($item['changed'] as $field)
                                    <span class="revision-tag" title="{{ __('Changed by the edit saved after this version') }}">{{ $labels[$field] }}</span>
                                @endforeach
                            </span>
                            <form method="POST" action="{{ route('songs.history.restore', [$song, $revision]) }}" class="ml-auto"
                                onsubmit="return confirm(@js(__('Restore the version of :date? The chord map, chord sheet, key and tempo go back to it (what the song has now stays in the history).', ['date' => $revision->created_at->format('d/m/Y H:i')])))">
                                @csrf
                                <button type="submit" class="ws-btn"><i class="fa-solid fa-clock-rotate-left"></i> {{ __('Restore') }}</button>
                            </form>
                        </div>
                        <div class="revision-summary">
                            {{ count($item['blocks']) }} {{ __('blocks') }} · {{ $item['chords'] }} {{ __('chords') }} · {{ count($item['sections']) }} {{ __('sections in the chord sheet') }}@if ($revision->musical_key) · {{ __('Key') }} {{ $revision->musical_key }}@endif @if ($revision->bpm) · {{ $revision->bpm }} BPM @endif
                        </div>
                        <details>
                            <summary>{{ __('See this version') }}</summary>
                            <div class="revision-preview">
                                <div>
                                    <h4>{{ __('Map') }}</h4>
                                    <pre>@foreach ($item['blocks'] as $block){{ str_replace('_', ' ', $block['section'] ?? '') }}@if (($block['jump'] ?? null) === 'start') ↺@endif{{ ($block['start'] ?? null) ? ' (' . $block['start'] . ')' : '' }}
  {{ implode(' ', array_map(fn ($chord) => $chord === '|' ? '/' : $chord, $block['chords'] ?? [])) }}
@endforeach</pre>
                                </div>
                                <div>
                                    <h4>{{ __('Chord sheet') }}</h4>
                                    <pre>@foreach ($item['sections'] as $section)== {{ $section['label'] ?? str_replace('_', ' ', $section['section'] ?? '') }}
@foreach ($section['lines'] ?? [] as $line){{ $line }}
@endforeach
@endforeach</pre>
                                </div>
                            </div>
                        </details>
                    </div>
                @empty
                    <p class="text-gray-500">{{ __('No earlier versions yet: they are kept from now on, each time the song changes.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
