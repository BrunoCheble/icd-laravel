<!-- member row from table with in color background by status -->
<tr class="{{ $member->isInactive() ? 'bg-red-100' : ($member->isPending() ? 'bg-yellow-100' : 'bg-white') }}">

    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        @include('member.avatar', ['class' => 'w-8 h-8'])
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->first_and_last_name }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->document_number }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->contact }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->city }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->age }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->marital_status_name }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->created_at_formatted }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 gap-4 flex">
        @include('member.menu.index')
    </td>

</tr>
