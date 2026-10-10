<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Chord suggestions') }}</h2>
    </x-slot>

    <style>
        .suggestion { padding: 1rem 0; border-top: 1px solid #e5e7eb; }
        .suggestion:first-of-type { border-top: 0; }
        .suggestion-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: .3rem .75rem; }
        .suggestion-head a { font-weight: 700; color: #4f46e5; }
        .suggestion-meta { font-size: .85rem; color: #6b7280; }
        .suggestion-note { margin-top: .35rem; padding: .4rem .6rem; border-left: 3px solid #c7d2fe; background: #f9fafb; font-size: .9rem; white-space: pre-line; }
        .change { display: flex; align-items: center; gap: .6rem; padding: .3rem 0; font-size: .95rem; }
        .change .where { min-width: 11rem; color: #6b7280; font-size: .85rem; }
        .change .from { font-weight: 700; text-decoration: line-through; color: #9ca3af; }
        .change .to { font-weight: 800; color: #111827; }
        .change .removed { font-weight: 700; color: #dc2626; }
        .change .badge { padding: .05rem .45rem; border-radius: 999px; font-size: .7rem; font-weight: 700; }
        .badge.outdated { background: #fef3c7; color: #92400e; }
        .badge.applied { background: #dcfce7; color: #166534; }
        .badge.rejected { background: #f3f4f6; color: #6b7280; }
    </style>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('layouts.alert')

            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6">
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
                            @foreach ($item['changes'] as $change)
                                <label class="change">
                                    <input type="checkbox" name="accepted[]" value="{{ $change['n'] }}" @checked(! $change['outdated']) @disabled($change['outdated'])>
                                    <span class="where">{{ $change['block'] }}{{ $change['position'] ? ', ' . __('chord :n', ['n' => $change['position']]) : '' }}</span>
                                    <span class="from">{{ $change['from'] }}</span>
                                    @if ($change['to'] === null)
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
                <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6">
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
                                    @if ($change['to'] === null) <span class="removed">{{ __('remove') }}</span> @else → <span class="to">{{ $change['to'] }}</span> @endif
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
