<div class="w-full {{ $member->isInactive() ? 'bg-red-100' : ($member->isPending() ? 'bg-yellow-100' : 'bg-white') }} border border-gray-200 rounded-lg shadow">
    <div class="flex justify-end px-4 pt-4">
        @include('member.menu.index')
    </div>
    <div x-data="" class="flex flex-col items-center pb-10">
        @include('member.avatar', ['class' => 'w-24 h-24 mb-3'])

        <h5 class="mb-1 text-xl font-medium text-gray-900">{{ $member->first_and_last_name }}</h5>
        <span class="text-sm text-gray-500">
            {{ $member->phone_number }}
        </span>

    </div>
</div>



