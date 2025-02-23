<tr class="even:bg-gray-50">
    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-semibold text-gray-900">{{ $financial->id }}</td>

    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->member?->first_and_last_name }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->description }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->amount }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->date }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->type }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->category?->name }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->ministry?->name }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->processed_at }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->notes }}</td>

    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
        <form action="{{ route('financial-movements.destroy', $financial->id) }}" method="POST">
            <a href="{{ route('financial-movements.show', $financial->id) }}"
                class="text-gray-600 font-bold hover:text-gray-900 mr-2">{{ __('Show') }}</a>
            <a href="{{ route('financial-movements.edit', $financial->id) }}"
                class="text-indigo-600 font-bold hover:text-indigo-900  mr-2">{{ __('Edit') }}</a>
            @csrf
            @method('DELETE')
            <a href="{{ route('financial-movements.destroy', $financial->id) }}"
                class="text-red-600 font-bold hover:text-red-900"
                onclick="event.preventDefault(); confirm('Are you sure to delete?') ? this.closest('form').submit() : false;">{{ __('Delete') }}</a>
        </form>
    </td>
</tr>
