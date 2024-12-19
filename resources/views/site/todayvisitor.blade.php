<x-visitors-today-layout>

    <div class="text-center text-white" style="font-size: 40px; margin: 40px; text-transform:uppercase;">
        {{ __('Today Visitors') }}
    </div>
    <div id="grid" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 text-center p-10">
        @foreach($visitors as $invited => $people)
            <div class="visitor">
                <strong>{{ is_numeric($invited) ? ' ' : ($invited === $socialMedia ? $socialMedia : $invited.' convidou:' ) }}</strong>
                {{ $people }}
            </div>
        @endforeach
    </div>
</x-visitors-today-layout>
