<tr>

    <td class="whitespace-nowrap width-1 px-3 py-4 text-sm text-gray-500">
        {{ $ministry->name }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        <div class="flex gap-2">
            @foreach ($ministry->members as $member)
                @include('member.avatar', ['class' => 'w-8 h-8'])
            @endforeach
            <!-- green circle add member icon -->
            <a href="{{ route('ministries-members.manage', $ministry->id) }}" class="text-white bg-green-500 w-8 h-8 rounded-full shadow-lg flex items-center justify-center hover:opacity-75">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </td>
    <td style="width: 100px" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">

    </td>

</tr>
