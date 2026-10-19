<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Chord suggestions') }}</h2>
    </x-slot>

    <style>
        .suggestion { padding: 1rem 0; border-top: 1px solid #e5e7eb; }
        /* The chord sheet with the suggested changes: new chords green, removed red (struck through), changed blue. */
        .sheet-preview summary { cursor: pointer; font-size: .85rem; font-weight: 600; color: #374151; }
        .sheet-preview .legend { margin-left: .5rem; font-weight: 700; font-size: .75rem; }
        .sheet-preview .inserted { color: #16a34a; }
        .sheet-preview .removed { color: #dc2626; text-decoration: line-through; }
        .sheet-preview .changed { color: #2563eb; }
        .sheet-sections { display: grid; grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr)); gap: .6rem 1.5rem; margin-top: .6rem; padding: .75rem; border: 1px solid #e5e7eb; border-radius: .5rem; background: #fafafa; }
        .sheet-label { font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }
        .sheet-line { display: flex; flex-wrap: wrap; margin-top: .1rem; font-size: .9rem; line-height: 1.15; }
        .sheet-line .seg { display: inline-flex; flex-direction: column; }
        .sheet-line .chord { min-height: 1.05em; padding-right: .3rem; font-size: .8rem; font-weight: 700; color: #4b5563; }
        .sheet-line .chord.inserted { color: #16a34a; }
        .sheet-line .chord.removed { color: #dc2626; text-decoration: line-through; }
        .sheet-line .chord.changed { color: #2563eb; font-weight: 800; }
        .sheet-line .chord.inserted, .sheet-line .chord.removed { font-weight: 800; }
        .change .insert { color: #16a34a; font-weight: 700; }
        .sheet-line .text { white-space: pre; }
        .sheet-comment { font-size: .8rem; font-style: italic; color: #6b7280; }
        .suggestion:first-of-type { border-top: 0; }
        .suggestion-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: .3rem .75rem; }
        .suggestion-head a { font-weight: 700; color: var(--brand-text); }
        .suggestion-meta { font-size: .85rem; color: #6b7280; }
        .suggestion-note { margin-top: .35rem; padding: .4rem .6rem; border-left: 3px solid var(--brand-200); background: #f9fafb; font-size: .9rem; white-space: pre-line; }
        .change { display: flex; align-items: center; gap: .6rem; padding: .3rem 0; font-size: .95rem; }
        .change .where { min-width: 11rem; color: #6b7280; font-size: .85rem; }
        .change .from { font-weight: 700; text-decoration: line-through; color: #9ca3af; }
        .change .to { font-weight: 800; color: #111827; }
        .change .removed { font-weight: 700; color: #dc2626; }
        .change .badge { padding: .05rem .45rem; border-radius: 999px; font-size: .7rem; font-weight: 700; }
        .badge.outdated { background: #fef3c7; color: #92400e; }
        .badge.applied { background: #dcfce7; color: #166534; }
        .badge.rejected { background: #f3f4f6; color: #6b7280; }
        .change input { flex: none; }
        /* Phones: where the change is goes over it; the chord sheet in one column */
        @media (max-width: 639px) {
            .change { flex-wrap: wrap; gap: .25rem .5rem; padding: .45rem 0; border-bottom: 1px dashed #f3f4f6; }
            .change .where { min-width: 0; flex-basis: calc(100% - 2rem); }
            .sheet-sections { grid-template-columns: minmax(0, 1fr); overflow-x: auto; }
        }
    </style>

    @include('music::partials.ui')

    <div class="m-page" style="max-width: 64rem;">
        <div class="space-y-4">
            @include('layouts.alert')

            <div class="m-card m-card-body">
                <h3 class="font-semibold text-gray-800">{{ __('Waiting for review') }} ({{ count($pending) }})</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('Sent from the public setlist page. Uncheck the changes you do not want and approve; the map changes, and the chord sheet too when it has the map\'s chords (the version before stays in the song\'s history).') }}</p>

                @forelse ($pending as $item)
                    @php $suggestion = $item['suggestion']; @endphp
                    <form method="POST" action="{{ route('song-suggestions.approve', $suggestion) }}" class="suggestion">
                        @csrf
                        <div class="suggestion-head">
                            <a href="{{ route('songs.layout.edit', $suggestion->song) }}">{{ $suggestion->song->title }}</a>
                            <span class="suggestion-meta">
                                {{ $suggestion->created_at->format('d/m/Y H:i') }}
                                · {{ $suggestion->author ?: __('Anonymous') }}
                                @if ($suggestion->setlist) · {{ $suggestion->setlist->title }} @endif
                                · {{ __('in the key of :key', ['key' => $suggestion->song->musical_key ?: '—']) }}
                            </span>
                        </div>
                        @if ($suggestion->note)
                            <div class="suggestion-note">{{ $suggestion->note }}</div>
                        @endif
                        <div class="mt-2">
                            @foreach ($item['changes'] as $index => $change)
                                <label class="change">
                                    <input type="checkbox" name="accepted[]" value="{{ $index }}" @checked(! $change['outdated']) @disabled($change['outdated'])>
                                    @if ($change['insert'])
                                        <span class="where">{{ $change['block'] }}{{ $change['bar'] ? ', ' . __('bar :bar, beat :beat', ['bar' => $change['bar'], 'beat' => $change['beat']]) . ($change['and'] ? ' (' . __('and') . ')' : '') : '' }}</span>
                                        <span class="insert">{{ __('insert') }}</span>
                                        → <span class="to">{{ $change['to'] }}</span>
                                        <span class="where">({{ __('during :chord', ['chord' => $change['from']]) }})</span>
                                    @else
                                    <span class="where">{{ $change['block'] }}{{ $change['position'] ? ', ' . __('chord :n', ['n' => $change['position']]) : '' }}</span>
                                    <span class="from">{{ $change['from'] }}</span>
                                    @endif
                                    @if ($change['insert'])
                                    @elseif ($change['to'] === null)
                                        <span class="removed">{{ __('remove') }}</span>
                                    @else
                                        → <span class="to">{{ $change['to'] }}</span>
                                    @endif
                                    @if ($change['outdated'])
                                        <span class="badge outdated" title="{{ __('The song has :chord there now', ['chord' => $change['current'] ?? '—']) }}">{{ __('outdated') }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @if ($item['preview'])
                            <details class="sheet-preview mt-3" open>
                                <summary>{{ __('Chord sheet with the changes') }}
                                    <span class="legend"><span class="inserted">{{ __('new') }}</span> · <span class="removed">{{ __('removed') }}</span> · <span class="changed">{{ __('changed') }}</span></span>
                                </summary>
                                <div class="sheet-sections">
                                    @foreach ($item['preview'] as $section)
                                        <div class="sheet-section">
                                            <div class="sheet-label">{{ $section['label'] }}</div>
                                            @foreach ($section['lines'] as $line)
                                                @if (isset($line['comment']))
                                                    <div class="sheet-comment">{{ $line['comment'] }}</div>
                                                @else
                                                    <div class="sheet-line">@foreach ($line['segments'] as $segment)<span class="seg"><span class="chord {{ $segment['kind'] }}" @if ($segment['from']) title="{{ __('was :chord', ['chord' => $segment['from']]) }}" @endif>{{ $segment['chord'] }}</span><span class="text">{{ $segment['text'] }}</span></span>@endforeach</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @elseif ($suggestion->song->chord_sheet)
                            <p class="text-sm text-gray-500 mt-3">{{ __('The chord sheet does not have the chords of the map: only the map will change.') }}</p>
                        @endif
                        <div class="flex flex-wrap gap-2 mt-3">
                            <x-primary-button>{{ __('Approve checked') }}</x-primary-button>
                            <x-secondary-button type="submit" formaction="{{ route('song-suggestions.reject', $suggestion) }}" onclick="return confirm(@js(__('Reject this whole suggestion?')))">{{ __('Reject all') }}</x-secondary-button>
                        </div>
                    </form>
                @empty
                    <p class="text-gray-500 mt-3">{{ __('No suggestions waiting.') }}</p>
                @endforelse
            </div>

            @if (count($reviewed))
                <div class="m-card m-card-body">
                    <h3 class="font-semibold text-gray-800">{{ __('Last reviewed') }}</h3>
                    @foreach ($reviewed as $item)
                        @php $suggestion = $item['suggestion']; @endphp
                        <div class="suggestion">
                            <div class="suggestion-head">
                                <a href="{{ route('songs.history', $suggestion->song) }}">{{ $suggestion->song->title }}</a>
                                <span class="suggestion-meta">{{ $suggestion->author ?: __('Anonymous') }} · {{ $suggestion->reviewed_at?->format('d/m/Y H:i') }} · {{ $suggestion->reviewer?->name }}</span>
                            </div>
                            @foreach ($item['changes'] as $change)
                                <div class="change">
                                    <span class="where">{{ $change['block'] }}</span>
                                    <span class="from">{{ $change['from'] }}</span>
                                    @if ($change['insert']) <span class="insert">{{ __('insert') }}</span> → <span class="to">{{ $change['to'] }}</span>
                                    @elseif ($change['to'] === null) <span class="removed">{{ __('remove') }}</span> @else → <span class="to">{{ $change['to'] }}</span> @endif
                                    <span class="badge {{ $change['result'] ?? 'rejected' }}">{{ __(['applied' => 'applied', 'rejected' => 'rejected', 'outdated' => 'outdated'][$change['result'] ?? 'rejected']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
