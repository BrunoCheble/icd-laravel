<!-- member row from table with -->
<tr>

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
        @include('member.menu')
    </td>

</tr>
