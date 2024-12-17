<tr>

    <td class="whitespace-nowrap width-1 px-3 py-4 text-sm text-gray-500">
        {{ $ministry->name }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        <a href="{{ route('ministries-members.manage', $ministry->id) }}" class="btn btn-primary">Manage</a>
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">

    </td>

</tr>
