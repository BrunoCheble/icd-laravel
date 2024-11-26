<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    @foreach($members as $member)
        @include('member.list.card')
    @endforeach
</div>
