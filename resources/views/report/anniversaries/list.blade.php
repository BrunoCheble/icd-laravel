<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    @foreach($birthdays as $member)
        @include('report.anniversaries.card')
    @endforeach
</div>
