<div class="w-full border border-gray-200 rounded-lg shadow">

    <div x-data="" class="flex flex-row p-10 items-center flex-start">

        @foreach($family as $member)
            <div class="relative w-12 h-12 mb-3 rounded-full shadow-lg overflow-hidden">
                <img class="absolute top-0 left-0 w-full h-full object-cover cursor-pointer hover:opacity-75"
                src="{{ $member->url_photo }}" />
            </div>
        @endforeach

    </div>
</div>
