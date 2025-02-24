<tr>

    <td class="whitespace-nowrap width-1 px-3 py-4 text-sm text-gray-500">
        {{ $member->member->first_and_last_name }}
    </td>

    <td class="whitespace-nowrap width-1 px-3 py-4 text-sm text-gray-500">
        {{ $member->role }}
    </td>

    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        <form action="{{ route('ministries-members.destroy', $member->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fa fa-trash"></i>
            </button>
        </form>
    </td>

</tr>
