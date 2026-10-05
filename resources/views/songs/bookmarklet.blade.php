<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import from a chord site') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-2xl space-y-6 text-sm text-gray-700">
                    <p>{{ __('The "Send to ICD" button reads the chord sheet you are viewing (e.g. on Cifra Club) and opens a new song already filled in: title, artist, key, YouTube, source, chord sheet and structure. Nothing is saved until you review and click Save.') }}</p>

                    <div>
                        <p class="font-semibold text-gray-900">1. {{ __('Drag this button to your bookmarks bar:') }}</p>
                        <div class="mt-3">
                            {{-- Rendered with {!! !!} on purpose: the href is a percent-encoded javascript: URL built on the server. --}}
                            <a href="{!! $bookmarklet !!}" onclick="event.preventDefault(); alert(@js(__('Drag this button to the bookmarks bar instead of clicking it.')));"
                                class="inline-block rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white shadow-sm hover:bg-indigo-500">{{ __('Send to ICD') }}</a>
                        </div>
                        <p class="mt-2 text-gray-500">{{ __('If the bookmarks bar is hidden, show it with Ctrl+Shift+B (Cmd+Shift+B on Mac).') }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-900">2. {{ __('Open the song on the chord site and click the bookmark.') }}</p>
                        <p class="mt-1">{{ __('A new tab opens with the song form filled in. If the song has no structure, an initial chord map is generated from the chord sheet.') }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-900">3. {{ __('Review and save.') }}</p>
                        <p class="mt-1">{{ __('Fields that could not be found on the page are listed at the top of the form.') }}</p>
                    </div>

                    <div>
                        <a href="{{ route('songs.index') }}" class="text-indigo-600 font-bold hover:text-indigo-900">{{ __('Back') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
