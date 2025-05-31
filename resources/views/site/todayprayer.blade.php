<x-prayers-today-layout>
    <div id="grid" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-6 text-center p-10">
        @foreach($prayers as $prayer)
            <div class="visitor">
                <strong>{{ $prayer->name }}</strong>
                <p>{{ $prayer->request }}</p>
            </div>
        @endforeach
    </div>
</x-prayers-today-layout>
